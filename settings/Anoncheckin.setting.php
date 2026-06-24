<?php

use CRM_Anoncheckin_ExtensionUtil as E;

return [
  'anoncheckin_hmac_secret' => [
    'name' => 'anoncheckin_hmac_secret',
    'type' => 'String',
    // No other metadata, as this should be omitted from settings forms.
  ],
  'anoncheckin_device_max_age_minutes' => [
    'name' => 'anoncheckin_device_max_age_minutes',
    'type' => 'Integer',
    'title' => E::ts('Device idle timeout (minutes)'),
    'description' => E::ts('If a device is unused for longer than this many minutes, the participant must re-scan their badge QR code.'),
    'default' => 2880,
    'html_type' => 'Text',
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['anoncheckin' => ['weight' => 5]],
  ],
  'anoncheckin_limit_checkin_by_time' => [
    'name' => 'anoncheckin_limit_checkin_by_time',
    'type' => 'Boolean',
    'title' => E::ts('Limit session checkin by session time?'),
    'description' => E::ts('If this is enabled, participants may only check in for a session within a certain time window before/after that session (see below).'),
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
    'description' => E::ts('Only relevant if "Limit session checkin by session time?" is enabled (see above).'),
    'default' => 0,
    'html_type' => 'Text',
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['anoncheckin' => ['weight' => 20]],
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
    'settings_pages' => ['anoncheckin' => ['weight' => 30]],
  ],
];
