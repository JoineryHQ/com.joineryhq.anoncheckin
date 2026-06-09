<?php

use CRM_Anoncheckin_ExtensionUtil as E;

return [
  'name' => 'AnoncheckinSession',
  'table' => 'civicrm_anoncheckin_session',
  'class' => 'CRM_Anoncheckin_DAO_AnoncheckinSession',

  'getInfo' => fn() => [
    'title' => E::ts('Anoncheckin Session'),
    'title_plural' => E::ts('Anoncheckin Sessions'),
    'description' => E::ts('Sessions available for check-in, per event'),
    'log' => FALSE,
  ],

  'getFields' => fn() => [

    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
      'description' => E::ts('Unique session ID'),
    ],

    'title' => [
      'title' => E::ts('Title'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Session title'),
    ],

    'start_date' => [
      'title' => E::ts('Start Date/Time'),
      'sql_type' => 'datetime',
      'input_type' => 'Date',
      'required' => TRUE,
      'description' => E::ts('Session start date and time'),
    ],

    'end_date' => [
      'title' => E::ts('End Date/Time'),
      'sql_type' => 'datetime',
      'input_type' => 'Date',
      'required' => TRUE,
      'description' => E::ts('Session end date and time'),
    ],

    'session_group_id' => [
      'title' => E::ts('Session Group'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('Grouping used to prevent check-in to multiple concurrent sessions'),
      'entity_reference' => [
        'entity' => 'AnoncheckinSessionGroup',
        'key' => 'id',
        'on_delete' => 'RESTRICT',
      ],
    ],

    'weight' => [
      'title' => E::ts('Weight'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'default' => 0,
      'description' => E::ts('Display order'),
    ],

  ],

  'getIndices' => fn() => [
    'index_session_group_id' => [
      'fields' => [
        'session_group_id' => TRUE,
      ],
    ],
  ],

];
