<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility methods for QR code handling
 */
class CRM_Anoncheckin_Utils_Qr {

  /**
   * Get the URL for a QR code image.
   *
   * @param String $data The data to be stored in the QR code, e.g. a URL.
   * @param String $color HTML color of the QR code
   * @param Integer $size Width (also height), in pixels, of the QR code image.
   * @param Boolean $cached Whether to cache the image.
   *    If true, cache this image as a png on the server and return the URL to that image.
   *    Otherise, just return a URL for the offsite QR generator api.
   * @param String $cacheLabel If $cache, this string will be appended (before
   *    the .png extension) to the filename of the cached image; probably useful
   *    for human review/debugging).
   * @return A URL which should display the QR code image.
   */
  public static function getQrImageUrl($data, $color = 'black', $size = 300, $cached = FALSE, $cacheLabel = '') {
    $encodedData = urlencode($data);
    $tempUrl = 'https://api.qrserver.com/v1/create-qr-code/?color=' . $color . '&size=' . "{$size}x{$size}" . '&data=' . $encodedData;
    if ($cached) {
      $qrDirname = 'anoncheckin-qr-cache';
      $qrImageDirectoryPath = Civi::paths()->getPath("[civicrm.files]/.") . "/{$qrDirname}/";
      $qrImageDirectoryUrl = Civi::paths()->getUrl("[civicrm.files]/.", 'absolute') . "/{$qrDirname}/";
      // Create our cache dir if it doesn't exist.
      if (!is_dir($qrImageDirectoryPath)) {
        mkdir($qrImageDirectoryPath);
      }

      // Generate the png image and store it with the appropriate name.
      $fileName = hash('sha256', "{$data}|{$size}|{$color}") . ($cacheLabel ? "-{$cacheLabel}" : '') . '.png';
      $filePath = $qrImageDirectoryPath . $fileName;
      $fileUrl = $qrImageDirectoryUrl . $fileName;
      if (!file_exists($filePath)) {
        $httpClient = Civi::service('httpClient');
        [$response, $content] = $httpClient->get($tempUrl);
        if ($response == CRM_Utils_HttpClient::STATUS_OK) {
          file_put_contents($filePath, $content);
        }
      }
      $ret = $fileUrl;
    }
    else {
      $ret = $tempUrl;
    }
    return $ret;
  }

}
