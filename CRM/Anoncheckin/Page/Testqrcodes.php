<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

class CRM_Anoncheckin_Page_Testqrcodes extends CRM_Core_Page {

  public function run() {
    // fixme: use real sessions for some given event.
    $sessionTitles = [
      1 => 'Session 1: Lorem ipsum dolor sit amet',
      2 => 'Session 2: Sed vel orci vitae tellus maximus viverra',
      3 => 'Session 3: Ut eu leo eget eros posuere efficitur eget ut purus',
      4 => 'Session 4: Vivamus at ante scelerisque purus placerat fringilla',
      5 => 'Session 5: Cras sed ex et libero efficitur maximus vitae sit amet nibh',
    ];
    

    $query = "select p.id from civicrm_participant p inner join civicrm_contact c on c.id = p.contact_id where c.contact_type = 'individual' and c.id > 500 limit 1";
    $pid = CRM_Core_DAO::singleValueQuery($query);
    $indivAppUrl = $this->getAppUrl(['p' => $pid, 'h' => CRM_Anoncheckin_Utils_Value::generateSignature($pid)]);
    $indivQrUrl = $this->getQrImageUrl($indivAppUrl, '0B3D91');
    $this->assign('indivAppUrl', $indivAppUrl);
    $this->assign('indivQrUrl', $indivQrUrl);

    $sessionUrls = [];
    foreach ($sessionTitles as $sessionId => $sessionTitle) {
      $appUrl = $this->getAppUrl(['s' => $sessionId, 'h' => CRM_Anoncheckin_Utils_Value::generateSignature($sessionId)]);
      $sessionUrls[] = [
        'title' => $sessionTitle,
        'app' => $appUrl,
        'qr'  => $this->getQrImageUrl($appUrl, '1B5E20')
      ];
    }
    $this->assign('sessionUrls', $sessionUrls);
    parent::run();
  }


  private function getAppUrl($params = []) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $base = CRM_Anoncheckin_Utils_Extern::getAppUrl();
    if (!empty($params)) {
      $url = $base . '?' . http_build_query($params);
    }
    else {
      $url = $base;
    }
    
    return $url;
    
  }

  private function getQrImageUrl($dataUrl, $color = 'black') {
    $ret = 'https://api.qrserver.com/v1/create-qr-code/?color='. $color .'&size=300&data=' . $dataUrl;
    return $ret;
  }
  
}
