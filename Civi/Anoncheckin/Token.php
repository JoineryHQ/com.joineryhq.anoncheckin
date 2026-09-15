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
      ->register('anoncheckinqr', ts('Anoncheckin QR code (name badges only)'));
  }

  public function eval(TokenValueEvent $e): void {
    // This token returns nothing. It's used only in hook_civicrm_alterBadge().
    return;
  }

}