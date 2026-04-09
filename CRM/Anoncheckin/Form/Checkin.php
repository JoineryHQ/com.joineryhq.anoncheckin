<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Anoncheckin_Form_Checkin extends CRM_Core_Form {

  private $sid;
  private $pid;
  
  /**
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {

    $this->add('hidden', 'sid', $this->sid);
    $this->addButtons([
      [
        'type' => 'done',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function preProcess(): void {

    // fixme: if sid is already recorded for pid, say so and redirect to Status page.
    $storage = CRM_Anoncheckin_Utils_Session::singleton();

    $sid = CRM_Utils_Request::retrieve('sid', 'String');
    if ($sid) {
      $this->sid = $sid;
      $storage->set('sid', $this->sid);
      $this->assign('sid', $this->sid);
      // fixme: null $eventId, should be derived from pid.
      $this->assign('sessionName', CRM_Anoncheckin_Utils_Data::getSessionOptions($eventId)[$sid]);
    }
    
    // Get stored pid, if any.
    $this->pid = $storage->get('pid');
    if ($this->pid) {
      $this->assign('pid', $this->pid);
      $this->assign('participantName', 'Marcus Brown');
    }
    else {
      // unknown user, so:
      //  - Instruct user to scan badge (badge url will store their pid and -- if they have a (not yet expired) stored sid, redirect them here.
      $this->assign('countdownMinutes', $storage->getExpiryMinutes('sid'));
    }
    
    $statusUrl = CRM_Utils_System::url('civicrm/anoncheckin/status', ['pid' => $this->pid]);
    CRM_Core_Session::singleton()->pushUserContext($statusUrl);
    
  }
  
  public function postProcess(): void {
    $storage = CRM_Anoncheckin_Utils_Session::singleton();

    $submitValues = $this->getSubmitValues();
    // fixme: sid needs tamper-proofing
    CRM_Anoncheckin_Utils_Data::recordParticipantSession($this->pid, $submitValues['sid']);

    $sessionName = CRM_Anoncheckin_Utils_Data::getSessionOptions($eventId)[$submitValues['sid']];
    
    $message = E::ts('Your attendance at "%1" has been recorded.', ['1' => $sessionName]);
    CRM_Core_Session::setStatus($message, E::ts('Success'), 'alert', ['expires' => 0]);

    // fixme: major schema question: what if the users changes his mind for the 10am session? ...
    // first he checks into session A, then moves to another room and checks into session B.
    // Should we remove session A and replace it with session B? Or, allow both and sort it out later? Or refuse B because A already exists?
    
    // clear sid from storage (they'll have to scan again)
    $storage->set('sid', NULL);    
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

}
