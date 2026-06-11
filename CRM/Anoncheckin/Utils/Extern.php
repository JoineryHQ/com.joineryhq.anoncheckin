<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility methods for the extern app script.
 */
class CRM_Anoncheckin_Utils_Extern {

  public static function getAppUrl() {
    return E::url('extern/app.php');
  }

  /**
   * Get the current device token from cookie.
   */
  public static function getUserDeviceKey(): ?string {
    return $_COOKIE['anoncheckin_device'] ?? NULL;
  }

  /**
   * Set the current device token cookie.
   */
  public static function setUserDeviceKey(string $deviceId): void {
    setcookie(
      'anoncheckin_device',
      $deviceId,
      [
        'expires' => time() + (86400 * 30), // 30 days
        'path' => '/',
        'secure' => TRUE,
        'httponly' => TRUE,
        'samesite' => 'Lax',
      ]
    );

    // Make available immediately during this request.
    $_COOKIE['anoncheckin_device'] = $deviceId;
  }

}
