<?php

use CRM_Anoncheckin_ExtensionUtil as E;

return [
  'name' => 'AnoncheckinSessionGroup',
  'table' => 'civicrm_anoncheckin_session_group',
  'class' => 'CRM_Anoncheckin_DAO_AnoncheckinSessionGroup',

  'getInfo' => fn() => [
    'title' => E::ts('Anoncheckin Session Group'),
    'title_plural' => E::ts('Anoncheckin Session Groups'),
    'description' => E::ts('Session grouping; used to prevent participants from checking in to multiple sessions with the same grouping.'),
    'log' => FALSE,
  ],

  'getFields' => fn() => [

    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
      'description' => E::ts('Unique session group ID'),
    ],

    'title' => [
      'title' => E::ts('Title'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Display title for the session group'),
    ],

    'event_id' => [
      'title' => E::ts('Event'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to event'),
      'entity_reference' => [
        'entity' => 'Event',
        'key' => 'id',
        'on_delete' => 'CASCADE',
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
    'index_event_id' => [
      'fields' => [
        'event_id' => TRUE,
      ],
    ],
  ],

];
