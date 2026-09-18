<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility method to handle configs for extern scripts.
 */
class CRM_Anoncheckin_Utils_App {

  /**
   * Get the appropriate app object
   * @return Object Child class of CRM_Anoncheckin_Extern_App
   */
  public static function getApp() {
    if ($_REQUEST['a'] == 'staff_info') {
      return new CRM_Anoncheckin_Extern_App_StaffInfo();
    }
    return new CRM_Anoncheckin_Extern_App_Default();
  }
}
