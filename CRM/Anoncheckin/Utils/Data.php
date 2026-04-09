<?php

/**
 * Data handler. Fixme for now, improve to use actual data.
 */
class CRM_Anoncheckin_Utils_Data {
  
  public static function getSessionOptions($eventId) {
    // fixme: ignoring $eventId
    // fixme: fake sessions
    return [
      1 => 'Session 1: Lorem ipsum dolor sit amet',
      2 => 'Session 2: Sed vel orci vitae tellus maximus viverra',
      3 => 'Session 3: Ut eu leo eget eros posuere efficitur eget ut purus',
      4 => 'Session 4: Vivamus at ante scelerisque purus placerat fringilla',
      5 => 'Session 5: Cras sed ex et libero efficitur maximus vitae sit amet nibh',
    ];
  }

  public static function getParticipantSessions() {
    $storage = CRM_Anoncheckin_Utils_Session::singleton();
    return ($storage->get('fixmeSessions') ?? []);
  }
  
  public static function recordParticipantSession($participantId, $sessionId) {
    // fixme: ignoring $participantId
    $storage = CRM_Anoncheckin_Utils_Session::singleton();
    $sessions = ($storage->get('fixmeSessions') ?? []);
    $sessions[] = $sessionId;
    $storage->set('fixmeSessions', array_unique($sessions));
    // fixme: also record IP address, User-Agent, timestamp, and logged-in-contact-id (in case staff are recording by proxy/request)
  }

}
