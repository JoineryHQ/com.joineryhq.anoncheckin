<?php

use CRM_Anoncheckin_ExtensionUtil as E;

return [
  'name' => 'AnoncheckinSessionParticipant',
  'table' => 'civicrm_anoncheckin_session_participant',
  'class' => 'CRM_Anoncheckin_DAO_AnoncheckinSessionParticipant',

  'getInfo' => fn() => [
    'title' => E::ts('Anoncheckin Session Participant'),
    'title_plural' => E::ts('Anoncheckin Session Participants'),
    'description' => E::ts('Check-in record for an event participant at a session'),
    'log' => FALSE,
  ],

  'getFields' => fn() => [

    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
      'description' => E::ts('Unique check-in record ID'),
    ],

    'session_id' => [
      'title' => E::ts('Session'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('Session attended'),
      'entity_reference' => [
        'entity' => 'AnoncheckinSession',
        'key' => 'id',
        'on_delete' => 'RESTRICT',
      ],
    ],

    'participant_id' => [
      'title' => E::ts('Participant'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('CiviCRM participant'),
      'entity_reference' => [
        'entity' => 'Participant',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],

    'session_group_id' => [
      'title' => E::ts('Session Group'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('Copied from the session record to enforce group uniqueness'),
      'entity_reference' => [
        'entity' => 'AnoncheckinSessionGroup',
        'key' => 'id',
        'on_delete' => 'RESTRICT',
      ],
    ],

    'created_date' => [
      'title' => E::ts('Created'),
      'sql_type' => 'timestamp',
      'required' => TRUE,
      'default' => 'CURRENT_TIMESTAMP',
      'description' => E::ts('Date/time check-in record was created'),
    ],

    'modified_date' => [
      'title' => E::ts('Modified'),
      'sql_type' => 'timestamp',
      'required' => TRUE,
      'default' => 'CURRENT_TIMESTAMP',
      'description' => E::ts('Date/time check-in record was last modified'),
    ],

    'device_id' => [
      'title' => E::ts('Device'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      // Not required because devices are ultimately ephemeral, but session_participant
      // is durable and valuable. Therefore, device_id is "nice to have" but nullable;
      // also, FK is 'on delete set null' for the same reason, so this column
      // must accept a null value.
//      'required' => FALSE,
      'description' => E::ts('Device used for check-in'),
      'entity_reference' => [
        'entity' => 'AnoncheckinDevice',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],

    'session_status_id' => [
      'title' => E::ts('Session Status'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => E::ts('Current status of this session participation'),
      'pseudoconstant' => [
        'option_group_name' => 'anoncheckin_session_status',
        'key_column' => 'value',
      ],
    ],

  ],

  'getIndices' => fn() => [

    'index_session_id' => [
      'fields' => [
        'session_id' => TRUE,
      ],
    ],

    'index_participant_id' => [
      'fields' => [
        'participant_id' => TRUE,
      ],
    ],

    'index_session_group_id' => [
      'fields' => [
        'session_group_id' => TRUE,
      ],
    ],

    'index_device_id' => [
      'fields' => [
        'device_id' => TRUE,
      ],
    ],

    'index_session_status_id' => [
      'fields' => [
        'session_status_id' => TRUE,
      ],
    ],

    'UI_participant_session_group' => [
      'fields' => [
        'participant_id' => TRUE,
        'session_group_id' => TRUE,
      ],
      'unique' => TRUE,
    ],

  ],

];
