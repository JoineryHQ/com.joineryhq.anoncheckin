<?php

/**
 * Single-page app display and processing.
 */
class CRM_Anoncheckin_Extern_App_Default extends CRM_Anoncheckin_Extern_App {
  function run() {
    $this->addCssFile('css/Extern/App/Default.css');
    $this->addCssFile('css/Extern/qrScanner.css');
    $this->addJsFile('js/Extern/qrScanner.js');
    $this->addJsFile('js/Extern/App/Default.js');

    parent::run();
  }
}
