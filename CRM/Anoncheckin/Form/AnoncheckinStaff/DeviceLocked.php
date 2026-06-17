<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Anoncheckin_Form_AnoncheckinStaff_DeviceLocked extends CRM_Anoncheckin_Form_AnoncheckinStaff {

  public function __construct() {
    $this->_dataTypes = ['badge', 'device'];
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
    $p = $values['p'];    

    // Close the device.
    $this->_closeDevice($this->_validatedValues['deviceKey']);

    // Invalidate any devices locked to badge participant.
    $updateCount = $this->_invalidateDevicesForParticipant($p);
    
    CRM_Core_Session::singleton()->setStatus(E::ts('Sessions saved.'), 'Success.', 'success no-popup');
    if ($updateCount) {
      $statusMessage = E::ts('%1 device(s) that were locked for %2 have been invalidated.',[
        1 => $updateCount,
        2 => $this->_userVars['badge']['displayName'],
      ]);
      CRM_Core_Session::singleton()->setStatus($statusMessage, 'Devices invalidated.', 'success no-popup');
    }
      
    CRM_Core_Session::singleton()->setStatus(E::ts('Please ask the participant to re-scan their badge on their device.'), 'Action required.', 'alert no-popup');
    
  }
}
