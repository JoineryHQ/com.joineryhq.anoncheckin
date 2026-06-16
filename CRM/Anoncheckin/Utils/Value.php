<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Value utilities
 */
class CRM_Anoncheckin_Utils_Value {
  public static function validateValue($value, $sig = NULL) : bool {
    if (empty($value) || empty($sig)) {
      return false;
    }
    return hash_equals(
      self::generateSignature($value),
      $sig
    );
  }

  public static function generateSignature($value) {
    $setting = CRM_Anoncheckin_Setting::singleton();
    $secret = $setting->get('anoncheckin_hmac_secret');
    $hmac = hash_hmac('sha256', (string) $value, $secret, true); // raw binary
    $truncated = substr($hmac, 0, 12);               // 96 bits
    $sig = self::base64url_encode($truncated);             // 16 chars
    return $sig;
  }
  
  private static function base64url_encode($data) {
      return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  private static function base64url_decode($data) {
      return base64_decode(strtr($data, '-_', '+/'));
  }  
}
