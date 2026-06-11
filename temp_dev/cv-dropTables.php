<?php
$tables = [
  'civicrm_anoncheckin_session_participant',
  'civicrm_anoncheckin_device_log',
  'civicrm_anoncheckin_device',
  'civicrm_anoncheckin_session',
  'civicrm_anoncheckin_session_group',
];
foreach ($tables as $table) {
  $tableEscaped = CRM_Core_DAO::escapeString($table);
  $query = "drop table if exists $tableEscaped;";
  echo "$query\n";
  $dao = CRM_Core_DAO::executeQuery($query);
}