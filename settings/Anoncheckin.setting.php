<?php

use CRM_Anoncheckin_ExtensionUtil as E;

return [
  'anoncheckin_hmac_secret' => [
    'name' => 'anoncheckin_hmac_secret',
    'type' => 'String',
    // No other metadata, as this should be omitted from settings forms.
  ],
  'anoncheckin_debug' => [
    'name' => 'anoncheckin_debug',
    'type' => 'Boolean',
    'title' => E::ts('Display debug messages on user-facing interface?'),
    'description' => '',
    'default' => FALSE,
    'html_type' => 'Toggle',
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['anoncheckin' => ['weight' => 5]],
  ],
  'anoncheckin_limit_checkin_by_time' => [
    'name' => 'anoncheckin_limit_checkin_by_time',
    'type' => 'Boolean',
    'title' => E::ts('Limit session checkin by session time?'),
    'description' => E::ts('If this is enabled, participants may only check in for a session within a certain time window before/after that session.'),
    'default' => FALSE,
    'html_type' => 'Toggle',
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['anoncheckin' => ['weight' => 10]],
  ],
  'anoncheckin_limit_checkin_minutes' => [
    'name' => 'anoncheckin_limit_checkin_minutes',
    'type' => 'Integer',
    'title' => E::ts('Max time in minutes to allow check-in before/after a session'),
    'description' => E::ts('Only relevant if "Limit session checkin by session time?" is enabled.'),
    'default' => 0,
    'html_type' => 'Text',
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['anoncheckin' => ['weight' => 10]],
  ],
];
