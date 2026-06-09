<?php

use CRM_Anoncheckin_ExtensionUtil as E;

return [
  'anoncheckin_hmac_secret' => [
    'name' => 'anoncheckin_hmac_secret',
    'type' => 'String',
    // No other metadata, as this should be omitted from settings forms.
  ],
  // Example copied from another extension, just for easy reference later.
  //  'anoncheckin_limit_days' => [
  //    'name' => 'anoncheckin_limit_days',
  //    'type' => 'Int',
  //    'title' => E::ts('Days before limiting'),
  //    'description' => E::ts('Disqualifying contributions are older than this many days, per their %1 field value.', [1 => E::ts('Contribution Date')]),
  //    'default' => 90,
  //    'html_type' => 'text',
  //    'is_domain' => 1,
  //    'is_contact' => 0,
  //    'settings_pages' => ['anoncheckin' => ['weight' => 10]],
  //  ],
];
