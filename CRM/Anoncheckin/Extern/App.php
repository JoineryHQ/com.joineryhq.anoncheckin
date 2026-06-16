<?php

/**
 * Single-page app display and processing.
 */
class CRM_Anoncheckin_Extern_App {

  const SESSION_PREFIX = 'anoncheckin_extern';

  var $device = [];
  var $participant = [];
  var $participantSessions = [];
  var $debugMessages = [];
  var $debug = FALSE;
  var $appUrl = '';
  
  /**
   * @var CRM_Core_session Instance of CRM_Core_Session, to be scoped for our own dedicated usage.
   */
  var $_session;

  public function __construct() {
    // validate all input (value vs hmac sig).
    $this->validateInput();

    // Create context for app session vars.
    $this->_session = CRM_Core_Session::singleton();
    $this->_session->createScope(self::SESSION_PREFIX);

    $this->appUrl = CRM_Anoncheckin_Utils_Extern::getAppUrl();
    $setting = CRM_Anoncheckin_Setting::singleton();
    $this->debug = $setting->get('anoncheckin_debug');
    
    $deviceKey = CRM_Anoncheckin_Utils_Extern::getUserDeviceKey();
    if ($deviceKey) {
      // If user's device has been initialized, populate $this->device.
      $this->device = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectDeviceByKey', $deviceKey);
    }
    if (empty($this->device)) {
      // If $this->device is still empty, then we're in one of two situatios:
      // - user-device has never visited before and has no cookie; OR
      // - user-device has a cookie, but a corresponding deviceKey no longer
      // exists in the DB. 
      // Either way, we need to (re-)initialize this device.
      $this->device = CRM_Anoncheckin_Utils_Extern::initializeDevice();      
    }

    if ($this->device['participantId'] ?? FALSE) {
      $this->participant = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantInfo', $this->device['participantId']);
      $this->participantSessions = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantSessions', $this->device['participantId']);
    }
    
  }

  public function run() {

    // Pass all input vers to template.
    foreach ($_REQUEST as $requestKey => $requestValue) {
      $this->assign($requestKey, $requestValue);
      $this->setDebugMessage("Set tpl value from REQEST: $requestKey = $requestValue");
    }
    
    $this->assign('appUrl', $this->appUrl);

    // Determine the appropriate action.
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

    if (
      $this->device['deviceStatusId'] == CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_INVALIDATED 
      && $actionFunctionName != 'action_staff_info'
    ) {
      // If device is invalid (and we're not just viewing staff info), fatal with message.
      $this->fatal('There is a problem verifying your identity. Please see a staff member for assistance.');
    }

    $this->assign('participantName', ($this->participant['displayName'] ?? NULL));
    $this->assign('participantId', ($this->participant['participantId'] ?? NULL));
    $deviceIsLocked = (bool)$this->getDeviceLockedPid();
    $this->assign('deviceIsLocked', $deviceIsLocked);
    $this->assign('isDebug', $this->debug);
    $this->assign('participantSessions', $this->participantSessions);

    // Perform called-for action, if it exists.
    if (!empty($actionFunctionName) && is_callable([$this, $actionFunctionName])) {
      $this->$actionFunctionName($actionValue);
      // If method was POST, redirect to clean app.
      if ($method == 'post') {
        $this->redirectClean();
      }
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
    if ($lockedPid) {
      // device is locked to some other pid.
      $this->fatalLocked();
    }

    // If we're still here, it's a little strange, because the action was "change my participant id / i.e. unlock my device"),
    // which should only happen if the device is locked.
    // So, just redirect to clean app.
    $this->redirectClean();
  }
  
  private function action_staff_info() {
    // For staff info, we should show the current device as a QR code and as a table.
    $deviceInfo = $this->device;
    // Add deviceStatus label to $deviceInfo.
    $optionValues = CRM_Core_OptionGroup::values('anoncheckin_device_status');
    $deviceInfo['deviceStatus'] = $optionValues[$deviceInfo['deviceStatusId']];
    $deviceInfo['participantName'] = $this->participant['displayName'];
    $this->assign('device', $deviceInfo);
    
    $deviceQrUrl = CRM_Anoncheckin_Utils_Qr::getQrImageUrl($this->device['deviceKey']);
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
        $this->fatalLocked();
      }
    }
    else {
      // This device is not locked.
      // We'll need the badge participant info soon.
      $badgeParticipant = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantInfo', $p);
      if ($badgeParticipant === NULL) {
        $this->fatal('This badge does not appear to be valid.');
      }
      // Is this badge locked to someone other device?
      $deviceLockedToPid = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectLockedDeviceByPid', $p);
      if (!empty($deviceLockedToPid)) {
        $this->fatal("The badge for <strong>{$badgeParticipant['displayName']}</strong> has been locked by another device ({$deviceLockedToPid['userAgentShort']}).<br/>To record sessions for {$badgeParticipant['displayName']} on <em>this</em> device, please see a staff member for assistance.");
      }
      // If we're still here, user has an unlocked device, and their badge is also not locked elsewhere.
      $this->assign('participantEventTitle', $badgeParticipant['eventTitle']);
      $this->assign('participantName', $badgeParticipant['displayName']);
      $this->assign('participantId', $p);
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
      $this->fatalLocked();
    }

    // Lock device to badge.
    if ($this->device = CRM_Anoncheckin_Utils_Device::lockDeviceToParticipant($this->device, $p)) {
      $this->participant = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantInfo', $p);
      $this->setMessage("Your device has now been locked to the badge for <strong>{$this->participant['displayName']}<strong>", 'success');
    }
    else {
      $this->fatal('There was a problem locking your device to this badge. Please try again.');
    }
    // Redirect to clean app.
    $this->redirectClean();
  }

  /**
   * Action: User has scanned a session code; prompt user to confirm session.
   */
  private function action_get_s($s) {
    // device is locked to a badge? if not, they gotta do that first: message and redirectClean.
    if (empty($this->getDeviceLockedPid())) {
      $this->setMessage('Please scan your badge first to establish your identity.', 'info');
      $this->redirectClean();      
    }
    // session exists in the same event as participant? If not, tell them that session's not available: message and redirectClean.
    $session = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectSessionInfo', $s);
    if ($session['eventId'] != $this->participant['eventId']) {
      $this->setMessage("The session you have selected (<strong>{$session['title']}</strong>) is not available for attendance recording.", 'error');
      $this->redirectClean();
    }
    
    // session start/end times are within allowed window? If not, tell them that session's not available: message and redirectClean.
    if (!CRM_Anoncheckin_Utils_Extern::sessionTimeIsValidNow($session)) {
      $this->setMessage("The session you have selected (<strong>{$session['title']}</strong>) is not available for attendance recording.", 'error');
      $this->redirectClean();      
    }
    
    // Compare this session to existing participant sessions.
    $sessionCompare = $this->compareSessionWithExisting($session);
    switch ($sessionCompare) {
      case 0:
        // No duplicates or conflicts; nothing special to do here.
        break;
      case -1:
        // Already recorded this session. We won't do anything here.
        $this->setMessage("You've already recorded this session (<strong>{$session['title']}</strong>).", 'success');
        $this->redirectClean();      
        break;
      default:
        // Anything besides 0 (NONE) and -1 (DUPLICATE) represents a group-based CONFLICT.
        // This participant already recorded another session in the same group.
        // That's not allowed. Tell them their other session will be replaced.
        $sessionParticipantId = $sessionCompare;        
        foreach($this->participantSessions as $participantSession) {
          if ($participantSession['sessionParticipantId'] == $sessionParticipantId) {
            $conflictingParticipantSession = $participantSession;
            break;
          }
        }
        $this->assign('sessionOverwriteWarning', ['oldTitle' => $conflictingParticipantSession['title'], 'newTitle' => $session['title']]);
        break;
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
      $this->setMessage('Please scan your badge first to establish your identity.', 'info');
      $this->redirectClean();      
    }
    
    // Will we actually record this session?
    $doRecordSession = FALSE;
    $session = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectSessionInfo', $s);
    $sessionCompare = $this->compareSessionWithExisting($session);
    switch ($sessionCompare) {
      case 0:
        // No duplicates or conflicts; we'll record.
        $doRecordSession = TRUE;
        break;
      case -1:
        // Session already recorded. Just tell them it's recorded, and redirect to clean app.
        $this->setMessage("You've been marked as attending session \"<strong>{$session['title']}</strong>\".", 'success');
        $this->redirectClean();
        break;
      default:
        // Anything besides 0 (NONE) and -1 (DUPLICATE) represents a group-based CONFLICT.
        // This participant already recorded another session in the same group.
        // But since this is POST, they've also confirmed that they want to replace this.
        // So, we'll delete the old one and save this one.
        $sessionParticipantId = $sessionCompare;
        CRM_Anoncheckin_Utils_ExternData::deleteSessionParticipant($sessionParticipantId);
        $doRecordSession = TRUE;
        break;
    }
    
    if ($doRecordSession) {
      CRM_Anoncheckin_Utils_ExternData::insertSessionOnDevice($s, $this->device);
      $this->setMessage("You've been marked as attending session \"<strong>{$session['title']}</strong>\".", 'success');
    }
  }
  
  private function validateInput() {
    foreach (['s', 'p'] as $varName) {
      if (!empty($_REQUEST[$varName]) && !CRM_Anoncheckin_Utils_Value::validateValue($_REQUEST[$varName], $_REQUEST["{$varName}h"])) {
        $this->fatal('Invalid input: '. $varName);
      }
    }

  }
  private function fatal($message) {
    $this->assign('isFatal', TRUE);
    $this->setMessage($message, 'error');
    if (!empty($this->device['deviceId'])) {
      // In odd circumstances, there may be no device, so only log if we have one.
      CRM_Anoncheckin_Utils_ExternData::insertDeviceLog($this->device['deviceId'], CRM_Anoncheckin_Utils_Extern::DEVICE_LOG_TYPE_USER, $message);
    }
    $this->print();
  }

  private function fatalLocked() {
    $participantName = $this->participant['displayName'];
    $this->fatal("Your device is locked to the badge for <strong>$participantName</strong>. If that's incorrect, please see a staff member for help.");
  }

  /**
   * Given a certain session, determine whether there's conflict/redundancy in
   * in this participant's existing sessions.
   * 
   * @param Array $session Session properties as returned by CRM_Anoncheckin_Utils_ExternData::selectSessionInfo()
   * @return Int One of the following:
   *   [sessonId]: CONFLICT: If participant has recorded a different session in
   *    the same group, return the sessionParticipantId (civicrm_anoncheckin_session_participant.id) 
   *    of that already-recorded session.
   *   -1: DUPLICATE: Participant has already recorded this session.
   *   0: NONE: None of the above is true (i.e. we can record this session).
   */
  private function compareSessionWithExisting(array $session): int{
    if (empty($this->participantSessions)) {
      $this->participantSessions = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantSessions', $this->device['participantId']);
    }
    foreach ($this->participantSessions as $participantSession) {
      if ($participantSession['sessionId'] == $session['sessionId']) {
        // Already recorded this session.
        return -1;
      }
      if ($participantSession['sessionGroupId'] == $session['sessionGroupId']) {
        // Already recorded another session in the same sessionGroup.
        return $participantSession['sessionParticipantId'];
      }
    }
    // If we're still here, there were no CONFLICTS or DUPLICATES. Return NONE.
    return 0;
  }

  private function print() {
    $tpl = CRM_Core_Smarty::singleton();
    $tpl->assign('messages', $this->consumeMessages());
    if ($this->debug) {
      $tpl->assign('debugMessages', $this->debugMessages);
    }
    
    $setting = CRM_Anoncheckin_Setting::singleton();
    $extensionBasePath = $setting->get('extensionBasePath');
    $tpl->assign('extensionBasePath', $extensionBasePath);

    // Embed CSS styles. We will embed CSS as literal code, rather than using 
    // separate CSS URLs, in order to minimize the number of http requests on
    // the page.
    $cssFilePaths = [
      $extensionBasePath . '/css/qrScanner.css',
    ];
    $cssContent = '';
    foreach ($cssFilePaths as $cssFilePath) {
      $cssContent .= '<style>' . file_get_contents($cssFilePath). '</style>';
    }
    $tpl->assign('cssContent', $cssContent);

    // Embed Javascript. We will embed JS as literal code, rather than using 
    // separate Script URLs, in order to minimize the number of http requests on
    // the page.
    $jsFilePaths = [
      $extensionBasePath . '/js/qrScanner.js',
    ];
    $jsContent = '';
    foreach ($jsFilePaths as $jsFilePath) {
      $jsContent .= '<script>' . file_get_contents($jsFilePath). '</script>';
    }
    $tpl->assign('jsContent', $jsContent);

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
  
  /**
   * Add a user-facing status message for display.
   * @param String $message The message body.
   * @param String $type One of: info, error, success
   */
  public function setMessage($message, $type = 'info') {
    $messages = $this->_session->get('messages', self::SESSION_PREFIX) ?? [];
    $messages[] = [
      'type' => $type,
      'message' => $message,
    ];
    $this->_session->set('messages', $messages, self::SESSION_PREFIX);
  }

  public function consumeMessages() {
    $messages = $this->_session->get('messages', self::SESSION_PREFIX) ?? [];
    $this->_session->set('messages', [], self::SESSION_PREFIX);
    return $messages;
  }
  
}
