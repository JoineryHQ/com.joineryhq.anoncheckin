<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

class CRM_Anoncheckin_Form_Loadtest extends CRM_Core_Form {

  public function buildQuickForm() {
    // Simple submit button
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);

    // Display something
    $this->assign('message', 'Load test form');
  }

  public function preProcess() {
    parent::preProcess();

    $session = CRM_Core_Session::singleton();

    // --- Simulate session usage ---
    $counter = $session->get('loadtest_counter') ?? 0;
    $counter++;
    $session->set('loadtest_counter', $counter);
    $this->assign('counter', $counter);
    
    // --- Simulate DB reads (2–3 queries) ---

    // 1. Fetch a contact (arbitrary)
    $sql1 = "SELECT id, display_name FROM civicrm_contact ORDER BY id DESC LIMIT 1";
    $dao1 = CRM_Core_DAO::executeQuery($sql1);
    if ($dao1->fetch()) {
      $this->assign('contact_name', $dao1->display_name);
    }

    // 2. Count participants (simulates "session list")
    $sql2 = "SELECT COUNT(*) as cnt FROM civicrm_participant";
    $dao2 = CRM_Core_DAO::executeQuery($sql2);
    if ($dao2->fetch()) {
      $this->assign('participant_count', $dao2->cnt);
    }

    // 3. Fetch an event (simulates "session name")
    $sql3 = "SELECT id, title FROM civicrm_event ORDER BY id DESC LIMIT 1";
    $dao3 = CRM_Core_DAO::executeQuery($sql3);
    if ($dao3->fetch()) {
      $this->assign('event_title', $dao3->title);
    }
  }

  public function postProcess() {
    parent::postProcess();

    // --- Simulate a DB write ---
    // Insert a minimal row into log table (safe-ish target)

    $sql = "
      INSERT INTO civicrm_log (entity_table, entity_id, data, modified_id, modified_date)
      VALUES ('civicrm_contact', 1, 'anoncheckin loadtest', 1, NOW())
    ";

    try {
      CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
      // Ignore errors (log table may not exist or may differ)
    }

    CRM_Core_Session::setStatus('Submitted', 'Load Test', 'success');
  }

}