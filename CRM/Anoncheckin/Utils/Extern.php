<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility methods for the extern app script.
 */
class CRM_Anoncheckin_Utils_Extern {

  const SESSION_STATUS_COMPLETED = 1;
  const SESSION_STATUS_TRANSFERRED = 2;
  const SESSION_STATUS_INVALIDATED = 3;
  
  const DEVICE_LOG_TYPE_USER = 1;
  const DEVICE_LOG_TYPE_ADMIN = 2;

  public static function getAppUrl($params = []) {
    $base = E::url('extern/app.php');
    if (!empty($params)) {
      $url = $base . '?' . http_build_query($params);
    }
    else {
      $url = $base;
    }
    return $url;    
  }
  
  public static function getApp() {
    if ($_REQUEST['a'] == 'staff_info') {
      return new CRM_Anoncheckin_Extern_App_StaffInfo();
    }
    return new CRM_Anoncheckin_Extern_App_Default();
  }
  
}
