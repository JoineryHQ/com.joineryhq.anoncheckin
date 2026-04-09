<?php

/**
 * Session storage.
 */
class CRM_Anoncheckin_Utils_Session {

  const PREFIX = 'anoncheckin';

  private static $_singleton;

  private $_session;

  private function __construct() {
    $this->_session = CRM_Core_Session::singleton();
  }

  /**
   * Singleton function used to manage this object.
   *
   * @return CRM_Core_Session
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Anoncheckin_Utils_Session();
    }
    return self::$_singleton;
  }

  public function getExpiryMinutes($name) {
    $expiryMinutes = [
      // fixme: make this a setting.
      'sid' => 5,
    ];
    return $expiryMinutes[$name];
  }
  
  public function set($name, $value) {
    $methodName = "set_$name";
    if (method_exists($this, $methodName)) {
      $this->$methodName($value);
    }
    $this->_session->set($name, $value, self::PREFIX);
  }

  public function get($name) {
    $methodName = "get_$name";
    if (method_exists($this, $methodName)) {
      return $this->$methodName();
    }
    return $this->_session->get($name, self::PREFIX);
  }

  public function reset() {
    $this->_session->resetScope(self::PREFIX);
  }

  public function getAll() {
    return $this->_session->get(self::PREFIX);
  }

  private function set_sid($value) {
    $this->_session->set('sid', $value, self::PREFIX);
    if ($value) {
      $this->_session->set('sid_timestamp', time(), self::PREFIX);
    }
    else {
      $this->_session->set('sid_timestamp', NULL, self::PREFIX);
    }
  }
  
  private function get_sid() {
    $timestamp = $this->_session->get('sid_timestamp', self::PREFIX);
    // Pad expiry by 30 seconds (we've probably told users the time in minutes; this gives them some grace).
    $expirySeconds = ($this->getExpiryMinutes('sid') * 60 + 30);
    if ($timestamp) {
      $age = (time() - $timestamp);
      if ($age < $expirySeconds) {
        return $this->_session->get('sid', self::PREFIX);
      }
    }
    return NULL;
  }

}
