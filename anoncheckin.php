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

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function anoncheckin_civicrm_navigationMenu(&$menu) {
  $pages = [
    'staff_help' => [
      'label'      => E::ts('Anonymous QR session check-in: Staff Assistance'),
      'name'       => 'anoncheckin-staff-help',
      'url'        => 'civicrm/admin/anoncheckin/staff',
      'parent' => array('Events'),
      'permission' => 'edit event participants',
    ],
  ];

  foreach ($pages as $item) {
    // Check that our item doesn't already exist.
    $menu_item_search = array('url' => $item['url']);
    $menu_items = array();
    CRM_Core_BAO_Navigation::retrieve($menu_item_search, $menu_items);
    if (empty($menu_items)) {
      // Now we're sure it doesn't exist; add it to the menu.
      $path = implode('/', $item['parent']);
      unset($item['parent']);
      _anoncheckin_civix_insert_navigation_menu($menu, $path, $item);
    }
  }
}

/**
 * Implements hook_civicrm_alterBarcode().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterBarcode
 */
function anoncheckin_civicrm_alterBarcode(&$data, $type, $context) {
  if (
    $type != 'qrcode'
    || $context != 'name_badge'
  ) {
    // We only operate on name badge qr codes.
    return;
  }
  $participant = \Civi\Api4\Participant::get()
    ->addSelect('event_id')
    ->addWhere('id', '=', $data['participant_id'])
    ->execute()
    ->first();
  // If there are anoncheckin sessions for this event, we will simply replace the qr code 
  // destination
  if (CRM_Anoncheckin_Utils_Session::eventHasSessions((int)$participant['event_id'])) {
    $pid = $data['participant_id'];
    $q = ['p' => $pid, 'ph' => CRM_Anoncheckin_Utils_Value::generateSignature($pid)];
    $data['current_value'] = CRM_Anoncheckin_Utils_Extern::getAppUrl() . '?' . http_build_query($q);
  }
}
