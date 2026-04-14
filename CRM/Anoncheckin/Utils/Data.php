<?php

/**
 * Data handler. Fixme for now, improve to use actual data.
 */
class CRM_Anoncheckin_Utils_Data {
  
  public static function getSessionOptions($eventId = NULL) {
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

  public static function getParticipantSessions($pid) {
    // fixme: ignoring pid
    $storage = CRM_Anoncheckin_Utils_Session::singleton();
    $sessions = ($storage->get('fixmeSessions') ?? []);
    return array_intersect_key(self::getSessionOptions(), array_flip($sessions));
    
  }
  
  public static function recordParticipantSession($pid, $sid) {
    // fixme: ignoring $participantId: should validate that $sid is for same event as $pid
    $storage = CRM_Anoncheckin_Utils_Session::singleton();
    $sessions = ($storage->get('fixmeSessions') ?? []);
    $sessions[] = $sid;
    $storage->set('fixmeSessions', array_unique($sessions));
    // fixme: also record IP address, User-Agent, timestamp, and logged-in-contact-id (in case staff are recording by proxy/request)
    
    // fixme: return false if saving failed.
    return TRUE;
  }

  public static function participantHasSession($pid, $sid) {
    $participantSessions = self::getParticipantSessions($pid);
    return array_key_exists($sid, $participantSessions);
  }
  
  public static function getSessionTitle($sid) {
    $sessionOptions = self::getSessionOptions();
    return $sessionOptions[$sid];
  }
  
  public static function getParticipantName($pid) {
    // fixme: stub
    if ($pid) {
      return 'Marcus Brown';
    }
  }
}
