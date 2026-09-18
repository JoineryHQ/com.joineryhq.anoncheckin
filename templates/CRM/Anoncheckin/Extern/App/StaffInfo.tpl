<div class="card">
  <h2>Staff Info</h2>
  <table style="margin-bottom: 1em;">
    <tr><td><strong>Device Key:</strong></td><td>{$device.deviceKey}</td></tr>
    <tr><td><strong>Participant ID:</strong></td><td>{$device.participantId|default:"[none]"}</td></tr>
    <tr><td><strong>Participant Name:</strong></td><td>{$device.participantName|default:"[none]"}</td></tr>
    <tr><td><strong>Description:</strong></td><td>{$device.userAgentShort}</td></tr>
    <tr><td><strong>Device Status:</strong></td><td>{$device.deviceStatus}</td></tr>
  </table>
  <img src="{$deviceQrUrl}">
</div>

<div class="card center">
  <a class="button secondary" href="{$appUrl}">Go Back</a>
</div>
