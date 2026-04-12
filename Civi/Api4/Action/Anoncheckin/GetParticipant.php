<?php

namespace Civi\Api4\Action\Anoncheckin;

use Civi\Api4\Generic\AbstractAction;
use \Civi\Api4\Generic\Result;

class GetParticipant extends AbstractAction {


  /**
   * Participant ID.
   *
   * @required
   *
   * @var int
   */
  protected $pid;

  public function _run(Result $result) {
    if (empty($this->pid)) {
      throw new \API_Exception('Missing pid');
    }

    $participantGet = \Civi\Api4\Participant::get()
      ->setCheckPermissions(FALSE)
      ->setLimit(1)
      ->addSelect('id', 'contact_id', 'event_id', 'status_id')
      ->addWhere('id', '=', $this->pid)
      ->addChain('contact', \Civi\Api4\Contact::get(TRUE)
        ->addWhere('id', '=', '$contact_id')
      )
      ->execute()
      ->first();

    $values = ['display_name' => $participantGet['contact'][0]['display_name']];
    $result->exchangeArray($values);
  }

  /**
   * Declare ad-hoc field list for this action.
   *
   * Some actions return entirely different data to the entity's "regular" fields.
   *
   * This is a convenient alternative to adding special logic to our GetFields function to handle this action.
   *
   * @return array
   */
  public static function fields() {
    return [
      [
        'name' => 'pid', 
        'data_type' => 'Integer',
        'title' => 'Participant ID',
      ],
    ];
  }  
}
