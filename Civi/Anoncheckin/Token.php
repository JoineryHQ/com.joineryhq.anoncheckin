<?php

namespace Civi\Anoncheckin;

use Civi\Core\Service\AutoSubscriber;
use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenValueEvent;
class Token extends AutoSubscriber {

  public static function getSubscribedEvents() {
    return [
      'civi.token.list' => 'list',
      'civi.token.eval' => 'eval',
    ];
  }

  public function list(TokenRegisterEvent $e) {
    $e->entity('contact')
      ->register('anoncheckin_self_unlock_link', ts('Anoncheckin self-service unlock link: url'))
      ->register('anoncheckin_self_unlock_expire_time', ts('Anoncheckin self-service unlock link: expiration time)'));
  }

  public function eval(TokenValueEvent $e): void {
    $activeTokens = $e->getTokenProcessor()->getMessageTokens();    
    foreach ($e->getRows() as $row) {
      $row->format('text/html');
      if (in_array('anoncheckin_self_unlock_link', $activeTokens['contact'])) {
        $pid = \Civi::$statics['anoncheckin_self_unlock_link_pid'];
        $row->tokens('contact', 'anoncheckin_self_unlock_link', \CRM_Anoncheckin_Extern_App_SelfUnlock::generateApplyLink($pid));
      }
      if (in_array('anoncheckin_self_unlock_expire_time', $activeTokens['contact'])) {
        $ttlMinutes = \CRM_Anoncheckin_Utils_Settings::get('anoncheckin_self_unlock_link_ttl');
        $tokenValue = "{$ttlMinutes} minutes";
        $ufTimeZone = \CRM_Anoncheckin_Utils_Settings::get('anoncheckin_timezone');
        if ($ufTimeZone) {
          $dateTime = new \DateTime("+{$ttlMinutes} minutes");
          $timeZone = new \DateTimeZone($ufTimeZone);
          $dateTime->setTimezone($timeZone);
          $tokenValue .= ' (' . $dateTime->format('F jS \a\t g:i A, T') . ')';
        }
        
        $row->tokens('contact', 'anoncheckin_self_unlock_expire_time', $tokenValue);
      }
    }
  }

}
