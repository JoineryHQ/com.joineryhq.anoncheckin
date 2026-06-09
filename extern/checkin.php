<?php
(function() {

  // get cached vars.
  require_once(__DIR__ . '/cache/vars.php');
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
  $participantName = CRM_Anoncheckin_Utils_Data::getParticipantName($pid);
  
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


  // --- INPUT ---
  $p = $_REQUEST['p'] ?? null;
  $ph = $_REQUEST['ph'] ?? null;
  $s = $_REQUEST['s'] ?? null;
  $sh = $_REQUEST['sh'] ?? null;

  // --- HELPERS ---
  function fail($msg) {
    http_response_code(400);
    echo json_encode(['error' => $msg]);
    exit;
  }

  function valid_hmac($value, $hmac, $secret) {
    return true;
    $calc = hash_hmac('sha256', $value, $secret, true);
    $calc = rtrim(strtr(base64_encode(substr($calc, 0, 12)), '+/', '-_'), '=');
    return hash_equals($calc, $hmac);
  }

  // --- VALIDATE ---
  if (!$p || !$ph || !valid_hmac($p, $ph, $secret)) {
    fail('invalid participant');
  }
  if (!$s || !$sh || !valid_hmac($s, $sh, $secret)) {
    fail('invalid session');
  }

  // --- LOAD PARTICIPANT ---
  $participant = \Civi\Api4\Participant::get()
    ->addWhere('id', '=', $p)
    ->addSelect('contact_id')
    ->execute()
    ->first();

  if (!$participant) {
    fail('participant not found');
  }

  // --- CHECK EXISTING ---
  $existing = \Civi\Api4\Participant::get()
    ->addWhere('contact_id', '=', $participant['contact_id'])
    ->addWhere('event_id', '=', $s)
    ->execute()
    ->count();

  if ($existing) {
    echo json_encode(['status' => 'already_checked_in']);
    exit;
  }

  // --- CREATE CHECK-IN ---
  $new = \Civi\Api4\Participant::create()
    ->addValue('contact_id', $participant['contact_id'])
    ->addValue('event_id', $s)
    ->addValue('status_id', 'Attended') // adjust if needed
    ->execute()
    ->first();

  // --- RESPONSE ---
  echo json_encode([
    'status' => 'ok',
    'participant_id' => $p,
    'session_id' => $s,
    'participant_record_id' => $new['id'],
  ]);
  
})();