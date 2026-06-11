<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility methods for the extern app script.
 */
class CRM_Anoncheckin_Utils_Extern {

  const SESSION_STATUS_COMPLETED = 1;
  const SESSION_STATUS_TRANSFERRED = 2;
  const SESSION_STATUS_INVALIDATED = 3;

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

  /**
   * Is this session available for recording, in light of the current time?
   *
   * @param Array $session Session properties, as, e.g. from CRM_Anoncheckin_Utils_ExternData::getSessionInfo()
   *
   * @return bool
   */
  public static function sessionTimeIsValidNow($session) {

    $limitByTime = (Civi::settings()->get('anoncheckin_limit_checkin_by_time') ?? FALSE);
    if (!$limitByTime) {
      // Time checking is disabled, so just allow this.
      return TRUE;            
    }

    $allowanceMinutes = (Civi::settings()->get('anoncheckin_limit_checkin_minutes') ?? 0);
    $allowanceSeconds = ((int)$allowanceMinutes * 60);

    $windowStart = strtotime($session['startDatetimeUtc'] . ' UTC') - $allowanceSeconds;
    $windowEnd   = strtotime($session['endDatetimeUtc'] . ' UTC') + $allowanceSeconds;
    $now = time();

    $timeIsValid = ($now >= $windowStart && $now <= $windowEnd);

    return $timeIsValid;
  }
}
