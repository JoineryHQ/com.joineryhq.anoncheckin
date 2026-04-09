<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

class CRM_Anoncheckin_Page_Reset extends CRM_Core_Page {

  public function run() {
    $storage = CRM_Anoncheckin_Utils_Session::singleton();
    $storage->reset();
    $this->assign('isReset', TRUE);
    
    parent::run();
  }

}
