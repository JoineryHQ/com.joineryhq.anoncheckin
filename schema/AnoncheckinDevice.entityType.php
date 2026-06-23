<?php
use CRM_Anoncheckin_ExtensionUtil as E;

return [
  'name' => 'AnoncheckinDevice',
  'table' => 'civicrm_anoncheckin_device',
  'class' => 'CRM_Anoncheckin_DAO_AnoncheckinDevice',
  'getInfo' => fn() => [
    'title' => E::ts('AnoncheckinDevice'),
    'title_plural' => E::ts('AnoncheckinDevices'),
    'description' => E::ts('A user session; here termed "device" to avoid confusion with "event sessions I can attend".'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique AnoncheckinDevice ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'participant_id' => [
      'title' => E::ts('Participant ID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('Unique string key for each device'),
    ],
    'device_key' => [
      'title' => E::ts('Device Key'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Unique string key for each device'),
    ],
    'user_agent' => [
      'title' => E::ts('User Agent String'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Full raw user-agent string'),
    ],
    'user_agent_short' => [
      'title' => E::ts('User Agent, Short'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Human-readable user agent, e.g. "Chrome on Android"'),
    ],
    'device_status_id' => [
      'title' => E::ts('Status'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => E::ts('Current status of this device'),
      'required' => TRUE,
      'pseudoconstant' => [
        'option_group_name' => 'anoncheckin_device_status',
        'key_column' => 'value',
      ],
    ],
    'expires' => [
      'title' => E::ts('Expires'),
      'sql_type' => 'bigint unsigned',
      'required' => TRUE,
      'input_type' => 'Number',
      'description' => E::ts('Unix timestamp at which this device expires.'),
    ],
  ],
  'getIndices' => fn() => [
    'index_device_key' => [
      'fields' => ['device_key' => TRUE],
      'unique' => TRUE,
    ],    
    'index_device_status_id' => [
      'fields' => ['device_status_id' => TRUE],
    ],
  ],
  'getPaths' => fn() => [],
];
