<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Anoncheckin_Form_AnoncheckinStaff_SessionsIncorrect extends CRM_Anoncheckin_Form_AnoncheckinStaff {

  public function __construct() {
    $this->_dataTypes = ['badge'];
    parent::__construct();
  }
  /**
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    parent::_preBuildQuickForm();
    parent::buildQuickForm();
  }

  public function postProcess(): void {
    parent::postProcess();

    $values = $this->exportValues();
    $p = (int)$values['p'];    
      
    CRM_Core_Session::singleton()->setStatus(E::ts('Please ask the participant to confirm session listing on their device.'), 'Action required.', 'alert no-popup');
    
  }
}
