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
   * Write the config file (conditionally, if $force == false; else unconditionally)
   * "Conditionally" means: if the file does not exist, or if it's more than 10
   * minutes old.
   *
   * @param Bool $force Should we re-create the file if it already exists?
   * 
   * @return boolean True if file was re-written; otherwise false.
   *
   * @throws CRM_Core_Exception
   */
  public static function refreshConfigFile($force = FALSE) {
    // We're about to write to the config file, so we must ensure config is correct.
    // An empty hmac secret here could break things badly.
    self::createHmacSecretIfEmpty();

    $dir = self::getConfigFileDir();
    $filePath = "{$dir}/config.json";

    if (
    // We're not forcing
      !$force
      // The file exists
      && file_exists($filePath)
      // The file is less than 10 minutes old.
      && filemtime($filePath) >= (time() - 600)
    ) {
      // File exists, is not expired, and we're not forcing update, so just return.
      return FALSE;
    }

    // Start with some useful civicrm settings, paths, etc.
    $config = [
      'civicrmSettingsPath' => CIVICRM_SETTINGS_PATH,
      'extensionBaseUrl' => E::url(),
      'extensionBasePath' => E::path(),
      'userFrameworkResourceURL' => Civi::settings()->get('userFrameworkResourceURL'),
    ];

    // Add all of our own settings values.
    $extensionSettings = require E::path('settings/Anoncheckin.setting.php');
    $extensionSettingsKeys = array_keys($extensionSettings);
    foreach ($extensionSettingsKeys as $extensionSettingsKey) {
      $config[$extensionSettingsKey] = Civi::settings()->get($extensionSettingsKey);
    }

    $json = json_encode($config);
    try {
      $tmpNam = tempnam($dir, 'config_temp_');
      file_put_contents($tmpNam, $json);
      rename($tmpNam, $filePath);
    } catch (Exception $e) {
      throw new CRM_Core_Exception('Error updating config.json file for extension "' . E::SHORT_NAME . '". Error message was: ' . $e->getMessage());
    }
    
    return TRUE;
  }

  private static function createHmacSecretIfEmpty() {
    if (!Civi::settings()->get('anoncheckin_hmac_secret')) {
      Civi::settings()->set(
        'anoncheckin_hmac_secret',
        bin2hex(random_bytes(32))
      );
    }
  }

}
