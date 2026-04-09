<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'anoncheckin.civix.php';
// phpcs:enable

use CRM_Anoncheckin_ExtensionUtil as E;

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
