<?php

/**
 * Direct-SQL data handling for performance-optimized extern scripts.
 */
class CRM_Anoncheckin_Utils_ExternData {
  
public static function getParticipantName(int $pid): ?string {
  $sql = "
    SELECT c.display_name
    FROM civicrm_participant p
    INNER JOIN civicrm_contact c
      ON c.id = p.contact_id
    WHERE p.id = %1
  ";
  $params = [1 => [$pid, 'Integer']];
  
  return CRM_Core_DAO::singleValueQuery(
    $sql,
    $params
  );
}

public static function getParticipantSessions(int $pid): array {
  $sql = "
    SELECT s.title
    FROM civicrm_anoncheckin_session_participant sp
    INNER JOIN civicrm_anoncheckin_session s
      ON s.id = sp.session_id
    INNER JOIN civicrm_anoncheckin_session_group sg
      ON sg.id = s.session_group_id
    WHERE sp.participant_id = %1
    ORDER BY
      sg.weight,
      s.weight
  ";

  $params = [
    1 => [$pid, 'Integer'],
  ];

  $dao = CRM_Core_DAO::executeQuery($sql, $params);

  $sessions = [];
  while ($dao->fetch()) {
    $sessions[] = $dao->title;
  }

  return $sessions;
}

}
