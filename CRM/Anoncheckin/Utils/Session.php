<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Utility methods for anoncheckin_session entity.
 */
class CRM_Anoncheckin_Utils_Session {
  
  /**
   * Does the given event have anoncheckin_sessions?
   * 
   * @staticvar array $cache
   * @param int $eventId
   * @return bool
   */
  public static function eventHasSessions(int $eventId): bool {
    static $cache;
    if (!isset($cache[$eventId])) {
      $cache[$eventId] = FALSE;
      $sessionGroups = \Civi\Api4\AnoncheckinSessionGroup::get()
        ->addWhere('event_id', '=', $eventId)
        ->addChain('sessions', \Civi\Api4\AnoncheckinSession::get()
          ->addWhere('session_group_id', '=', '$id')
        )
        ->execute();
      foreach ($sessionGroups as $sessionGroup) {
        if (!empty($sessionGroup['sessions'])) {
          $cache[$eventId] = TRUE;
          break;
        }
      }
    }
    return $cache[$eventId];
  }

}
