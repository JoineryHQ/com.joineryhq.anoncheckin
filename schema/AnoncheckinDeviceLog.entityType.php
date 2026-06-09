<?php
use CRM_Anoncheckin_ExtensionUtil as E;

return [
  'name' => 'AnoncheckinDeviceLog',
  'table' => 'civicrm_anoncheckin_device_log',
  'class' => 'CRM_Anoncheckin_DAO_AnoncheckinDeviceLog',

  'getInfo' => fn() => [
    'title' => E::ts('Anoncheckin Device Log'),
    'title_plural' => E::ts('Anoncheckin Device Logs'),
    'description' => E::ts('Log of relevant events on devices'),
    'log' => FALSE,
  ],

  'getFields' => fn() => [

    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
      'description' => E::ts('Unique log entry ID'),
    ],

    'device_id' => [
      'title' => E::ts('Device'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('Related device'),
      'entity_reference' => [
        'entity' => 'AnoncheckinDevice',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],

    'logged_at' => [
      'title' => E::ts('Logged At'),
      'sql_type' => 'timestamp',
      'required' => TRUE,
      'default' => 'CURRENT_TIMESTAMP',
      'input_type' => 'Date',
      'description' => E::ts('Date and time of logged event'),
    ],

    'user_cid' => [
      'title' => E::ts('User Contact'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('cid of User who performed the action (usually a staff member)'),
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
      ],
    ],

    'event_type_id' => [
      'title' => E::ts('Event Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Type of event being logged'),
    ],

    'details' => [
      'title' => E::ts('Details'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'required' => TRUE,
      'description' => E::ts('Additional details about the event'),
    ],

  ],

  'getIndices' => fn() => [
    'index_device_id' => [
      'fields' => [
        'device_id' => TRUE,
      ],
    ],
    'index_user_cid' => [
      'fields' => [
        'user_cid' => TRUE,
      ],
    ],
    'index_event_type_id' => [
      'fields' => [
        'event_type_id' => TRUE,
      ],
    ],
  ],

];
