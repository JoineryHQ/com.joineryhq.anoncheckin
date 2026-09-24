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
      // Add a message with link to test qr codes.
      $qrTestingUrl = CRM_Utils_System::url('civicrm/anoncheckin/testqrcodes', NULL, NULL, NULL, NULL, TRUE, NULL);
      CRM_Core_Session::setStatus(E::ts('You may also view <a href="%1">QR codes for testing</a>', ['1' => $qrTestingUrl]), NULL, 'no-popup');

      // Inform javascript whether 'emailapi' is installed.
      $jsVars = [
        'isEmailApiInstalled' => (CRM_Extension_System::singleton()->getManager()->getStatus('org.civicoop.emailapi') == 'installed'),
      ];
      CRM_Core_Resources::singleton()->addVars(E::SHORT_NAME, $jsVars);
    }
  }
  elseif ($formName == 'CRM_Badge_Form_Layout') {
    $form->addElement('checkbox', 'is_anoncheckinqr', E::ts('Display QR code for Anonymous QR session check-in?'));
    // Assign bhfe fields to the template, so our new field has a place to live.
    $tpl = CRM_Core_Smarty::singleton();
    $bhfe = $tpl->getTemplateVars('beginHookFormElements');
    if (!$bhfe) {
      $bhfe = array();
    }
    $bhfe[] = 'is_anoncheckinqr';
    $form->assign('beginHookFormElements', $bhfe);

    // Add javascript that will relocate our field to a sensible place in the form.
    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.anoncheckin', 'js/CRM_Badge_Form_Layout.js');

    // Set defaults so our field has the right value.
    $badgeLayoutId = (int) $form->getVar('_id');
    $defaults = [
      'is_anoncheckinqr' => CRM_Anoncheckin_Utils_Settings::getBadgeLayoutHasQrCode($badgeLayoutId),
    ];
    $form->setDefaults($defaults);

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
  elseif ($formName == 'CRM_Badge_Form_Layout') {
    $values = $form->getSubmitValues();
    $isAnoncheckinQr = (bool) $values['is_anoncheckinqr'];
    $badgeLayoutId = $form->getVar('_id');
    CRM_Anoncheckin_Utils_Settings::setBadgeLayoutHasQrCode($badgeLayoutId, $isAnoncheckinQr);
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

function anoncheckin_civicrm_alterBadge($labelName, CRM_Badge_BAO_Badge &$label, &$format, &$participant) {
  // Static vars to avoid needless repeated calculation (since this hook fires once per badge).
  static $badgeLayoutHasQrCode;
  static $staticValues = [];

  if (!isset($badgeLayoutHasQrCode)) {
    $badgeLayoutId = (int) ($format['labelId'] ?? NULL);
    $badgeLayoutHasQrCode = (bool) CRM_Anoncheckin_Utils_Settings::getBadgeLayoutHasQrCode($badgeLayoutId);
  }
  if (!$badgeLayoutHasQrCode) {
    return;
  }

  // Values from the $label object, which we'll need for proper placement.
  // Height (in mm) of a single label.
  $labelHeight = $label->pdf->height;
  // Width (in mm) of a single label.
  $labelWidth = $label->pdf->width;

  if (empty($staticValues)) {
    // Calculate some values which will be the same for all badges.

    // Our QR image file measurements;
    // Units are milimeters because that's hardcoded in CiviCRM's core badge-
    // generation code -- reference: CRM_Badge_BAO_Badge::createLabels()
    // QR code image is a square; this is the length (in milimmeters) of one side.
    $qrSideLength = CRM_Anoncheckin_Utils_Settings::get('anoncheckin_badge_qr_size');
    // Distance (in milimeters) between the bottom of the badge and the bottom
    // of the QR code image.
    $qrMinMargin = CRM_Anoncheckin_Utils_Settings::get('anoncheckin_badge_qr_min_margin');

    // Calculation of QR image side length (in pixels) based on 300-dpi and
    // QR side length in milimeters.
    $qrSizePixels = ($qrSideLength * 12);

    // Width (in mm) of column gutters (spaces between columns)
    $labelXSpace = $label->pdf->xSpace;
    // Height (in mm) of row gutters (spaces between rows)
    $labelYSpace = $label->pdf->ySpace;

    // Relative x and y position (e.g. 'top/left') per extension settings.
    $qrPosX = CRM_Anoncheckin_Utils_Settings::get('anoncheckin_badge_qr_pos_x');
    $qrPosY = CRM_Anoncheckin_Utils_Settings::get('anoncheckin_badge_qr_pos_y');

    // Determine badge-relative x offset for QR code image (per 'x' setting).
    // This is the mm distance from badge-left-edge to image-left-edge
    switch ($qrPosX) {
      case 'left':
        $qrOffsetX = $qrMinMargin;
        break;

      case 'center':
        $qrOffsetX = (($labelWidth / 2) - ($qrSideLength / 2));
        break;

      case 'right':
        $qrOffsetX = ($labelWidth - $qrSideLength - $qrMinMargin);
        break;
    }

    // Determine badge-relative y offset for QR code image (per 'y' setting).
    // This is the mm distance from badge-top-edge to image-top-edge.
    switch ($qrPosY) {
      case 'top':
        $qrOffsetY = $qrMinMargin;
        break;

      case 'center':
        $qrOffsetY = ($labelHeight / 2) - ($qrSideLength / 2);
        break;

      case 'bottom':
        $qrOffsetY = ($labelHeight - $qrSideLength - $qrMinMargin);
        break;
    }
    $staticValues = [
      'qrSizePixels' => $qrSizePixels,
      'qrOffsetX' => $qrOffsetX,
      'qrOffsetY' => $qrOffsetY,
      'qrSideLength' => $qrSideLength,
    ];
  }

  // Get participant ID.
  $pid = $participant['participant_id'];

  // Generate app query params for pid.
  $queryParams = ['p' => $pid, 'ph' => CRM_Anoncheckin_Utils_Value::generateSignature($pid)];

  // Create the app url for this badge.
  $qrData = CRM_Anoncheckin_Utils_Extern::getAppUrl() . '?' . http_build_query($queryParams);

  // Create (or get from cache) the URL of the QR code image.
  // Rationale: creating qr codes in our way is server-intensive. So we cache
  // them as actual deterministically-named image files. Then we can just
  // re-use them as needed.
  $qrImageUrl = CRM_Anoncheckin_Utils_Qr::getQrImageUrl($qrData, 'black', $staticValues['qrSizePixels'], TRUE, "p={$pid}");

  // Count (zero-based) of the horizontal (column) position of the current badge.
  // (First column is 0, second is 1, etc.)
  $labelCountX = $label->pdf->countX;
  // Count (zero-based) of the vertical (row) position of the current badge.
  // (First row is 0, second is 1, etc.)
  $labelCountY = $label->pdf->countY;

  // Calculation of total column gutter space preceding the current badge.
  // (This is, BTW "0" in the first row, as it should be.)
  $labelXSpaces = ($labelCountX * $labelXSpace);
  // Calculation of total column gutter space preceding the current badge.
  // (This is, BTW "0" in the first column, as it should be.)
  $labelYSpaces = ($labelCountY ? ($labelCountY * $labelYSpace) : 0);

  // Calculation of the X placement of our QR code image.
  // This is:
  //    Page left margin;
  //    plus: Total width of all preceding badges in this row;
  //    plus: Total column gutter space preceding the current badge;
  //    plus: qrOffsetX
  $qrX = $label->pdf->marginLeft + ($labelCountX * $labelWidth) + $labelXSpaces + $staticValues['qrOffsetX'];

  // Calculation of the Y placement of our QR code image.
  // This is:
  //    Page top margin;
  //    plus: Total height of all preceding badges in this column;
  //    plus: Total row gutter space preceding the current badge;
  //    plus: qrOffsetY
  $qrY = $label->pdf->marginTop + ($labelCountY * $labelHeight) + $labelYSpaces + $staticValues['qrOffsetY'];

  // printImage (below) will increment x and y, but we actually don't want that;
  // We want to print our QR code wherever we decide, without regard to (and
  // without affecting) the position of other elements. So we'll store the
  // current x and y values now, and then after printImage(), we'll reset
  // x and y to those values.
  $origX = $label->pdf->GetAbsX();
  $origY = $label->pdf->GetY();
  $label->printImage($qrImageUrl, $qrX, $qrY, $staticValues['qrSideLength'], $staticValues['qrSideLength']);
  $label->pdf->SetXY($origX, $origY);
}
