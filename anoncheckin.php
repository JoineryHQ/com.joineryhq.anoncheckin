<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'anoncheckin.civix.php';
// phpcs:enable

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm/
 *
 */
function anoncheckin_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Admin_Form_Generic') {
    if ($form->getSettingPageFilter() == 'anoncheckin') {
      // Add our javascript for this form.
      CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/CRM_Admin_Form_Generic-anoncheckin.js');
    }
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postProcess/
 *
 */
function anoncheckin_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Admin_Form_Generic') {
    if ($form->getSettingPageFilter() == 'anoncheckin') {
      // This fires after the form has saved changes to settings.
      // Rebuild cached config.
      // TODO: This doesn't address settings changes via api or other mechanisms
      // and as of this writing, we have no mechanism to do so.
      if (CRM_Anoncheckin_Utils_Config::refreshConfigFile(TRUE)) {
        CRM_Core_Session::setStatus('Updated extension cached config.', 'Cache updated', 'success');
      }
      else {
        CRM_Core_Session::setStatus('Could not update extension cached config.', 'Error', 'error');
      }
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
  if (CRM_Anoncheckin_Utils_Session::eventHasSessions((int) $participant['event_id'])) {
    $pid = $data['participant_id'];
    $q = ['p' => $pid, 'ph' => CRM_Anoncheckin_Utils_Value::generateSignature($pid)];
    $data['current_value'] = CRM_Anoncheckin_Utils_Extern::getAppUrl() . '?' . http_build_query($q);
  }
}

function anoncheckin_civicrm_alterBadge($labelName, CRM_Badge_BAO_Badge &$label, &$format, &$participant) {
  foreach ($format['token'] as &$rowToken) {
    if ($rowToken['token'] == '{contact.anoncheckinqr}') {
      // This token actually prints nothing within the flow of the badge.
      $rowToken['value'] = '';

      // Get participant ID.
      $pid = $participant['participant_id'];

      // Generate app query params for pid.
      $queryParams = ['p' => $pid, 'ph' => CRM_Anoncheckin_Utils_Value::generateSignature($pid)];

      // Create the app url for this badge.
      $qrData = CRM_Anoncheckin_Utils_Extern::getAppUrl() . '?' . http_build_query($queryParams);

      // Our QR image file measurements; TODO: these should be user-editable settings:
      // Units are milimeters because that's hardcoded in CiviCRM's core badge-
      // generation code -- reference: CRM_Badge_BAO_Badge::createLabels()
      // QR code image is a square; this is the length (in milimmeters) of one side.
      $qrSideLength = 30;
      // Distance (in milimeters) between the bottom of the badge and the bottom
      // of the QR code image.
      $qrBottomPadding = 17;

      // Calculation of QR image side length (in pixels) based on 300-dpi and
      // QR side length in milimeters.
      $qrSizePixels = ($qrSideLength * 12);

      // Create (or get from cache) the URL of the QR code image.
      // Rationale: creating qr codes in our way is server-intensive. So we cache
      // them as actual deterministically-named image files. Then we can just
      // re-use them as needed.
      $qrImageUrl = CRM_Anoncheckin_Utils_Qr::getQrImageUrl($qrData, 'black', $qrSizePixels, TRUE, "p={$pid}");

      // Values from the $label object, which we'll need for proper placement.
      // Height (in mm) of a single label.
      $labelHeight = $label->pdf->height;
      // Width (in mm) of a single label.
      $labelWidth = $label->pdf->width;
      // Count (zero-based) of the horizontal (column) position of the current badge.
      // (First column is 0, second is 1, etc.)
      $labelCountX = $label->pdf->countX;
      // Count (zero-based) of the vertical (row) position of the current badge.
      // (First row is 0, second is 1, etc.)
      $labelCountY = $label->pdf->countY;
      // Width (in mm) of column gutters (spaces between columns)
      $labelXSpace = $label->pdf->xSpace;
      // Height (in mm) of row gutters (spaces between rows)
      $labelXSpace = $label->pdf->xSpace;

      // Calculation of total column gutter space preceding the current badge.
      // (This is, BTW "0" in the first row, as it should be.)
      $labelXSpaces = ($labelCountX * $labelXSpace);
      // Calculation of total column gutter space preceding the current badge.
      // (This is, BTW "0" in the first column, as it should be.)
      $labelYSpaces = ($labelCountY ? ($labelCountY * $labelYSpace) : 0);

      // Calculation of the X placement of our QR code image.
      // This is:
      //    Page left margin;
      //    plus: Total width of all badges in this row, including the current badge;
      //    plus: Total column gutter space preceding the current badge;
      //    minus: half of one label width;
      //    minus: half of one QR image width.
      $qrX = $label->pdf->marginLeft + (($labelCountX + 1) * $labelWidth) + $labelXSpaces - ($labelWidth / 2) - ($qrSideLength / 2);
      // Calculation of the Y placement of our QR code image.
      // This is:
      //    Page top margin;
      //    plus: Total height of all badges in this row, including the current badge;
      //    plus: Total row gutter space preceding the current badge;
      //    minus: QR image height;
      //    minus: our bottom padding.
      $qrY = $label->pdf->marginTop + (($labelCountY + 1) * $labelHeight) + $labelYSpaces - $qrSideLength - $qrBottomPadding;

      // printImage (below) will increment x and y, but we actually don't want that;
      // We want to print our QR code wherever we decide, without regard to (and
      // without affecting) the position of other elements. So we'll store the
      // current x and y values now, and then after printImage(), we'll reset
      // x and y to those values.
      $origX = $label->pdf->GetAbsX();
      $origY = $label->pdf->GetY();
      $label->printImage($qrImageUrl, $qrX, $qrY, $qrSideLength, $qrSideLength);
      $label->pdf->SetXY($origX, $origY);
      break;
    }
  }

}
