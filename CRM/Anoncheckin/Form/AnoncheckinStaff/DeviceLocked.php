<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Anoncheckin_Form_AnoncheckinStaff_DeviceLocked extends CRM_Core_Form {

  var $_validatedValues = [];
  var $_userVars = [];
  var $_sessionOptions = [];
  
  /**
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {

    // Process any values given (in query params or POST fields)
    $this->_processValues();

    $this->assign('userVars', $this->_userVars);

    // add form elements
    $this->add(
      'hidden', // field type
      'deviceKey', // field name
      '', // label
      ['id' => 'deviceKey'],
    );
    $this->add(
      'hidden', // field type
      'p', // field name
      '', // label
      ['id' => 'p',],
    );
    $this->add(
      'hidden', // field type
      'ph', // field name
      '', // label
      ['id' => 'ph',],
    );

    if (
      ($this->_validatedValues['deviceKey'] ?? NULL)
      && ($this->_validatedValues['p'] ?? NULL)
    ) {
      // This doesn't need to happen unless we have both deviceKey and 'p'.
      $this->_sessionOptions = $this->_buildSessionOptions();

      $sessionElementNames = [];
      foreach ($this->_sessionOptions as $sessionOption) {
        $radioOptions = $sessionOption['radioOptions'];
        $sessionGroupTitle = $sessionOption['groupTitle'];
        $sessionGroupId = $sessionOption['groupId'];
        $elementName = 'sessionGroup_' . $sessionGroupId;
        $this->addRadio($elementName, $sessionGroupTitle, $radioOptions, [], ''); 
        $sessionElementNames[] = $elementName;
      }
      $this->assign('sessionElementNames', $sessionElementNames);
      
      $this->addButtons([
        [
          'type' => 'done',
          'name' => E::ts('Submit'),
          'isDefault' => TRUE,
        ],
      ]);
    }
    
    

    CRM_Core_Resources::singleton()->addStyleFile(E::LONG_NAME, '/css/qrScanner.css');
    CRM_Core_Resources::singleton()->addScriptUrl('https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js');
    CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, '/js/qrScanner.js');
    CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, '/js/CRM_Anoncheckin_Form_AnoncheckinStaff.js');

    // Pass useful info to JS.
    $jsVars = [
      'externAppUrl' => CRM_Anoncheckin_Utils_Extern::getAppUrl(),
    ];
    CRM_Core_Resources::singleton()->addVars('anoncheckin', $jsVars);
      
      
    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess(): void {
    $values = $this->exportValues();
    // Update badge participant sessions (this form represents the canononical full set).
    $p = $values['p'];
    // First, get all existing sessions for participant.
    $sessionParticipants = \Civi\Api4\AnoncheckinSessionParticipant::get()
      ->addWhere('participant_id', '=', $p)
      ->execute();
    $existingSessionParticipantIdsBySessionId = [];
    foreach ($sessionParticipants as $sessionParticipant) {
      $existingSessionParticipantIdsBySessionId[$sessionParticipant['session_id']] = $sessionParticipant['id'];
    }
    $existingSessionIds = array_keys($existingSessionParticipantIdsBySessionId);

    // Determine which should be deleted, and which added (we'll avoid a more
    // ham-fisted "delete all, then create all", in order to reduce the chance
    // of needless notification/activity generation).
    $sessionsToDelete = $sessionsToCreate = $sessionsSelected = [];
    
    $sessionGroupIdsBySessionId = [];
    foreach ($this->_sessionOptions as $sessionGroup) {
      $elementName = 'sessionGroup_' . $sessionGroup['groupId'];
      $groupSelection = ($values[$elementName] ?? 0);
      if ($groupSelection) {
        $sessionsSelected[] = $groupSelection;
      }
      foreach ($sessionGroup['radioOptions'] as $radioOptionValue => $radioOptionLabel) {
        if ($radioOptionValue) {
          $sessionGroupIdsBySessionId[$radioOptionValue] = $sessionGroup['groupId'];
        }
      } 
    }
  
    $sessionsToDelete = array_diff($existingSessionIds, $sessionsSelected);
    $sessionsToCreate = array_diff($sessionsSelected, $existingSessionIds);
    
    // Delete unselected existing sessions;
    foreach ($sessionsToDelete as $sessionToDelete) {
      $sessionParticipantId = $existingSessionParticipantIdsBySessionId[$sessionToDelete];
      \Civi\Api4\AnoncheckinSessionParticipant::delete()
        ->addWhere('id', '=', $sessionParticipantId)
        ->execute();
    }
    
    // Create non-existing selected sessions;
    foreach ($sessionsToCreate as $sessionToCreate) {
      \Civi\Api4\AnoncheckinSessionParticipant::create()
        ->addValue('participant_id', $p)
        ->addValue('session_id', $sessionToCreate)
        ->addValue('session_group_id', $sessionGroupIdsBySessionId[$sessionToCreate])
        ->addValue('session_status_id', CRM_Anoncheckin_Utils_Extern::SESSION_STATUS_COMPLETED)
        ->execute();
    }
          
    // Close the device.
    \Civi\Api4\AnoncheckinDevice::update()
      ->addWhere('device_key', '=', $this->_validatedValues['deviceKey'])
      ->setValues([
        'device_status_id' => CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_CLOSED
      ])
      ->execute();
    // Invalidate any devices locked to badge participant.
    \Civi\Api4\AnoncheckinDevice::update()
      ->addWhere('device_status_id', '=', CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_LOCKED)
      ->addWhere('participant_id', '=', $p)
      ->setValues([
        'device_status_id' => CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_INVALIDATED
      ])
      ->execute();
    
    CRM_Core_Session::singleton()->setStatus(E::ts('Sessions saved.'), 'Success.', 'success no-popup');
    CRM_Core_Session::singleton()->setStatus(E::ts('Please ask the participant to re-scan their badge on their device.'), 'Action required.', 'alert no-popup');

    // Set on-submit redirect path.
    $urlPath = implode('/', $this->urlPath);
    $url = CRM_Utils_System::url($urlPath, "reset=1");
    CRM_Core_Session::singleton()->pushUserContext($url);
    
    parent::postProcess();
    
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames(): array {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  public function setDefaultValues() {
    return $this->_validatedValues;
  }
  
  public function _processValues() {
    // Process deviceKey.
    if ($deviceKey = CRM_Utils_Request::retrieve('deviceKey', 'String')) {
      $device = \Civi\Api4\AnoncheckinDevice::get()
        ->addSelect('id', 'device_status_id:label', 'user_agent_short', 'participant_id')
        ->addWhere('device_key', '=', $deviceKey)
        ->addChain(
          'participant', 
          \Civi\Api4\Participant::get()
          ->addSelect('id', 'contact_id')
          ->addWhere('id', '=', '$participant_id')
          ->addChain(
            'contact',
            \Civi\Api4\Contact::get()
            ->addSelect('display_name')
            ->addWhere('id', '=', '$contact_id')
          )
        )
        ->setLimit(1)
        ->execute()
        ->first();
      if ($device) {
        $this->_validatedValues['deviceKey'] = $deviceKey;
        $this->_userVars['device'] = [
          'deviceKey' => $deviceKey,
          'status' => $device['device_status_id:label'],
          'userAgentShort' => $device['user_agent_short'],
          'participantId' => ($device['participant'][0]['id'] ?? NULL),
          'displayName' => ($device['participant'][0]['contact'][0]['display_name'] ?? NULL),
        ];
      }
      else {
        CRM_Core_Session::setStatus('The scanned device does not provide valid data.', 'Invalid device', 'error no-popup');
      }
    }
    
    // Process p (badge participantId)
    if (
      ($p = CRM_Utils_Request::retrieve('p', 'Integer'))
      && ($ph = CRM_Utils_Request::retrieve('ph', 'String'))
    ) {
      if (!CRM_Anoncheckin_Utils_Value::validateValue($p, $ph)) {
        CRM_Core_Session::setStatus('The scanned badge does not provide valid data.', 'Invalid badge', 'error no-popup');
      }
      else {
        $this->_validatedValues['p'] = $p;
        $this->_validatedValues['ph'] = $ph;
        $participant = \Civi\Api4\Participant::get()
          ->addSelect('id', 'contact_id', 'event_id')
          ->addWhere('id', '=', $p)
          ->addChain(
            'contact',
            \Civi\Api4\Contact::get()
            ->addSelect('display_name')
            ->addWhere('id', '=', '$contact_id')
          )
          ->addChain(
            'event',
            \Civi\Api4\Event::get()
            ->addSelect('title')
            ->addWhere('id', '=', '$event_id')
          )
          ->setLimit(1)
          ->execute()
          ->first();
        
        $this->_userVars['badge'] = [
          'participantId' => $p,
          'displayName' => $participant['contact'][0]['display_name'],
          'eventTitle' => $participant['event'][0]['title']
        ];
      }
    }
  }
  
  private function _buildSessionOptions() {
    $ret = [];
    $participant = \Civi\Api4\Participant::get()
      ->addSelect('event_id')
      ->addWhere('id', '=', $this->_validatedValues['p'])
      ->execute()
      ->first();
    $sessionGroups = \Civi\Api4\AnoncheckinSessionGroup::get()
      ->addSelect('id', 'title')
      ->addOrderBy('start_datetime_utc')
      ->addWhere('event_id', '=', $participant['event_id'])
      ->addChain('session', \Civi\Api4\AnoncheckinSession::get()
        ->addOrderBy('title')
        ->addWhere('session_group_id', '=', '$id')
      )
      ->execute();

    foreach ($sessionGroups as $sessionGroup) {
      $groupElement = [
        'groupId' => $sessionGroup['id'],
        'groupTitle' => $sessionGroup['title'],
        'radioOptions' => [],
      ];
      foreach ($sessionGroup['session'] as $session) {
        $groupElement['radioOptions'][$session['id']] = $session['title'];
      }
      $ret[] = $groupElement;
    }
    
    foreach ($ret as &$sessionOption) {
      $sessionOption['radioOptions']['0'] = '- None -';
    }
    return $ret;
  }  
  
}
