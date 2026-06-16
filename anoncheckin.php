<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'anoncheckin.civix.php';
// phpcs:enable

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Implements hook_civicrm_check().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_check/
 *
 */
function anoncheckin_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Admin_Form_Generic') {
    if ($form->getSettingPageFilter() == 'anoncheckin') {
      // This fires after the form has saved changes to settings. 
      // Rebuild cached config.
      // TODO: This doesn't address settings changes via api or other mechanisms
      // and as of this writing, we have no mechanism to do so.
      CRM_Anoncheckin_Utils_Config::refreshConfigFile(TRUE);
    }
  }
}

/**
 * Implements hook_civicrm_check().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_check/
 *
 */
function anoncheckin_civicrm_check(&$messages, $statusNames = [], $includeDisabled = FALSE) {
  // We're cheating a little, in that we'll never send a "system check" message about this.

  // Ensure extern config file has latest values.
  CRM_Anoncheckin_Utils_Config::refreshConfigFile();
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function anoncheckin_civicrm_config(\CRM_Core_Config $config): void {
  _anoncheckin_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function anoncheckin_civicrm_install(): void {
  _anoncheckin_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function anoncheckin_civicrm_enable(): void {
  _anoncheckin_civix_civicrm_enable();
}
