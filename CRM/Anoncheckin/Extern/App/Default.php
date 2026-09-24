<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Single-page app display and processing.
 */
class CRM_Anoncheckin_Extern_App_Default extends CRM_Anoncheckin_Extern_App {

  var $participantSessions = [];

  function run() {
    $this->addCssFile('css/Extern/App/Default.css');
    $this->addCssFile('css/qrScanner.css');
    $this->addJsFile('js/qrScanner.js');
    $this->addJsFile('js/Extern/App/Default.js');
    $this->addJsUrl('https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js');

    // validate all input (value vs hmac sig).
    $this->validateInput();

    if ($this->device['participantId'] ?? FALSE) {
      $this->participantSessions = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantSessions', $this->device['participantId']);
    }
    
    // Determine the appropriate action.
    $actionFunctionName = "action_default";
    foreach (['s', 'p'] as $actionKey) {
      if ($_REQUEST[$actionKey] ?? '') {
        $actionValue = $_REQUEST[$actionKey];
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $actionFunctionName = "action_{$method}_{$actionKey}";
        break;
      }
    }

    if (
      $this->device['deviceStatusId'] == CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_INVALIDATED && $actionFunctionName != 'action_staff_info'
    ) {
      // If device is invalid (and we're not just viewing staff info), fatal with message.
      $this->fatal('There is a problem verifying your identity. Please see a staff member for assistance.');
    }

    $this->assign('participantName', ($this->participant['displayName'] ?? NULL));
    $this->assign('participantId', ($this->participant['participantId'] ?? NULL));
    $deviceIsLocked = (bool) $this->getDeviceLockedPid();
    $this->assign('deviceIsLocked', $deviceIsLocked);
    $this->assign('participantSessions', $this->participantSessions);

    // Perform called-for action, if it exists.
    if (!empty($actionFunctionName) && is_callable([$this, $actionFunctionName])) {
      $this->$actionFunctionName($actionValue);
      // If method was POST, redirect to clean app.
      if ($method == 'post') {
        $this->redirectClean();
      }
    }

    $this->assign('action', $actionFunctionName);

    parent::run();
  }

  /**
   * Action: Fallback. E.g. app url loaded with no query params
   */
  private function action_default() {
    // At present we do nothing here.
    $this->setDebugMessage('No action given, using default action.');
  }

  /**
   * Action: User has scanned a badge; prompt user for lock-in.
   */
  private function action_get_p($p) {
    $this->setDebugMessage('Action: ' . __FUNCTION__);
    $this->setDebugMessage('State: Scanned a badge.');

    // is this device locked?
    $lockedPid = $this->getDeviceLockedPid();
    if ($lockedPid) {
      if ($lockedPid == $p) {
        // Device is locked to user (great!)
        // Redirect to clean app.
        $this->redirectClean();
      } else {
        // device is locked to some other pid.
        $this->fatalLocked();
      }
    } else {
      // This device is not locked.
      // We'll need the badge participant info soon.
      $badgeParticipant = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantInfo', $p);
      if ($badgeParticipant === NULL) {
        $this->fatal('This badge does not appear to be valid.');
      }
      // Is this badge locked to someone other device?
      $deviceLockedToPid = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectLockedDeviceByPid', $p);
      if (!empty($deviceLockedToPid)) {
        $recoveryOptions = [
          E::ts('Please see a staff member for assistance.'),
        ];
        if ($this->isSelfUnlockSupported()) {
          array_unshift($recoveryOptions, E::ts('<a href="%1">Click here to unlock your badge via email</a>; OR', [
            '1' => CRM_Anoncheckin_Utils_Extern::getAppUrl(['a' => 'self_unlock', 'p' => $p, 'ph' => CRM_Anoncheckin_Utils_Value::generateSignature($p)]),
          ]));
        }
        $recoveryOptionsList = '<ul>';
        foreach ($recoveryOptions as $recoveryOption) {
          $recoveryOptionsList .= "<li>{$recoveryOption}</li>";
        }
        $recoveryOptionsList .= '</ul>';
        $fatalMessage = E::ts("The badge for <strong>%1</strong> has been locked by another device (%2). <br/>To record sessions for %1 on <em>this</em> device: %3", [
          '1' => $badgeParticipant['displayName'],
          '2' => $deviceLockedToPid['userAgentShort'],
          '3' => $recoveryOptionsList,
        ]);
        $this->fatal($fatalMessage);
      }
      // If we're still here, user has an unlocked device, and their badge is also not locked elsewhere.
      $this->assign('participantEventTitle', $badgeParticipant['eventTitle']);
      $this->assign('participantName', $badgeParticipant['displayName']);
      $this->assign('participantId', $p);
      $this->setDebugMessage(__FUNCTION__ . ': assign participantName = ' . $badgeParticipant['displayName']);
    }
  }

  /**
   * Action: User is locking device to badge.
   */
  private function action_post_p($p) {
    $this->setDebugMessage('Action: ' . __FUNCTION__);
    $this->setDebugMessage('State: Requested to lock device to badge.');

    // is this device locked to some other pid?
    if ($this->getDeviceLockedPid($p)) {
      $this->fatalLocked();
    }

    // Lock device to badge.
    if ($this->device = CRM_Anoncheckin_Utils_Device::extern_lockDeviceToParticipant($this->device, $p)) {
      $this->participant = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantInfo', $p);
      $this->setMessage("Your device is now locked to the badge for <strong>{$this->participant['displayName']}<strong>", 'success');
    } else {
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
    if (!self::sessionTimeIsValidNow($session)) {
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
        foreach ($this->participantSessions as $participantSession) {
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
        $this->fatal('Invalid input: ' . $varName);
      }
    }
  }

  private function fatalLocked() {
    $participantName = $this->participant['displayName'];
    $this->fatal(E::ts("Your device is locked to the badge for <strong>%1</strong>. If that's incorrect, please see a staff member for help.", [1 => $participantName]));
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
  private function compareSessionWithExisting(array $session): int {
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

  private function getDeviceLockedPid() {
    $ret = NULL;
    if ($this->device['deviceStatusId'] == CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_LOCKED) {
      $ret = ($this->device['participantId'] ?? NULL);
    }
    return $ret;
  }

  /**
   * Is this session available for recording, in light of the current time?
   *
   * @param Array $session Session properties, as, e.g. from CRM_Anoncheckin_Utils_ExternData::getSessionInfo()
   *
   * @return bool
   */
  public static function sessionTimeIsValidNow($session) {

    $setting = CRM_Anoncheckin_Setting::singleton();
    $limitByTime = ($setting->get('anoncheckin_limit_checkin_by_time') ?? FALSE);
    if (!$limitByTime) {
      // Time checking is disabled, so just allow this.
      return TRUE;            
    }

    $allowanceMinutes = ($setting->get('anoncheckin_limit_checkin_minutes') ?? 0);
    $allowanceSeconds = ((int)$allowanceMinutes * 60);

    $windowStart = strtotime($session['startDatetimeUtc'] . ' UTC') - $allowanceSeconds;
    $windowEnd   = strtotime($session['endDatetimeUtc'] . ' UTC') + $allowanceSeconds;
    $now = time();

    $timeIsValid = ($now >= $windowStart && $now <= $windowEnd);

    return $timeIsValid;
  }

}
