<?php

use CRM_Anoncheckin_ExtensionUtil as E;

return [
  'anoncheckin_hmac_secret' => [
    'name' => 'anoncheckin_hmac_secret',
    'type' => 'String',
    // No other metadata, as this should be omitted from settings forms.
  ],
  'anoncheckin_limit_checkin_minutes' => [
    'name' => 'anoncheckin_limit_checkin_minutes',
    'type' => 'Integer',
    'title' => E::ts('Max time in minutes to allow check-in before/after a session'),
    'description' => E::ts('Participants may only check in for a session within this many minutes before/after a session. To disable this limitation, set this to empty.'),
    'default' => 30,
    'html_type' => 'Text',
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['anoncheckin' => ['weight' => 10]],
  ],
];
