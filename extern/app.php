<?php
// Wrap this in an Immediately Invoked Function Expression, just to avoid
// potentially polluting the global namespace.
(function() {
  set_exception_handler(function (Throwable $e) {
    print "An unexpected error occurred. Error message was: ". $e->getMessage();
    exit;
  });  

  function getCachedConfig() {
    // get cached config so we can bootstrap civicrm.
    $anoncheckinConfig = json_decode(
      file_get_contents(__DIR__ . '/cache/config.json'),
      TRUE
    );  
    return $anoncheckinConfig;
  }

  $anoncheckinConfig = getCachedConfig();
  
  // initialize civicrm
  if (!defined('CIVICRM_SETTINGS_PATH')) {                                                                                            
    define('CIVICRM_SETTINGS_PATH', $anoncheckinConfig['civicrmSettingsPath']);
  }
  require_once CIVICRM_SETTINGS_PATH;
  require_once 'CRM/Core/Config.php';
  CRM_Core_Config::singleton();

  // We needed the above-loaded cached config, because without it we can't do anything
  // (it contains the path to civicrm.settings.php). But it may be old.
  // Rebuild it if it's expired, and (if it was expired) reload it.
  $updated = CRM_Anoncheckin_Utils_Config::refreshConfigFile();
  if ($updated) {
    $anoncheckinConfig = getCachedConfig();
  }
  
  // Initialize settings handler with our cached config.
  CRM_Anoncheckin_Setting::singleton($anoncheckinConfig);

  // declare app object
  $app = new CRM_Anoncheckin_Extern_App();
  // run the app.
  $app->run();  
})();