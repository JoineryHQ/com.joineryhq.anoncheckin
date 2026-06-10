<?php

use CRM_Anoncheckin_ExtensionUtil as E;

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

  /**
   * Create a device entry.
   *
   * @param string $deviceKey
   * @param string $userAgent
   * @param string $userAgentShort
   * @param int $deviceStatusId
   * @return int Created device.id
   */
  public static function createDevice(string $deviceKey, string $userAgent, string $userAgentShort, int $deviceStatusId): int {

    $sql = "
      INSERT INTO civicrm_anoncheckin_device (
        device_key,
        user_agent,
        user_agent_short,
        device_status_id
      ) VALUES (
        %1,
        %2,
        %3,
        %4
      )
    ";

    $params = [
      1 => [$deviceKey, 'String'],
      2 => [$userAgent, 'String'],
      3 => [$userAgentShort, 'String'],
      4 => [$deviceStatusId, 'Integer'],
    ];

    CRM_Core_DAO::executeQuery($sql, $params);

    return (int) CRM_Core_DAO::singleValueQuery(
      "SELECT LAST_INSERT_ID()"
    );
  }

}
