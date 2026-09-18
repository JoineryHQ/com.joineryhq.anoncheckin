<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility method to handle configs for extern scripts.
 */
class CRM_Anoncheckin_Utils_App {

  /**
   * Get the full path to config file directory.
   * @return String
   */
  public static function getApp() {
    return new CRM_Anoncheckin_Extern_App_Default();
  }
}
