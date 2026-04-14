<?php

function echoQrImage($params = [], $color = 'black') {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'];
  $base = $scheme . '://' . $host . '/wp-content/uploads/civicrm/ext/com.joineryhq.anoncheckin/extern/checkin.php';
  if (!empty($params)) {
    $url = $base . '?' . http_build_query($params);
  }
  else {
    $url = $base;
  }
  echo "<p>$url</p>" . '<img src="https://api.qrserver.com/v1/create-qr-code/?color='. $color .'&size=300&data=' . $url . '" style="display: block; margin-bottom: 25em;">';
}

  


$sessions = [
  1 => 'Session 1: Lorem ipsum dolor sit amet',
  2 => 'Session 2: Sed vel orci vitae tellus maximus viverra',
  3 => 'Session 3: Ut eu leo eget eros posuere efficitur eget ut purus',
  4 => 'Session 4: Vivamus at ante scelerisque purus placerat fringilla',
  5 => 'Session 5: Cras sed ex et libero efficitur maximus vitae sit amet nibh',
];


echo "<h1>QR Codes for simple testing</h1>";
echo "<p>Print these on paper, or scan directly on screen.</p>";
echo "<h2>Badge:</h2>";
echoQrImage(['p' => '1'], '0B3D91');

foreach ($sessions as $sessionId => $sessionTitle) {
  echo "<h2>{$sessionTitle}</h2>";
  echoQrImage(['s' => $sessionId], '1B5E20');
}
