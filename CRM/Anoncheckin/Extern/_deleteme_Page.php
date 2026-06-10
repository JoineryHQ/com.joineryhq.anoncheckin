<?php

/**
 * Output utilities
 */
class CRM_Anoncheckin_Extern_Page {
  
  public function run() {
    $this->validateInput();
    foreach (['s', 'p'] as $actionKey) {
      if ($_REQUEST[$actionKey] ?? '') {
        break;
      }
    }
    $method = strtolower($_SERVER['REQUEST_METHOD']);
    $functionName = "action_{$method}_{$actionKey}";
    if (is_callable([$this, $functionName])) {
      $this->$functionName();
    }
    else {
      $this->fatal("Invalid action, attemped: ". $functionName);
    }
    
  }

  /**
   * Action: User has scanned a badge; prompt user for lock-in.
   */
  private function action_get_p() {
    $this->fatal(__METHOD__ . 'is not ready.');    
  }

  /**
   * Action: User has scanned a session code; prompt user to confirm session.
   */
  private function action_get_s() {
    $this->fatal(__METHOD__ . 'is not ready.');    
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
    // fixme: stub
    return __DIR__ . '/fixmeTpl/Page.tpl';
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
