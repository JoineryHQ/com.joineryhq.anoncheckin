<?php

use CRM_Anoncheckin_ExtensionUtil as E;

return [
  // Option Group: Anoncheckin Device Status
  [
    'name' => 'OptionGroup_anoncheckin_device_status',
    'entity' => 'OptionGroup',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'anoncheckin_device_status',
        'title' => E::ts('Anoncheckin Device Status'),
        'description' => E::ts('Status values for anonymous check-in devices'),
        'is_reserved' => TRUE,
        'is_locked' => TRUE,
        'is_active' => TRUE,
        'data_type' => 'Integer',
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  // pending
  [
    'name' => 'OptionValue_anoncheckin_device_status_pending',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'anoncheckin_device_status',
        'label' => E::ts('Pending'),
        'name' => 'pending',
        'value' => 1,
        'weight' => 1,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  // locked
  [
    'name' => 'OptionValue_anoncheckin_device_status_locked',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'anoncheckin_device_status',
        'label' => E::ts('Locked'),
        'name' => 'locked',
        'value' => 2,
        'weight' => 2,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  // invalidated
  [
    'name' => 'OptionValue_anoncheckin_device_status_invalidated',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'anoncheckin_device_status',
        'label' => E::ts('Invalidated'),
        'name' => 'invalidated',
        'value' => 3,
        'weight' => 3,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  // closed
  [
    'name' => 'OptionValue_anoncheckin_device_status_closed',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'anoncheckin_device_status',
        'label' => E::ts('Closed'),
        'name' => 'closed',
        'value' => 4,
        'weight' => 4,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  // Option Group: Anoncheckin Session Status
  [
    'name' => 'OptionGroup_anoncheckin_session_status',
    'entity' => 'OptionGroup',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'anoncheckin_session_status',
        'title' => E::ts('Anoncheckin Session Status'),
        'description' => E::ts('Status values for session participation records'),
        'is_reserved' => TRUE,
        'is_locked' => TRUE,
        'is_active' => TRUE,
        'data_type' => 'Integer',
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  // completed
  [
    'name' => 'OptionValue_anoncheckin_session_status_completed',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'anoncheckin_session_status',
        'label' => E::ts('Completed'),
        'name' => 'completed',
        'value' => 1,
        'weight' => 1,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  // transferred
  [
    'name' => 'OptionValue_anoncheckin_session_status_transferred',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'anoncheckin_session_status',
        'label' => E::ts('Transferred'),
        'name' => 'transferred',
        'value' => 2,
        'weight' => 2,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  // invalidated
  [
    'name' => 'OptionValue_anoncheckin_session_status_invalidated',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'anoncheckin_session_status',
        'label' => E::ts('Invalidated'),
        'name' => 'invalidated',
        'value' => 3,
        'weight' => 3,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  
  // Option Group: Anoncheckin Device Log Type
  [
    'name' => 'OptionGroup_anoncheckin_device_log_type',
    'entity' => 'OptionGroup',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'anoncheckin_device_log_type',
        'title' => E::ts('Anoncheckin Device Log Type'),
        'description' => E::ts('Types of device event log entries'),
        'is_reserved' => TRUE,
        'is_locked' => TRUE,
        'is_active' => TRUE,
        'data_type' => 'Integer',
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  // user
  [
    'name' => 'OptionValue_anoncheckin_device_log_type_user',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'anoncheckin_device_log_type',
        'label' => E::ts('User'),
        'name' => 'pending',
        'value' => 1,
        'weight' => 1,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],
  // admin
  [
    'name' => 'OptionValue_anoncheckin_device_log_type_admin',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'anoncheckin_device_log_type',
        'label' => E::ts('Admin'),
        'name' => 'pending',
        'value' => 2,
        'weight' => 2,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'cleanup' => 'always',
        'update' => 'always',
      ],
    ],
  ],

  
];
