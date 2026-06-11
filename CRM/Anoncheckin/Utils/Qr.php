<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility method to handle configs for extern scripts.
 */
class CRM_Anoncheckin_Utils_Qr {
  
  public static function getQrImageUrl($data, $color = 'black') {
    $ret = 'https://api.qrserver.com/v1/create-qr-code/?color='. $color .'&size=300&data=' . urlencode($data);
    return $ret;
  }

}
