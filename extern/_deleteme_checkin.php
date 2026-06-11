<?php
// Wrap this in an Immediately Invoked Function Expression, just to avoid
// potentially polluting the global namespace.
(function() {

  // get cached config so we can bootstrap civicrm.
  $anoncheckinConfig = json_decode(
    file_get_contents(__DIR__ . '/cache/config.json'),
    TRUE
  );
  
  $secret = $anoncheckinConfig['hmacSecret'];

  // initialize civicrm
  require_once $anoncheckinConfig['civicrmSettingsPath'];
  require_once 'CRM/Core/Config.php';
  CRM_Core_Config::singleton();

  // declare page object for output
  $page = new CRM_Anoncheckin_Extern_Page();
//  $page->run();
  
  // declare session manager
  $session = CRM_Anoncheckin_Utils_Session::singleton();

  if (($_GET['reset'] ?? '') == 1) {
    // fixme: this is for dev testing only, to be removed.
    $session->reset();
    header("Location: ". $_SERVER['PHP_SELF']);
    exit;    
  }
  // validate input or fail
  if ($_REQUEST['s'] && !CRM_Anoncheckin_Utils_Value::validateValue($_REQUEST['s'], $_REQUEST['sh'])) {
    $page->fatal('Invalid input.');
  }
  if ($_REQUEST['p'] && !CRM_Anoncheckin_Utils_Value::validateValue($_REQUEST['p'], $_REQUEST['ph'])) {
    $page->fatal('Invalid input.');
  }

  // store input in sesion
  if ($_REQUEST['p']) {
    $session->set('pid', $_REQUEST['p']);
  }
  if ($_REQUEST['s']) {
    $session->set('sid', $_REQUEST['s']);
  }
  
  $pid = $session->get('pid');
  $sid = $session->get('sid');

  $sessionTitle = CRM_Anoncheckin_Utils_Data::getSessionTitle($sid);
  $participantName = CRM_Anoncheckin_Utils_ExternData::selectParticipantInfo($pid);
  
  // if POST: process input.
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_POST['s'] || !$_POST['p']) {
      $session->setMessage('Invalid input: participant ID and session ID are both required.', 'error');
    }
    if (CRM_Anoncheckin_Utils_Data::participantHasSession($pid, $sid)) {
      $session->setMessage('You are already checked into this session. See your attended sessions below.', 'success');
    }
    else {
      if (CRM_Anoncheckin_Utils_Data::recordParticipantSession($pid, $sid)) {
        $message = $page->ts("We've recorded your attendance at <em>%1</em>", ['1' => $sessionTitle]);
        $session->setMessage($message, 'success');
      }
      else {
        $message = $page->ts("We couldn't record your attendance at %1. Please try again.", ['1' => $sessionTitle]);
        $session->setMessage($message, 'error');
      }
    }
    $session->set('sid', NULL);
    header("Location: ". $_SERVER['PHP_SELF']);
    exit;
  }
  
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Only assign session-specific vars on GET; no need on POST.
    if (CRM_Anoncheckin_Utils_Data::participantHasSession($pid, $sid)) {
      $session->setMessage('You are already checked into this session. See your attended sessions below.', 'success');
    }
    else {
      $page->assign('s', $sid);
      $page->assign('sh', CRM_Anoncheckin_Utils_Value::generateHmac($sid));
      $page->assign('sessionTitle', $sessionTitle);
    }
  }
  
  $page->assign('participantName', $participantName);
  $page->assign('p', $pid);
  $page->assign('ph', CRM_Anoncheckin_Utils_Value::generateHmac($pid));
  
  // load attendance list
  $attendedSessionTitles = [];
  $participantSessions = CRM_Anoncheckin_Utils_Data::getParticipantSessions($pid);
  foreach ($participantSessions as $participantSession) {
    $attendedSessionTitles[] = $page->ts($participantSession);
  }
  $page->assign('attendedSessionNames', $attendedSessionTitles);
  
  $page->print();

})();