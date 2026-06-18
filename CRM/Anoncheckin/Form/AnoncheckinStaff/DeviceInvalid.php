<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Anoncheckin_Form_AnoncheckinStaff_DeviceInvalid extends CRM_Anoncheckin_Form_AnoncheckinStaff {

  // This form uses its own set of sessionSuggestions.
  var $_sessionSuggestions = [];

  public function __construct() {
    $this->_dataTypes = ['badge', 'device'];
    $this->_allowedDeviceStatuses[] = CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_INVALIDATED;
    parent::__construct();
  }
  /**
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    parent::_preBuildQuickForm();
    parent::buildQuickForm();
  }

  public function postProcess(): void {
    $values = $this->exportValues();
    $p = (int) $values['p'];    

    // Close the device.
    $this->_closeDevice($values['deviceKey']);

    // Invalidate any devices locked to badge participant.
    $updateCount = $this->_invalidateDevicesForParticipant($p);
    
    if ($updateCount) {
      $statusMessage = E::ts('%1 device(s) that were locked for %2 have been invalidated.',[
        1 => $updateCount,
        2 => $this->_userVars['badge']['displayName'],
      ]);
      CRM_Core_Session::singleton()->setStatus($statusMessage, 'Devices invalidated.', 'success no-popup');
    }
      
    CRM_Core_Session::singleton()->setStatus(E::ts('Please ask the participant to re-scan their badge on their device.'), 'Action required.', 'alert no-popup');
    parent::postProcess();    
  }
  
  protected function _processValues() {
    $deviceKey = CRM_Utils_Request::retrieve('deviceKey', 'String');
    if ($deviceKey) {
      $deviceSessions = [];
      // Build a list of sessions scanned on this device.
      $deviceGet = \Civi\Api4\AnoncheckinDevice::get()
        ->addWhere('device_key', '=', $deviceKey)
        ->addChain('session_participant', \Civi\Api4\AnoncheckinSessionParticipant::get()
          ->addWhere('device_id', '=', '$id')
          ->addChain('session', \Civi\Api4\AnoncheckinSession::get()
            ->addWhere('id', '=', '$session_id')
          )
        )
        ->execute();
      foreach ($deviceGet[0]['session_participant'] as $sessionParticipant) {
        $sessionTitle = $sessionParticipant['session'][0]['title'];
        $deviceSessions[] = $sessionTitle;
        $this->_sessionSuggestions[] = $sessionParticipant['session_id'];
      }
      $this->_userVars['deviceSessions'] = $deviceSessions;
    }
    parent::_processValues();
  }

  /** 
   * Override parent::_getSessionSuggestions because we DO NOT want to suggest
   * sessions based on the device participant, because this device is invalid, 
   * and so the device owner is almost surely not the same person as the device
   * participant.
   * Instead, we want to suggest sessions only from this device.
   * Therefore, we'll completely ignore the $participantIds param return
   * $this->_sessionSuggestions, which was built in own own (also overridden)
   * _processValues().
   * 
   * @param array $participantIds NOT USED, see above.
   * @return array
   */
  protected function _getSessionSuggestions(array $participantIds): array {
    return $this->_sessionSuggestions;
  }
  
}
