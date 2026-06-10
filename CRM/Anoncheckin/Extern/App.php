<?php

/**
 * Single-page app display and processing.
 */
class CRM_Anoncheckin_Extern_App {
  
  var $deviceKey = '';
  var $participantName = '';
  
  public function __construct() {
    
  }

  public function run() {
    $this->validateInput();
    $actionFunctionName = "action_default";
    foreach (['s', 'p'] as $actionKey) {
      if ($_REQUEST[$actionKey] ?? '') {
        $actionValue = $_REQUEST[$actionKey];
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $actionFunctionName = "action_{$method}_{$actionKey}";
        break;
      }
    }
    if (!empty($actionFunctionName) && is_callable([$this, $actionFunctionName])) {
      $this->$actionFunctionName($actionValue);
    }
    else {
      $this->fatal("Invalid action, attemped: ". $actionFunctionName);
    }
    
    $this->assign('action', $actionFunctionName);
    $this->assign('participantName', $this->participantName);
  }

  /**
   * Action: Fallback. E.g. app url loaded with no query params
   */
  private function action_default() {
    // At present we do nothing here.
  }

  /**
   * Action: User has scanned a badge; prompt user for lock-in.
   */
  private function action_get_p($p) {
    // is this device locked? fixme: assume no for now, but need to check.
    // is it locked to the given pid, or to some other? fixme: assume no for now, but need to check.
    
    // fixme: we're assuming device is not locked.
    $this->deviceKey = CRM_Anoncheckin_Utils_Device::initializeDevice($p);
    $this->participantName = CRM_Anoncheckin_Utils_ExternData::getParticipantName($p);
  }

  /**
   * Action: User has scanned a session code; prompt user to confirm session.
   */
  private function action_get_s() {
    $this->fatal(__METHOD__ . ' is not ready.');    
  }

  private function validateInput() {
    foreach (['s', 'p'] as $varName) {
      if ($_REQUEST[$varName] && !CRM_Anoncheckin_Utils_Value::validateValue($_REQUEST[$varName], $_REQUEST['h'])) {
        $this->fatal('Invalid input: '. $varName);
      }
    }
    
  }
  public function fatal($message) {
    // fixme: stub
    CRM_Anoncheckin_Utils_Session::singleton()->setMessage($message, 'error');
    $this->print();
  }

  public function print() {
    $tpl = CRM_Core_Smarty::singleton();
    $messages = CRM_Anoncheckin_Utils_Session::singleton()->consumeMessages();
    $config = CRM_Core_Config::singleton();
    $tpl->assign('userFrameworkResourceURL', $config->userFrameworkResourceURL);
    $tpl->assign('messages', $messages);
    $tpl->display($this->getTemplate());
    exit();
  }
  
  private function getTemplate() {
    return __DIR__ . '/templates/App.tpl';
  }
  
  public function ts($str, $params = []) {
    $tr = [];
    foreach ($params as $paramKey => $paramVal) {
      $tr['%' . $paramKey] = $paramVal;
    }
    return (strtr($str, $tr));
  }
  
  public function assign($name, $value) {
    CRM_Core_Smarty::singleton()->assign($name, $value);
  }
}
