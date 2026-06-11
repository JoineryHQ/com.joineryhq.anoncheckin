<?php

/**
 * Single-page app display and processing.
 */
class CRM_Anoncheckin_Extern_App {

  var $device = [];
  var $participant = [];
  var $participantSessions = [];
  var $debugMessages = [];
  var $debug = FALSE;
  var $appUrl = '';

  public function __construct() {
    // validate all input (value vs hmac sig).
    $this->validateInput();

    $this->appUrl = CRM_Anoncheckin_Utils_Extern::getAppUrl();
    $this->debug = Civi::settings()->get('debug_enabled');
    
    $deviceKey = CRM_Anoncheckin_Utils_Extern::getUserDeviceKey();
    if ($deviceKey) {
      // If user's device has been initialized, populate $this->device.
      $this->device = CRM_Anoncheckin_Utils_ExternData::selectDeviceByKey($deviceKey);
    }
    if (empty($this->device)) {
      // If $this->device is still empty, then we're in one of two situatios:
      // - user-device has never visited before and has no cookie; OR
      // - user-device has a cookie, but a corresponding deviceKey no longer
      // exists in the DB. 
      // Either way, we need to (re-)initialize this device.
      $this->device = CRM_Anoncheckin_Utils_Extern::initializeDevice();      
    }

    if ($this->device['participantId']) {
      $this->participant = CRM_Anoncheckin_Utils_ExternData::selectParticipantInfo($this->device['participantId']);
      $this->participantSessions = CRM_Anoncheckin_Utils_ExternData::selectParticipantSessions($this->device['participantId']);
    }
    
  }

  public function run() {

    // Pass all input vers to template.
    foreach ($_REQUEST as $requestKey => $requestValue) {
      $this->assign($requestKey, $requestValue);
      $this->setDebugMessage("Set tpl value from REQEST: $requestKey = $requestValue");
    }
    
    $this->assign('appUrl', $this->appUrl);

    if ($this->device['deviceStatusId'] == CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_INVALIDATED) {
      $this->fatal('There is a problem verifying your identity. Please see a staff member for assistance.');
    }
    elseif ($this->device['deviceStatusId'] == CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_CLOSED) {
      $this->device = [];
    }

    $this->assign('participantName', ($this->participant['displayName'] ?? NULL));
    $deviceIsLocked = (bool)$this->getDeviceLockedPid();
    $this->assign('deviceIsLocked', $deviceIsLocked);
    $this->assign('isDebug', $this->debug);
    $this->assign('participantSessions', $this->participantSessions);


    // Run the appropriate action.
    if ($_REQUEST['a']) {
      $actionFunctionName = 'action_' . $_REQUEST['a'];
    }
    else {
      $actionFunctionName = "action_default";
      foreach (['s', 'p'] as $actionKey) {
        if ($_REQUEST[$actionKey] ?? '') {
          $actionValue = $_REQUEST[$actionKey];
          $method = strtolower($_SERVER['REQUEST_METHOD']);
          $actionFunctionName = "action_{$method}_{$actionKey}";
          break;
        }
      }
    }

    if (!empty($actionFunctionName) && is_callable([$this, $actionFunctionName])) {
      $this->$actionFunctionName($actionValue);
    }
    else {
      $this->fatal("Invalid action, attemped: ". $actionFunctionName);
    }

    $this->assign('action', $actionFunctionName);
    
    $this->print();
  }

  /**
   * Action: Fallback. E.g. app url loaded with no query params
   */
  private function action_default() {
    // At present we do nothing here.
    $this->setDebugMessage('No action given, using default action.');
  }

  private function action_change_p() {
    $this->setDebugMessage('Action: '. __FUNCTION__);
    $this->setDebugMessage('State: Requested to change p.');

    // is this device locked?
    $lockedPid = $this->getDeviceLockedPid();
    $lockedParticipant = CRM_Anoncheckin_Utils_ExternData::selectParticipantInfo($lockedPid);
    if ($lockedPid) {
      // device is locked to some other pid.
      $this->setDebugMessage("Device is already locked to a different participant: ". var_export($this->device, 1));
      $this->fatalLocked($lockedParticipant['displayName']);
    }

    // If we're still here, it's a little strange, because the action was "change my participant id / i.e. unlock my device"),
    // which should only happen if the device is locked.
    // So, just redirect to clean app.
    $this->redirectClean();
  }
  
  private function action_staff_info() {
    // For staff info, we should show the current device as a QR code and as a table.
    $this->assign('device', $this->device);
    $deviceQrUrl = CRM_Anoncheckin_Utils_Qr::getQrImageUrl('app://staff.info?'. http_build_query($this->device));
    $this->assign('deviceQrUrl', $deviceQrUrl);
    $this->assign('isStaffInfo', TRUE);
    
  }
  

  /**
   * Action: User has scanned a badge; prompt user for lock-in.
   */
  private function action_get_p($p) {
    $this->setDebugMessage('Action: '. __FUNCTION__);
    $this->setDebugMessage('State: Scanned a badge.');

    // is this device locked?
    $lockedPid = $this->getDeviceLockedPid();
    if ($lockedPid) {
      if ($lockedPid == $p) {
        // Device is locked to user (great!)
        // Redirect to clean app.
        $this->redirectClean();
      }
      else {
        // device is locked to some other pid.
        $this->setDebugMessage("Device is already locked to a different participant: ". var_export($this->device, 1));
        $lockedParticipant = CRM_Anoncheckin_Utils_ExternData::selectParticipantInfo($lockedPid);
        $this->fatalLocked($lockedParticipant['displayName']);
      }
    }
    else {
      // We'll need the badge participant info soon.
      $badgeParticipant = CRM_Anoncheckin_Utils_ExternData::selectParticipantInfo($p);
      // This device is not locked.
      // But is this badge locked to someone other device?
      $deviceLockedToPid = CRM_Anoncheckin_Utils_ExternData::selectLockedDeviceByPid($p);
      if (!empty($deviceLockedToPid)) {
        $this->fatal("The badge for \"<strong>{$badgeParticipant['displayName']}</strong>\" has been locked by another device ({$deviceLockedToPid['userAgentShort']}). To record sessions on <em>this</em> device, please see a staff member for assistance.");
      }
      // If we're still here, user has an unlocked device, and their badge is also not locked elsewhere.
      $this->assign('participantName', $badgeParticipant['displayName']);
      $this->setDebugMessage(__FUNCTION__ . ': assign participantName = '. $badgeParticipant['displayName']);
    }
  }

  /**
   * Action: User is locking device to badge.
   */
  private function action_post_p($p) {
    $this->setDebugMessage('Action: '. __FUNCTION__);
    $this->setDebugMessage('State: Requested to lock device to badge.');

    // is this device locked to some other pid?
    if ($this->getDeviceLockedPid($p)) {
      $this->setDebugMessage("Device is already locked to a different participant: ". var_export($device, 1));
      // fixme: this is a fatal.
      return;
    }


    // Lock device to badge.
    $this->device = CRM_Anoncheckin_Utils_Device::lockDeviceToParticipant($this->device, $p);
    $this->setDebugMessage("Device locked: ". var_export($this->sdevice, 1));

    $this->participant = CRM_Anoncheckin_Utils_ExternData::selectParticipantInfo($p);
    $this->setUserMessage("Your device has now been locked to the badge named: {$this->participant['displayName']}", 'success');

    // Redirect to clean app.
    $this->redirectClean();
  }

  /**
   * Action: User has scanned a session code; prompt user to confirm session.
   */
  private function action_get_s($s) {
    // device is locked to a badge? if not, they gotta do that first: message and redirectClean.
    if (empty($this->getDeviceLockedPid())) {
      $this->setUserMessage('Please scan your badge first to establish your identity.', 'info');
      $this->redirectClean();      
    }
    // session exists in the same event as participant? If not, tell them that session's not available: message and redirectClean.
    $session = CRM_Anoncheckin_Utils_ExternData::selectSessionInfo($s);
    if ($session['eventId'] != $this->participant['eventId']) {
      $this->setUserMessage("The session you have selected (<strong>{$session['title']}</strong>) is not available for attendance recording.", 'error');
      $this->redirectClean();
    }
    
    // session start/end times are within allowed window? If not, tell them that session's not available: message and redirectClean.
    if (!CRM_Anoncheckin_Utils_Extern::sessionTimeIsValidNow($session)) {
      $this->setUserMessage("The session you have selected (<strong>{$session['title']}</strong>) is not available for attendance recording.", 'error');
      $this->redirectClean();      
    }
    
    // Compare this session to existing participant sessions.
    $session = CRM_Anoncheckin_Utils_ExternData::selectSessionInfo($s);
    foreach ($this->participantSessions as $participantSession) {
      if ($participantSession['sessionId'] == $s) {
        // Already recorded this session.
        $this->setUserMessage("You've already recorded this session (<strong>{$session['title']}</strong>).", 'success');
        $this->redirectClean();      
      }
      if ($participantSession['sessionGroupId'] == $session['sessionGroupId']) {
        // Is this participant already recorded another session in the same group.
        // That's not allowed. Tell them their other session will be replaced.
        $this->fatal('fixme: this should not be fatal: participant already recored another session in this group.');
      }
    }

    // Display session title and ask "are you sure?"
    $this->assign('sessionTitle', $session['title']);    
  }

  /**
   * Action: User has confirmed a session; record attendance.
   */
  private function action_post_s($s) {
    // device is locked to a badge? if not, they gotta do that first: message and redirectClean.
    if (empty($this->getDeviceLockedPid())) {
      $this->setUserMessage('Please scan your badge first to establish your identity.', 'info');
      $this->redirectClean();      
    }
    CRM_Anoncheckin_Utils_ExternData::insertSessionOnDevice($s, $this->device);
  }
  
  private function validateInput() {
    foreach (['s', 'p'] as $varName) {
      if (!empty($_REQUEST[$varName]) && !CRM_Anoncheckin_Utils_Value::validateValue($_REQUEST[$varName], $_REQUEST["{$varName}h"])) {
        $this->fatal('Invalid input: '. $varName);
      }
    }

  }
  private function fatal($message) {
    // fixme: stub
    $this->assign('isFatal', TRUE);
    $this->setUserMessage($message, 'error');
    $this->print();
  }

  private function fatalLocked($participantName) {
    $this->fatal("Your device is locked to the badge for <strong>$participantName</strong>. If that's incorrect, please see a staff member for help.");
  }

  private function print() {
    $tpl = CRM_Core_Smarty::singleton();
    $messages = CRM_Anoncheckin_Utils_Session::singleton()->consumeMessages();
    $config = CRM_Core_Config::singleton();
    $tpl->assign('userFrameworkResourceURL', $config->userFrameworkResourceURL);
    $tpl->assign('messages', $messages);
    if ($this->debug) {
      $tpl->assign('debugMessages', $this->debugMessages);
    }
    $tpl->display($this->getTemplate());
    exit();
  }

  private function getTemplate() {
    $extPath = \Civi::resources()->getPath('com.joineryhq.anoncheckin');
    $ret = $extPath . '/templates/CRM/Anoncheckin/Extern/App.tpl';
    return $ret;
  }

  private function assign($name, $value) {
    CRM_Core_Smarty::singleton()->assign($name, $value);
  }

  private function setDebugMessage($message) {
    $this->debugMessages[] = $message;
  }

  private function setUserMessage($message, $type) {
    CRM_Anoncheckin_Utils_Session::singleton()->setMessage($message, $type);
  }

  private function getDeviceLockedPid() {
    $ret = NULL;
    if ($this->device['deviceStatusId'] == CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_LOCKED) {
      $ret = ($this->device['participantId'] ?? NULL);
    }
    return $ret;
  }

  private function redirectClean() {
    header('Location: '. $this->appUrl);
    exit();
  }
}
