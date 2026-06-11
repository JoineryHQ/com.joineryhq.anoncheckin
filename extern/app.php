<?php
// Wrap this in an Immediately Invoked Function Expression, just to avoid
// potentially polluting the global namespace.
(function() {

  // get cached config so we can bootstrap civicrm.
  $anoncheckinConfig = json_decode(
    file_get_contents(__DIR__ . '/cache/config.json'),
    TRUE
  );
  
  $secret = $anoncheckinConfig['hmacSecret'];

  // initialize civicrm
  require_once $anoncheckinConfig['civicrmSettingsPath'];
  require_once 'CRM/Core/Config.php';
  CRM_Core_Config::singleton();

  // declare app object
  $app = new CRM_Anoncheckin_Extern_App();
  // run the app.
  $app->run();  
})();