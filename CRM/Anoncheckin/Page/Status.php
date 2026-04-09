<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

class CRM_Anoncheckin_Page_Status extends CRM_Core_Page {

  public function run() {
    $storage = CRM_Anoncheckin_Utils_Session::singleton();
    if (CRM_Utils_Request::retrieve('reset', 'Int')) {
      $storage->reset();
    }
    
    $pid = CRM_Utils_Request::retrieve('pid', 'Int');
    if ($pid) {
      // If pid is given, store it in session.
      $storage->set('pid', $pid);
    }
    // fixme: if pid is not given, we should NOT use session pid; we should error.
    
    // if sid is in session (and not expired), redirect to checkin.
    if ($sid = $storage->get('sid')) {
      $checkinUrl = CRM_Utils_System::url('/civicrm/anoncheckin/checkin', ['sid' => $sid]);
      CRM_Utils_System::redirect($checkinUrl);
    }

    // use the stored pid.
    $storedPid = $storage->get('pid');
    $this->assign('pid', $storedPid);

    // fixme: dummy name
    $this->assign('display_name', 'Marcus Brown');
    
    $participantSessions = CRM_Anoncheckin_Utils_Data::getParticipantSessions($pid);
    if (!empty($participantSessions)) {
      // fixme: null $eventId, should be based on $pid.
      $sessionOptions = CRM_Anoncheckin_Utils_Data::getSessionOptions($eventId);
      $sessions = array_intersect_key($sessionOptions, array_flip($participantSessions));
      $this->assign('sessions', $sessions);
    }
   
    CRM_Core_Resources::singleton()->addStyleFile('com.joineryhq.anoncheckin', 'css/scanner.css');
    CRM_Core_Resources::singleton()->addScriptUrl('https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js');
    
    
    // Define a set of valid qr-code urls, which we'll pass to JS.
    $validQrBaseUrls = [];
    if ($storedPid) {
      // We know the pid, so we're prompting for a session qr code:
      $validQrBaseUrls[] = CRM_Utils_System::url('civicrm/anoncheckin/checkin');
    }
    else {
      // We DON'T know the pid, so we're prompting for a badge qr code:
      $validQrBaseUrls[] = CRM_Utils_System::url('civicrm/anoncheckin/status');
    }
    $jsSettings = [
      'validQrBaseUrls' => $validQrBaseUrls,
      ];
    CRM_Core_Resources::singleton()->addVars('anoncheckin', $jsSettings);
    parent::run();
  }

}
