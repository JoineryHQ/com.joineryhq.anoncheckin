<?php

use CRM_Anoncheckin_ExtensionUtil as E;

/**
 * Direct-SQL data handling for performance-optimized extern scripts.
 */
class CRM_Anoncheckin_Utils_ExternData {

  private static $cache = [];
  
  /**
   * Get recorded sessions for this participant.
   * @param Int $pid Participant ID.
   * @return Array One array member per session, with keyed properties for each. Empty array if none found.
   */
  public static function selectParticipantSessions(int $pid): ?array {
    $ret = [];
    $sql = "
      SELECT s.title, s.id as session_id, sp.id as session_participant_id, sp.*
      FROM civicrm_anoncheckin_session_participant sp
        INNER JOIN civicrm_anoncheckin_session s ON s.id = sp.session_id
        INNER JOIN civicrm_anoncheckin_session_group sg ON sg.id = s.session_group_id
      WHERE sp.participant_id = %1
      ORDER BY sg.start_datetime_utc, s.title
    ";
    $params = [1 => [$pid, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    while ($dao->fetch()) {
      $ret[] = self::rowToArray($dao->toArray());
    }

    return $ret;
  }

  /**
   * Get relevant info for a given participant ID
   * 
   * @param int $pid Participant ID
   * @return array|null If participant exists, an array of attributes; otherwise NULL
   */
  public static function selectParticipantInfo(int $pid): ?array {
    $sql = "
      SELECT c.display_name, p.event_id, e.title as event_title, p.id as participant_id
      FROM civicrm_participant p
        INNER JOIN civicrm_contact c
          ON c.id = p.contact_id
        INNER JOIN civicrm_event e on e.id = p.event_id
      WHERE p.id = %1
    ";
    $params = [1 => [$pid, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if (!$dao->fetch()) {
      return NULL;
    }

    $ret = self::rowToArray($dao->toArray());

    return $ret;

  }

  public static function selectSessionInfo(int $session_id): ?array {
    $sql = "
      SELECT s.title, s.id as session_id, sg.start_datetime_utc, sg.end_datetime_utc, sg.timezone, sg.event_id, s.session_group_id
      FROM civicrm_anoncheckin_session s
        INNER JOIN civicrm_anoncheckin_session_group sg ON sg.id = s.session_group_id
      WHERE s.id = %1
    ";
    $params = [1 => [$session_id, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if (!$dao->fetch()) {
      return NULL;
    }

    $ret = self::rowToArray($dao->toArray());

    return $ret;

  }

  /**
   * Update the properties for a device entry.
   *
   * @param string $deviceKey Value of _device.device_key (Notice: that's key, not id)
   * @param array $deviceParams Device attributes, keyed to camelCase attribute names.
   *
   * @return int Number of affected rows -- should be either 1 or 0, since $deviceKey is required and unique.
   */
  public static function updateDevice(string $deviceKey, array $deviceParams): int {
    $sets = [];
    $queryParams = [];
    $set_counter = 1;
    foreach ($deviceParams as $deviceParamKey => $deviceParamValue) {
      $columnName = self::camelToSnake($deviceParamKey);
      $sets[] = "$columnName = %{$set_counter}";
      // We're going to treat all values as strings here. Yes, some are ints,
      // but string will work fine (until we hit a fatal sql query error, but that's
      // just as fatal as DAO's "value was not of type Int").
      $queryParams[$set_counter] = [$deviceParamValue, 'String'];
      $set_counter++;
    }
    $query = "
      UPDATE civicrm_anoncheckin_device
      SET
    "
    . implode(', ', $sets)
    . "
      WHERE device_key = %{$set_counter}
    ";
    $queryParams[$set_counter] = [$deviceKey, 'String'];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $ret = $dao->affectedRows();
    
    if ($ret) {
      // We've changed rows, so future calls to some methods should refresh cache.
      self::cacheSelectClearActionArg('selectDeviceByKey', $deviceKey);
      if (!empty($device['participantId'])) {
        self::cacheSelectClearActionArg('selectLockedDeviceByPid', $device['participantId']);
      }
    }
    return $ret;
  }

  /**
   * Extend the `expires` time for a given device, per CRM_Anoncheckin_Utils_Device::calculateExpiresTimestamp(),
   * if that device is not already expired.
   *
   * @param string $deviceKey
   */
  public static function extendDeviceExpires(string $deviceKey): void {
    $expiresTimestamp = CRM_Anoncheckin_Utils_Device::calculateExpiresTimestamp();
    $query = "
      UPDATE civicrm_anoncheckin_device
      SET expires = %1
      WHERE device_key = %2
        AND expires > unix_timestamp()
    ";
    $params = [
      1 => [$expiresTimestamp, 'Integer'],
      2 => [$deviceKey, 'String'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
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
  public static function insertDevice(string $deviceKey, string $userAgent, string $userAgentShort, int $deviceStatusId): int {
    $sql = "
      INSERT INTO civicrm_anoncheckin_device (
        device_key,
        user_agent,
        user_agent_short,
        device_status_id,
        expires
      ) VALUES (
        %1,
        %2,
        %3,
        %4,
        %5
      )
    ";

    $expiresTimestamp = CRM_Anoncheckin_Utils_Device::calculateExpiresTimestamp();
    $params = [
      1 => [$deviceKey, 'String'],
      2 => [$userAgent, 'String'],
      3 => [$userAgentShort, 'String'],
      4 => [$deviceStatusId, 'Integer'],
      5 => [$expiresTimestamp, 'Integer'],
    ];

    CRM_Core_DAO::executeQuery($sql, $params);

    return (int) CRM_Core_DAO::singleValueQuery(
      "SELECT LAST_INSERT_ID()"
    );
  }

  public static function insertDeviceLog(int $deviceId, int $eventTypeId, string $details): int {

    $sql = "
      INSERT INTO civicrm_anoncheckin_device_log (
        device_id,
        event_type_id,
        details
      ) VALUES (
        %1,
        %2,
        %3
      )
    ";

    $params = [
      1 => [$deviceId, 'Integer'],
      2 => [$eventTypeId, 'Integer'],
      3 => [$details, 'String'],
    ];

    CRM_Core_DAO::executeQuery($sql, $params);

    return (int) CRM_Core_DAO::singleValueQuery(
      "SELECT LAST_INSERT_ID()"
    );
  }

  /**
   * Record a session for the participant on a given device
   *
   * @param int $sessionId
   * @param array $device
   * @return int
   */
  public static function insertSessionOnDevice(int $sessionId, array $device): int {

    $now = gmdate('Y-m-d H:i:s');
    $sql = "
      INSERT INTO civicrm_anoncheckin_session_participant (
        session_id,
        participant_id,
        session_group_id,
        created_date,
        modified_date,
        device_id,
        session_status_id
      ) SELECT
        id,
        %1,
        session_group_id,
        %2,
        %2,
        %3,
        %4
        FROM civicrm_anoncheckin_session where id = %5;
    ";


    $params = [
      1 => [$device['participantId'], 'Integer'],
      2 => [$now, 'String'],
      3 => [$device['deviceId'], 'Integer'],
      4 => [CRM_Anoncheckin_Utils_Extern::SESSION_STATUS_COMPLETED, 'Integer'],
      5 => [$sessionId, 'Integer'],
    ];

    $sql = CRM_Core_DAO::composeQuery($sql, $params);

    CRM_Core_DAO::executeQuery($sql, $params);

    // Assuming success (which we're assuming), this addition means that 
    // future calls to selectParticipantSessions($pid) should refresh cache.
    self::cacheSelectClearActionArg('selectParticipantSessions', $device['participantId']);
      
    return (int) CRM_Core_DAO::singleValueQuery(
      "SELECT LAST_INSERT_ID()"
    );
  }

  /**
   * Get all properties of a device for a given deviceKey (ignoring any devices
   * with status='closed' or expires <= now)
   *
   * @param string $deviceKey
   * @return array|null If device found, an array of device properties; otherwise null.
   */
  public static function selectDeviceByKey(string $deviceKey): ?array {

    $sql = "
      SELECT d.id as device_id, d.*
      FROM civicrm_anoncheckin_device d
      WHERE d.device_key = %1
        AND d.device_status_id != %2
        AND d.expires > unix_timestamp()
    ";

    $params = [
      1 => [$deviceKey, 'String'],
      2 => [CRM_Anoncheckin_Utils_Device::DEVICE_STATUS_CLOSED, 'Integer'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if (!$dao->fetch()) {
      return NULL;
    }

    $ret = self::rowToArray($dao->toArray());

    return $ret;
  }

  public static function selectLockedDeviceByPid(string $pid): ?array {

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

    $ret = self::rowToArray($dao->toArray());

    return $ret;
  }

  /**
   * Delete a _session_participant record, per PK id.
   *
   * @param int Table PK id
   *
   * @return bool True if records were deleted; otherwise false.
   */
  public static function deleteSessionParticipant(int $sessionParticipantId): bool {
    $query = "
      DELETE FROM civicrm_anoncheckin_session_participant
      WHERE id = %1
    ";
    $queryParams = [
      1 => [$sessionParticipantId, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $ret = (bool)$dao->affectedRows();
    
    if ($ret) {
      // We've changd rows, so future calls to deleteSessionParticipant should not use cache.
      self::cacheSelectClearAction('deleteSessionParticipant');
    }
    return $ret;
  }

  private static function snakeToCamel(string $value): string {
    $parts = explode('_', $value);
    $first = array_shift($parts);

    return $first . implode('', array_map('ucfirst', $parts));
  }

  private static function camelToSnake(string $value): string {
    return strtolower(
      preg_replace('/([a-z])([A-Z])/', '$1_$2', $value)
    );
  }

  private static function rowToArray($row) {
    $ret = [];
    foreach ($row as $key => $value) {
      if ($key == 'id') {
        // We will not handle keys named 'id' because they're often ambiguous.
        // If you need that, name it something else, e.g. participant_id, etc.
        continue;
      }
      $ret[self::snakeToCamel($key)] = $value;
    }
    return $ret;
  }
  
  
  public static function cacheSelectClearAction(string $action) {
    unset(self::$cache[$action]);
  }
  
  public static function cacheSelectClearActionArg(string $action, $arg) {
    $argCacheKey = self::createArgCacheKey($arg);
    unset(self::$cache[$action][$argCacheKey]);
  }

  private static function createArgCacheKey($arg): string {
    return serialize($arg);
  }
 
  public static function cacheSelect(string $action, $arg) {
    if (!is_callable("self::{$action}")) {
      throw new CRM_Core_Exception(__METHOD__ . ": unrecognized action: " . var_export($action, 1));
    }
    $argCacheKey = self::createArgCacheKey($arg);
    if (
      !array_key_exists($action, self::$cache)
      || !array_key_exists($argCacheKey, self::$cache[$action])
    ) {
      self::$cache[$action][$argCacheKey] = self::{$action}($arg);
    }
    return self::$cache[$action][$argCacheKey];
  }  
}
