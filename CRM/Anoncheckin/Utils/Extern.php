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

  public static function getAppUrl() {
    return E::url('extern/app.php');
  }

  public static function initializeDevice() {
    $device = CRM_Anoncheckin_Utils_Device::createDevice();
    self::setUserDeviceKey($device['deviceKey']);    
  }
  /**
   * Get the current device token from cookie.
   */
  public static function getUserDeviceKey(): ?string {
    return $_COOKIE['anoncheckin_device'] ?? NULL;
  }

  /**
   * Set the current device token cookie and update _device.expires in DB.
   */
  public static function setUserDeviceKey(string $deviceKey): void {
    // Get device expiry as unix timestamp.
    $deviceExpiresTimestamp = CRM_Anoncheckin_Utils_Device::calculateExpiresTimestamp();
    // Set cookie to expire 24 hours after device expiry.
    // This ensures cookies have a healthy margin of survival so as not to expire before device.
    $cookieExpiresTimestamp = $deviceExpiresTimestamp + (24 * 60 * 60);
    setcookie(
      'anoncheckin_device',
      $deviceKey,
      [
        'expires' => $cookieExpiresTimestamp,
        'path' => '/',
        'secure' => TRUE,
        'httponly' => TRUE,
        'samesite' => 'Lax',
      ]
    );

    // Make available immediately during this request.
    $_COOKIE['anoncheckin_device'] = $deviceKey;

    // extend device expiry in the database (assuming device not already expired)
    CRM_Anoncheckin_Utils_ExternData::extendDeviceExpires($deviceKey);
  }

  /**
   * Is this session available for recording, in light of the current time?
   *
   * @param Array $session Session properties, as, e.g. from CRM_Anoncheckin_Utils_ExternData::getSessionInfo()
   *
   * @return bool
   */
  public static function sessionTimeIsValidNow($session) {

    $setting = CRM_Anoncheckin_Setting::singleton();
    $limitByTime = ($setting->get('anoncheckin_limit_checkin_by_time') ?? FALSE);
    if (!$limitByTime) {
      // Time checking is disabled, so just allow this.
      return TRUE;            
    }

    $allowanceMinutes = ($setting->get('anoncheckin_limit_checkin_minutes') ?? 0);
    $allowanceSeconds = ((int)$allowanceMinutes * 60);

    $windowStart = strtotime($session['startDatetimeUtc'] . ' UTC') - $allowanceSeconds;
    $windowEnd   = strtotime($session['endDatetimeUtc'] . ' UTC') + $allowanceSeconds;
    $now = time();

    $timeIsValid = ($now >= $windowStart && $now <= $windowEnd);

    return $timeIsValid;
  }
}
