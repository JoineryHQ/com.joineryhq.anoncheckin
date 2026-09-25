<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Single-page app display and processing.
 */
class CRM_Anoncheckin_Extern_App_SelfUnlock extends CRM_Anoncheckin_Extern_App {
  const INVALID_SIGNATURE = 1;
  const INVALID_TIMESTAMP = 2;
  
  function run() {
    if (!$this->isSelfUnlockSupported()) {
      $this->fatal("We're sorry, the \"Unlock via email\" feature is not currently enabled.");
    }
    
    // Determine the appropriate action.
    if ($_REQUEST['p'] ?? '') {
      $mode = 'request';
    }
    elseif ($_REQUEST['ds'] ?? '') {
      $mode = 'apply';
    }
    $method = strtolower($_SERVER['REQUEST_METHOD']);
    $actionFunctionName = "action_{$method}_{$mode}";
  
    // Perform called-for action, if it exists.
    if (!empty($actionFunctionName) && is_callable([$this, $actionFunctionName])) {
      $this->$actionFunctionName($actionValue);
      // If method was POST, and we're still here, redirect to clean app.
      if ($method == 'post') {
        // Post methods in this class should do their own redirection, but if they
        // didn't, just redirect to the default app.
        $this->redirectClean();
      }
    }
    else {
      $this->fatal('Unrecognized action.');
    }
    $this->setDebugMessage('action: '. $actionFunctionName);

    $this->assign('action', $actionFunctionName);

    parent::run();
  }
  
  /**
   * Handle initial clicking of "send me an unlock email" link.
   */
  private function action_get_request() {
    $this->setDebugMessage('we are: '. __METHOD__);
    // expected vars: p, ph
    $pid = $_REQUEST['p'];
    $ph = $_REQUEST['ph'];
    if (empty($pid) || !CRM_Anoncheckin_Utils_Value::validateValue($pid, $ph)) {
      $this->fatal('Invalid input.');
    }
    $emailAddress = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantEmail', $pid);
    if (empty($emailAddress)) {
      $this->fatal('We could not find an email address on file for you. Please see staff for help unlocking your badge.');
    }  
    $this->assign('emailTruncated', CRM_Utils_String::maskEmail($emailAddress));
  }

  /**
   * Handle form submission on "send me an unlock email" form.
   */
  private function action_post_request() {
    // expected vars: p, ph
    $pid = $_REQUEST['p'];
    $ph = $_REQUEST['ph'];
    if (empty($pid) || !CRM_Anoncheckin_Utils_Value::validateValue($pid, $ph)) {
      $this->fatal('Invalid input.');
    }
    $emailAddress = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantEmail', $pid);
    $badgeParticipant = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantInfo', $pid);
    \Civi::$statics['anoncheckin_self_unlock_link_pid'] = $pid;
    $result = civicrm_api3('Email', 'send', [
      'contact_id' => $badgeParticipant['contactId'],
      'template_id' => CRM_Anoncheckin_Utils_Settings::get('anoncheckin_self_unlock_template'),
    ]);    
    $this->setMessage("Please check your email at ". CRM_Utils_String::maskEmail($emailAddress) . " for a link to unlock your badge.", 'success');
    $this->redirectClean([
      'a' => 'self_unlock', 
      'p' => $pid,
      'ph' => $ph,
      'sent' => 1,
    ]);
  }

  /**
   * Handle initial clicking of "unlock" link.
   */
  private function action_get_apply() {
    $this->validateApplyLinkData();
    $this->assign('title', 'Almost done!');
    $pid = $_REQUEST['dp'];
    $badgeParticipant = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantInfo', $pid);
    $this->assign('displayName', $badgeParticipant['displayName']);
  }

  /**
   * Handle form submission on "unlock" link form.
   */
  private function action_post_apply() {
    $this->validateApplyLinkData();    
    $pid = $_REQUEST['dp'];
    $logNote = 'Participant self-unlock via email';
    $updateCount = CRM_Anoncheckin_Utils_Device::invalidateDevicesForParticipant($pid, $logNote);
    $badgeParticipant = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantInfo', $pid);
    $this->setMessage(E::ts("The badge for <strong>%1</strong> has been unlocked from all devices. Please lock this device to your badge now.", [
      '1' => $badgeParticipant['displayName']
    ]), 'success');
    $this->redirectClean(['p' => $pid, 'ph' => CRM_Anoncheckin_Utils_Value::generateSignature($pid)]);
  }
  
  private function validateApplyLinkData() {
    $pid = $_REQUEST['dp'];
    $expiryTimestamp = $_REQUEST['dt'];
    $data = "self_unlock|{$pid}|{$expiryTimestamp}";
    $dataSig = $_REQUEST['ds'];
    // Ensure no tampering.
    if (!CRM_Anoncheckin_Utils_Value::validateValue($data, $dataSig)) {
      $this->fatal('That link is not valid. Please see a staff member for assistance, or try again.');
    }
    // Ensure not expired.
    if ($expiryTimestamp < time()) {
      $this->fatal('That link has expired. Please see a staff member for assistance, or try using a newer link.');
    }
  }

  /**
   * Generate the unlock-by-email link to be sent to this participant.
   * @return String
   */
  public static function generateApplyLink(int $pid): string {
    $ttlMinutes = (int) CRM_Anoncheckin_Utils_Settings::get('anoncheckin_self_unlock_link_ttl');
    $expiryTimestamp = strtotime("+{$ttlMinutes} minutes");
    $data = "self_unlock|{$pid}|{$expiryTimestamp}";
    $dataSig = CRM_Anoncheckin_Utils_Value::generateSignature($data);
    $params = [
      'a' => 'self_unlock',
      'dp' => $pid,
      'dt' => $expiryTimestamp,
      'ds' => $dataSig,
    ];
    return CRM_Anoncheckin_Utils_Extern::getAppUrl($params);
  }
  
}
