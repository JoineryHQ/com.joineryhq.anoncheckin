<?php

/**
 * Single-page app display and processing.
 */
class CRM_Anoncheckin_Extern_App {

  const SESSION_PREFIX = 'anoncheckin_extern';

  var $device = [];
  var $debugMessages = [];
  var $debug = FALSE;
  var $appUrl = '';
  
  var $cssFiles = [];
  var $jsFiles = [];
  var $cssUrls = [];
  var $jsUrls = [];
  var $setting;
  var $tpl;
  var $isFatal = FALSE;
  
  /**
   * @var CRM_Core_session Instance of CRM_Core_Session, to be scoped for our own dedicated usage.
   */
  var $_session;

  public function __construct() {
    // Create context for app session vars.
    $this->_session = CRM_Core_Session::singleton();
    $this->_session->createScope(self::SESSION_PREFIX);

    // Define smarty variable
    $this->tpl = CRM_Core_Smarty::singleton();
    
    $this->appUrl = CRM_Anoncheckin_Utils_Extern::getAppUrl();
    $this->setting = CRM_Anoncheckin_Setting::singleton();
    $this->debug = $this->setting->get('anoncheckin_debug');
    
    $deviceKey = CRM_Anoncheckin_Utils_Extern::getUserDeviceKey();
    if ($deviceKey) {
      // If user's device has been initialized, populate $this->device.
      $this->device = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectDeviceByKey', $deviceKey);
    }
    if (empty($this->device)) {
      // If $this->device is still empty, then we're in one of two situatios:
      // - user-device has never visited before and has no cookie; OR
      // - user-device has a cookie, but a corresponding deviceKey no longer
      // exists in the DB. 
      // Either way, we need to (re-)initialize this device.
      $this->device = CRM_Anoncheckin_Utils_Extern::initializeDevice();      
    }
    else {
      // We have a device; update the cookie.
      CRM_Anoncheckin_Utils_Extern::setUserDeviceKey($this->device['deviceKey']);
    }

    if ($this->device['participantId'] ?? FALSE) {
      $this->participant = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantInfo', $this->device['participantId']);
      $this->participantSessions = CRM_Anoncheckin_Utils_ExternData::cacheSelect('selectParticipantSessions', $this->device['participantId']);
    }
    
  }

  public function run() {    
    $this->print();
  }

  protected function fatal($message) {
    $this->isFatal = TRUE;
    $this->setMessage($message, 'error');
    if (!empty($this->device['deviceId'])) {
      // In odd circumstances, there may be no device, so only log if we have one.
      CRM_Anoncheckin_Utils_ExternData::insertDeviceLog($this->device['deviceId'], CRM_Anoncheckin_Utils_Extern::DEVICE_LOG_TYPE_USER, $message);
    }
    $this->print();
  }

  private function print() {
    $this->addCssFile('css/Extern/App.css');
    $this->addCssFile('[civicrm.root]/css/crm-i.css');
    $this->addCssUrl('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css');
    $this->addJsUrl('https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js');

    // Pass all input vars to template.
    foreach ($_REQUEST as $requestKey => $requestValue) {
      $this->assign($requestKey, $requestValue);
      $this->setDebugMessage("Set tpl value from REQUEST: $requestKey = $requestValue");
    }
    
    $this->assign('appUrl', $this->appUrl);
    $this->assign('isDebug', $this->debug);

    $this->assign('messages', $this->consumeMessages());
    if ($this->debug) {
      $this->assign('debugMessages', $this->debugMessages);
    }
    
    $extensionBasePath = $this->setting->get('extensionBasePath');
    $this->assign('extensionBasePath', $extensionBasePath);

    
    $this->assignAssets();

    $this->assign('appTpl', $this->getTemplate());
    
    $extPath = \Civi::resources()->getPath('com.joineryhq.anoncheckin');
    $this->tpl->display($extPath . '/templates/CRM/Anoncheckin/Extern/App.tpl');
    exit();
  }

  private function getTemplate() {
    if ($this->isFatal) {
      return 'common/Fatal.tpl';
    }
    $baseName = $this->getBaseName();
    return "App/{$baseName}.tpl";
  }

  protected function assign($name, $value) {
    $this->tpl->assign($name, $value);
  }

  protected function setDebugMessage($message) {
    $this->debugMessages[] = $message;
  }

  protected function redirectClean() {
    header('Location: '. $this->appUrl);
    exit();
  }
  
  /**
   * Add a user-facing status message for display.
   * @param String $message The message body.
   * @param String $type One of: info, error, success
   */
  protected function setMessage($message, $type = 'info') {
    $messages = $this->_session->get('messages', self::SESSION_PREFIX) ?? [];
    $messages[] = [
      'type' => $type,
      'message' => $message,
    ];
    $this->_session->set('messages', $messages, self::SESSION_PREFIX);
  }

  protected function consumeMessages() {
    $messages = $this->_session->get('messages', self::SESSION_PREFIX) ?? [];
    $this->_session->set('messages', [], self::SESSION_PREFIX);
    return $messages;
  }
  
  /**
   * Specify a CSS file to be included
   * @param string $path Path to css file.
   *  If begins with [, we assume it's beginning with a civicrm path variable such as [civicrm.root]
   *  Otherwise, we assume it's relative to 'extensionBasePath' setting.
   * @param int $weight
   */
  protected function addCssFile(string $path, int $weight = 0) {
    if (substr($path, 0, 1) == '[') {
      $path = \Civi::paths()->getPath($path);
    }
    else {
      $extensionBasePath = $this->setting->get('extensionBasePath');
      $path = "{$extensionBasePath}". DIRECTORY_SEPARATOR . "$path";
    }
    $this->cssFiles[] = [
      'path' => $path,
      'weight' => $weight,
    ];
  }
  
  /**
   * Specify a JS file to be included
   * @param string $path Path to js file.
   *  If begins with DIRECTORY_SEPARATOR, we assume it's a full path
   *  Otherwise, we assume it's relative to 'extensionBasePath' setting.
   * @param int $weight
   */
  protected function addJsFile(string $path, int $weight = 0) {
    if (substr($path, 0, 1) != DIRECTORY_SEPARATOR) {
      $extensionBasePath = $this->setting->get('extensionBasePath');
      $path = "{$extensionBasePath}". DIRECTORY_SEPARATOR . "$path";
    }
    $this->jsFiles[] = [
      'path' => $path,
      'weight' => $weight,
    ];
  }
  
  /**
   * Specify a CSS url to be included
   * @param string $url URL to css file. Passed as first parameter to Civi::paths()->getUrl();
   * @param string $preferFormat 'relative' or 'absolute'. Passed as second parameter to Civi::paths()->getUrl();
   * @param ssl $ssl NULL to autodetect. TRUE to force to SSL. Passed as third parameter to Civi::paths()->getUrl();
   * @param int $weight relative order for placement in html <head>
   */
  protected function addCssUrl($url, $preferFormat = 'relative', $ssl = NULL, int $weight = 0) {
    $url = Civi::paths()->getUrl($url, $preferFormat, $ssl);
    $this->cssUrls[] = [
      'url' => $url,
      'weight' => $weight,
    ];
  }
  /**
   * Specify a JS url to be included
   * @param string $url URL to js file. Passed as first parameter to Civi::paths()->getUrl();
   * @param string $preferFormat 'relative' or 'absolute'. Passed as second parameter to Civi::paths()->getUrl();
   * @param ssl $ssl NULL to autodetect. TRUE to force to SSL. Passed as third parameter to Civi::paths()->getUrl();
   * @param int $weight relative order for placement in html <head>
   */
  protected function addJsUrl($url, $preferFormat = 'relative', $ssl = NULL, int $weight = 0) {
    $url = Civi::paths()->getUrl($url, $preferFormat, $ssl);
    $this->jsUrls[] = [
      'url' => $url,
      'weight' => $weight,
    ];
  }
  
  /**
   * Assign to template all js/css assets (files and urls)
   */
  private function getBaseName() {
    $myClass = get_class($this);
    return (array_pop(explode('_', $myClass)));
  }
  
  /**
   * Assign to template all js/css assets (files and urls)
   */
  private function assignAssets() {
    // css files
    $cssFilesContent = '';
    $cssFiles = CRM_Utils_Array::asort($this->cssFiles, 'weight');
    foreach ($cssFiles as $cssFile) {
      if (
        !empty($cssFile['path'])
        && file_exists($cssFile['path'])
      ) {
        $cssFilesContent .= "<!-- contents of " . basename($cssFile['path']) . " ... -->\n";
        $cssFilesContent .= '<style>' . file_get_contents($cssFile['path']). '</style>';
      }
    }
    $this->assign('cssFilesContent', $cssFilesContent);
  
    // js files
    $jsFilesContent = '';
    $jsFiles = CRM_Utils_Array::asort($this->jsFiles, 'weight');
    foreach ($jsFiles as $jsFile) {
      if (
        !empty($jsFile['path'])
        && file_exists($jsFile['path'])
      ) {
        $jsFilesContent .= "<!-- contents of " . basename($jsFile['path']) . " ... -->\n";
        $jsFilesContent .= '<script>' . file_get_contents($jsFile['path']). '</script>';
      }
    }
    $this->assign('jsFilesContent', $jsFilesContent);
    
    // css URLs
    $cssUrlsContent = '';
    $cssUrls = CRM_Utils_Array::asort($this->cssUrls, 'weight');
    foreach ($cssUrls as $cssUrl) {
      if (!empty($cssUrl['url'])) {
        $cssUrlsContent .= '<link rel="stylesheet" href="' . $cssUrl['url']. '" media="all">';
      }
    }
    $this->assign('cssUrlsContent', $cssUrlsContent);
  
    // js files
    $jsUrlsContent = '';
    $jsUrls = CRM_Utils_Array::asort($this->jsUrls, 'weight');
    foreach ($jsUrls as $jsUrl) {
      if (!empty($jsUrl['url'])) {
        $jsUrlsContent .= '<script src="' . $jsUrl['url'] . '"></script>';
      }
    }
    $this->assign('jsUrlsContent', $jsUrlsContent);
  }
  
}
