<?php

class CRM_Anoncheckin_Setting {
  
  var $config = [];
  var $cachedOnly;
  
  private function __construct(array $config = NULL) {
    if (is_array($config)) {
      $this->config = $config;
    }
    $this->cachedOnly = $cachedOnly;
  }

  /**
   * Create and return a singleton instance of this class.
   * @param Array $config A set of config values
   * @return object
   */
  public static function singleton(array $config = NULL) {
    static $singleton;
    if (!isset($singleton)) {
      $singleton = new CRM_Anoncheckin_Setting($config);
    }
    return $singleton;
  }
  
  public function get($settingName) {
    if ($this->cachedOnly) {
      if (array_key_exists($settingName, $this->config)) {
        return $this->config[$settingName];
      }
      else {
        throw new CRM_Core_Exception("The setting '$settingName' is unavailagle in a cachedOnly context.");
      }
    }
    else {
      return \Civi::settings()->get($settingName) ?? $this->config[$settingName] ?? NULL;
    }
  }
}