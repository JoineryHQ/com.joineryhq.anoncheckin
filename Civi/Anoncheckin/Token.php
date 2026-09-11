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
      ->register('anoncheckinfoo', ts('Anoncheckin foo'));
  }

  public function eval(TokenValueEvent $e): void {
    return;

    foreach ($e->getRows() as $row) {
      $rowContext = $row->tokenProcessor->rowContexts[$row->tokenRow];
      /* @var TokenRow $row */
      $row->format('text/plain');
      if($cid = $rowContext['contactId']) {
//        $row->tokens('contact', 'anoncheckinfoo', "this is foo: $cid");
//        $row->tokens('participant', 'anoncheckinfoo', "this is foo: $cid");
      }
    }    
  }

}