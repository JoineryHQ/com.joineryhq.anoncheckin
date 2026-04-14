<?php
declare(strict_types = 1);

// --- CONFIG ---
//define('CIVICRM_SETTINGS_PATH', '/var/www/fppta/wp-content/uploads/civicrm/civicrm.settings.php');
$cid = 7037;

// --- BOOTSTRAP CIVI ONLY ---
require_once '/var/www/fppta/wp-content/uploads/civicrm/civicrm.settings.php';
require_once 'CRM/Core/Config.php';
CRM_Core_Config::singleton();

// --- JSON RESPONSE ---
header('Content-Type: application/json');

// --- SESSION (same as your form) ---
$session = CRM_Core_Session::singleton();

$counter = $session->get('loadtest_counter') ?? 0;
$counter++;
$session->set('loadtest_counter', $counter);

// --- DB READS ---

// 1. Contact
$sql1 = "SELECT id, display_name FROM civicrm_contact WHERE id = %1 LIMIT 1";
$params1 = [1 => [$cid, 'Integer']];
$dao1 = CRM_Core_DAO::executeQuery($sql1, $params1);

$contact_name = null;
if ($dao1->fetch()) {
  $contact_name = $dao1->display_name;
}

// 2. Participant count
$sql2 = "SELECT COUNT(*) as cnt FROM civicrm_participant";
$dao2 = CRM_Core_DAO::executeQuery($sql2);

$participant_count = 0;
if ($dao2->fetch()) {
  $participant_count = $dao2->cnt;
}

// 3. Event (using contrib page in case test server has no events)
$sql3 = "SELECT id, title FROM civicrm_contribution_page ORDER BY id DESC LIMIT 1";
$dao3 = CRM_Core_DAO::executeQuery($sql3);

$event_title = null;
if ($dao3->fetch()) {
  $event_title = $dao3->title;
}

// --- OPTIONAL WRITE (trigger with POST) ---
$write_ok = null;

if ($_GET['doWrite']) {
  $sql = "
    INSERT INTO civicrm_log (entity_table, entity_id, data, modified_id, modified_date)
    VALUES ('civicrm_contact', %1, 'anoncheckin extern loadtest', %1, NOW())
  ";
  try {
    CRM_Core_DAO::executeQuery($sql, [1 => [$cid, 'Integer']]);
    $write_ok = true;
  }
  catch (Exception $e) {
    $write_ok = false;
  }
}

// --- RESPONSE ---
echo json_encode([
  'counter' => $counter,
  'contact_name' => $contact_name,
  'participant_count' => $participant_count,
  'event_title' => $event_title,
  'write' => $write_ok,
]);