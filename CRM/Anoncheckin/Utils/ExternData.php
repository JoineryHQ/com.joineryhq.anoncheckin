<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Direct-SQL data handling for performance-optimized extern scripts.
 */
class CRM_Anoncheckin_Utils_ExternData {

  public static function getParticipantInfo(int $pid): ?array {
    $sql = "
      SELECT c.display_name, p.event_id
      FROM civicrm_participant p
      INNER JOIN civicrm_contact c
        ON c.id = p.contact_id
      WHERE p.id = %1
    ";
    $params = [1 => [$pid, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if (!$dao->fetch()) {
      return NULL;
    }

    $ret = [];

    foreach ($dao->toArray() as $key => $value) {
      $ret[self::snakeToCamel($key)] = $value;
    }

    return $ret;

  }

  /**
   * FIXME: untested
   * @param int $pid
   * @return array
   */
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
  public static function createDevice(string $deviceKey, int $participantId, string $userAgent, string $userAgentShort, int $deviceStatusId): int {

    $sql = "
      INSERT INTO civicrm_anoncheckin_device (
        device_key,
        participant_id,
        user_agent,
        user_agent_short,
        device_status_id
      ) VALUES (
        %1,
        %2,
        %3,
        %4,
        %5
      )
    ";

    $params = [
      1 => [$deviceKey, 'String'],
      2 => [$participantId, 'String'],
      3 => [$userAgent, 'String'],
      4 => [$userAgentShort, 'String'],
      5 => [$deviceStatusId, 'Integer'],
    ];

    CRM_Core_DAO::executeQuery($sql, $params);

    return (int) CRM_Core_DAO::singleValueQuery(
      "SELECT LAST_INSERT_ID()"
    );
  }

  /**
   * Get all properties of a device for a given deviceKey.
   *
   * @param string $deviceKey
   * @return array|null
   */
  public static function getDeviceByKey(string $deviceKey): ?array {

    $sql = "
      SELECT *
      FROM civicrm_anoncheckin_device
      WHERE device_key = %1
    ";

    $params = [
      1 => [$deviceKey, 'String'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if (!$dao->fetch()) {
      return NULL;
    }

    $ret = [];

    foreach ($dao->toArray() as $key => $value) {
      $ret[self::snakeToCamel($key)] = $value;
    }

    return $ret;
  }  
  
  public static function getLockedDeviceByPid(string $pid): ?array {

    $sql = "
      SELECT *
      FROM civicrm_anoncheckin_device
      WHERE participant_id = %1
        AND device_status_id = %2
    ";

    $params = [
      1 => [$pid, 'Int'],
      2 => [CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_LOCKED, 'Int'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if (!$dao->fetch()) {
      return NULL;
    }

    $ret = [];

    foreach ($dao->toArray() as $key => $value) {
      $ret[self::snakeToCamel($key)] = $value;
    }

    return $ret;
  }
  
  public static function snakeToCamel(string $value): string {
    $parts = explode('_', $value);
    $first = array_shift($parts);

    return $first . implode('', array_map('ucfirst', $parts));
  }  
}
