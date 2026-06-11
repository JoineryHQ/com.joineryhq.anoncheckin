<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility methods for device handling in performance-optimized extern scripts.
 */
class CRM_Anoncheckin_Utils_Device {
  
  // Mimic values as defined in anoncheckin_device_status reserved optionGroup.
  const DEVICE_STATUS_PENDING = 1; // FIXME: DEPRECATED
  const DEVICE_STATUS_LOCKED = 2;
  const DEVICE_STATUS_INVALIDATED = 3;
  const DEVICE_STATUS_CLOSED = 4;
  
  public static function createDevice() : array {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $userAgentShort = self::getUserAgentShort($userAgent);
    $deviceStatusId = self::DEVICE_STATUS_PENDING;
    $deviceKey = self::generateDeviceKey();
    $deviceId = CRM_Anoncheckin_Utils_ExternData::insertDevice($deviceKey, $userAgent, $userAgentShort, $deviceStatusId);
    
    // Return all device attributes.
    return [
      'userAgent' => $userAgent,
      'userAgentShort' => $userAgentShort,
      'deviceStatusId' => $deviceStatusId,
      'deviceKey' => $deviceKey,
      'deviceId' => $deviceId,
    ];
  }
  
  public static function lockDeviceToParticipant($device, $participantId) {
    $deviceParams = [
      'participantId' => $participantId,
      'deviceStatusId' => self::DEVICE_STATUS_LOCKED,
    ];
    CRM_Anoncheckin_Utils_ExternData::updateDevice($device['deviceKey'], $deviceParams);
  }
  
  
  private static function generateDeviceKey() {
    return bin2hex(random_bytes(32));
  }
  
  /**
   * Convert a raw User-Agent string into a short human-readable form.
   *
   * Returns an empty string if no easy guess can be made.
   * Examples:
   *   Chrome on Android
   *   Safari on iOS
   *   Edge on Windows
   *   Firefox on Linux
   */
  public static function getUserAgentShort(string $userAgent): string {

    // Browser
    if (preg_match('/EdgiOS\//i', $userAgent)) {
      $browser = 'Edge';
    }
    elseif (preg_match('/EdgA\//i', $userAgent)) {
      $browser = 'Edge';
    }
    elseif (preg_match('/Edg\//i', $userAgent)) {
      $browser = 'Edge';
    }
    elseif (preg_match('/CriOS\//i', $userAgent)) {
      $browser = 'Chrome';
    }
    elseif (preg_match('/Chrome\//i', $userAgent)) {
      $browser = 'Chrome';
    }
    elseif (preg_match('/FxiOS\//i', $userAgent)) {
      $browser = 'Firefox';
    }
    elseif (preg_match('/Firefox\//i', $userAgent)) {
      $browser = 'Firefox';
    }
    elseif (
      preg_match('/Safari\//i', $userAgent)
      && !preg_match('/Chrome\//i', $userAgent)
    ) {
      $browser = 'Safari';
    }

    // OS
    if (
      preg_match('/iPhone/i', $userAgent)
      || preg_match('/iPad/i', $userAgent)
      || preg_match('/iPod/i', $userAgent)
    ) {
      $os = 'iOS';
    }
    elseif (preg_match('/Android/i', $userAgent)) {
      $os = 'Android';
    }
    elseif (preg_match('/Windows/i', $userAgent)) {
      $os = 'Windows';
    }
    elseif (
      preg_match('/Macintosh/i', $userAgent)
      || preg_match('/Mac OS X/i', $userAgent)
    ) {
      $os = 'Mac';
    }
    elseif (preg_match('/Linux/i', $userAgent)) {
      $os = 'Linux';
    }

    $parts = array_filter([$browser, $os]);
    $ret = implode(' on ', $parts);
    return $ret;
  }  
}
