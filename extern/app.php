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
  $app = new CRM_Anoncheckin_Extern_App();
  $app->run();  

  return;
  
  function zz_unused_legacy() {
    // declare session manager
    $session = CRM_Anoncheckin_Utils_Session::singleton();

    if (($_GET['reset'] ?? '') == 1) {
      // fixme: this is for dev testing only, to be removed.
      $session->reset();
      header("Location: ". $_SERVER['PHP_SELF']);
      exit;    
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
    $participantName = CRM_Anoncheckin_Utils_ExternData::getParticipantInfo($pid);

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
          $message = $app->ts("We've recorded your attendance at <em>%1</em>", ['1' => $sessionTitle]);
          $session->setMessage($message, 'success');
        }
        else {
          $message = $app->ts("We couldn't record your attendance at %1. Please try again.", ['1' => $sessionTitle]);
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
        $app->assign('s', $sid);
        $app->assign('sh', CRM_Anoncheckin_Utils_Value::generateHmac($sid));
        $app->assign('sessionTitle', $sessionTitle);
      }
    }

    $app->assign('participantName', $participantName);
    $app->assign('p', $pid);
    $app->assign('ph', CRM_Anoncheckin_Utils_Value::generateHmac($pid));

    // load attendance list
    $attendedSessionTitles = [];
    $participantSessions = CRM_Anoncheckin_Utils_Data::getParticipantSessions($pid);
    foreach ($participantSessions as $participantSession) {
      $attendedSessionTitles[] = $app->ts($participantSession);
    }
    $app->assign('attendedSessionNames', $attendedSessionTitles);

    $app->print();
  }
})();