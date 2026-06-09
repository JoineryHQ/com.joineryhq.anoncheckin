<?php

/**
 * Output utilities
 */
class CRM_Anoncheckin_Extern_Page {
  
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
