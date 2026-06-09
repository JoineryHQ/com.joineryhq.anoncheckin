<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility method to handle configs for extern scripts.
 */
class CRM_Anoncheckin_Utils_Config {

  /**
   * Get the full path to config file directory.
   * @return String
   */
  private static function getConfigFileDir() {
    $extPath = \Civi::resources()->getPath('com.joineryhq.anoncheckin');
    return "{$extPath}/extern/cache";
  }

  /**
   * Write the config file (conditionally, if $force == false)
   * @param Bool $force Should we re-create the file if it already exists?
   * @throws CRM_Core_Exception
   */
  public static function writeConfigFile($force = FALSE) {
    $dir = self::getConfigFileDir();
    $filePath = "{$dir}/config.json";
    if (file_exists($filePath) && !$force) {
      // File exists, and we're not forcing update, so just return.
      return;
    }
    $config = [
      'civicrmSettingsPath' => CIVICRM_SETTINGS_PATH,
      'hmacSecret' => Civi::settings()->get('anoncheckin_hmac_secret'),
    ];
    $json = json_encode($config);
    try {
      $tmpNam = tempnam($dir, 'config_temp_');
      file_put_contents($tmpNam, $json);
      rename($tmpNam, $filePath);
    }
    catch (Exception $e) {
      throw new CRM_Core_Exception('Error updating config.json file for extension "' . E::SHORT_NAME . '". Error message was: ' . $e->getMessage());
    }
  }

  public static function createHmacSecretIfEmpty() {
    if (!Civi::settings()->get('anoncheckin_hmac_secret')) {
      Civi::settings()->set(
        'anoncheckin_hmac_secret',
        bin2hex(random_bytes(32))
      );
    }
  }

}
