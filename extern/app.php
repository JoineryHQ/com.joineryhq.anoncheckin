<?php
// Wrap this in an Immediately Invoked Function Expression, just to avoid
// potentially polluting the global namespace.
(function() {

  // get cached config so we can bootstrap civicrm.
  $anoncheckinConfig = json_decode(
    file_get_contents(__DIR__ . '/cache/config.json'),
    TRUE
  );

  // initialize civicrm
  require_once $anoncheckinConfig['civicrmSettingsPath'];
  require_once 'CRM/Core/Config.php';
  CRM_Core_Config::singleton();

  $config = CRM_Anoncheckin_Setting::singleton($anoncheckinConfig);

  // declare app object
  $app = new CRM_Anoncheckin_Extern_App($anoncheckinConfig);
  // run the app.
  $app->run();  
})();