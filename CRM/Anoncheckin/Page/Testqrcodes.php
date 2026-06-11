<?php
declare(strict_types = 1);

use CRM_Anoncheckin_ExtensionUtil as E;

class CRM_Anoncheckin_Page_Testqrcodes extends CRM_Core_Page {

  public function run() {
    $eventId = 113;

    $sessions = [];
    $query = "
      select s.id, s.title 
      from civicrm_anoncheckin_session s 
        inner join civicrm_anoncheckin_session_group sg on sg.id = s.session_group_id
      where
        sg.event_id = %1
      order by 
        sg.start_datetime_utc, s.title
    ";
    $queryParams = [
      1 => [$eventId, 'Int'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $sessions[$dao->id] = $dao->title;
    }
    
    $badgeQrColor = '0B3D91';
    $sessionQrColor = '1B5E20';

    $indivUrls = [];
    $query = "select p.id as pid, p.contact_id as cid, c.display_name from civicrm_participant p inner join civicrm_contact c on c.id = p.contact_id where p.event_id = %1 and c.contact_type = 'individual' and c.id > 500 limit 1";
    $queryParams = [
      1 => [$eventId, 'Int'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $dao->fetch();
    $participant = $dao->toArray();
    $pid = $participant['pid'];
    $cid = $participant['cid'];
    $displayName = $participant['display_name'];
    $indivAppUrl = $this->getAppUrl(['p' => $pid, 'ph' => CRM_Anoncheckin_Utils_Value::generateSignature($pid)]);
    $indivUrls[] = [
      'title' => "$displayName ($pid)",
      'app' => $indivAppUrl,
      'qr'  => CRM_Anoncheckin_Utils_Qr::getQrImageUrl($indivAppUrl, $badgeQrColor)
    ];
    $query = "select p.id as pid, p.contact_id as cid, c.display_name from civicrm_participant p inner join civicrm_contact c on c.id = p.contact_id where p.event_id = %1 and c.contact_type = 'individual' and c.id > 500 and c.id != %2 limit 1";
    $params = [
      1 => [$eventId, 'String'],
      2 => [$cid, 'String'],
    ];    
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $dao->fetch();
    $participant = $dao->toArray();
    $pid = $participant['pid'];
    $cid = $participant['cid'];
    $displayName = $participant['display_name'];
    $indivAppUrl = $this->getAppUrl(['p' => $pid, 'ph' => CRM_Anoncheckin_Utils_Value::generateSignature($pid)]);
    $indivUrls[] = [
      'title' => "$displayName ($pid)",
      'app' => $indivAppUrl,
      'qr'  => CRM_Anoncheckin_Utils_Qr::getQrImageUrl($indivAppUrl, $badgeQrColor)
    ];
    $this->assign('indivUrls', $indivUrls);

    $sessionUrls = [];
    foreach ($sessions as $sessionId => $sessionTitle) {
      $appUrl = $this->getAppUrl(['s' => $sessionId, 'sh' => CRM_Anoncheckin_Utils_Value::generateSignature($sessionId)]);
      $sessionUrls[] = [
        'title' => $sessionTitle,
        'app' => $appUrl,
        'qr'  => CRM_Anoncheckin_Utils_Qr::getQrImageUrl($appUrl, $sessionQrColor)
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

}
