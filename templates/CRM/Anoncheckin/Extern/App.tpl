<html>
  <head>
    <title>Session Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>
    <!-- fixme: this path should be dynamically generated in App::__construct() -->
    <link rel="stylesheet" id="ls-global-css" href="/wp-content/plugins/civicrm/civicrm/css/crm-i.css" media="all">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css"/>
    {$cssContent}
    {$jsContent}
    <style>
      body {
        font-family: sans-serif;
        max-width: 900px;
        margin: 0 auto;
        padding: 1rem;
        background: #f7f7f7;
      }
      .card {
        background: #fff;
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      }
      .center {
        text-align: center;
      }
      .button {
        display: block;
        width: 100%;
        padding: 0.75rem;
        margin-top: 0.5rem;
        font-size: 1rem;
        border: none;
        border-radius: 6px;
        background: #007bff;
        color: #fff;
        box-sizing: border-box;
        text-decoration: none;
      }
      .button.secondary {
        background: #6c757d;
      }
      .button.success {
        background: #28a745;
      }
      .list {
        list-style: none;
        padding: 0;
        margin: 0;
      }
      .list li {
        padding: 0.5rem 0;
        border-bottom: 1px solid #eee;
      }

      .card.message {
        border-left: 4px solid;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
      }
      .card.message.message-type-error {
        border-color: #cc1e2e;
      }
      .card.message.message-type-success {
        border-color: #1e7e34;
      }
      .card.message.message-type-info {
        border-color: #fdbd00;
      }

      .inline-message {
        padding: 1rem;
      }
      
      .card.message.message-type-error,
      .inline-message.message-type-error {
        background: #f8d7da;
        color: #842029;
      }
      
      .card.message.message-type-success,
      .inline-message.message-type-success {
        background: #e6f4ea;
        color: #1e7e34;
      }
      
      .card.message.message-type-info,
      .inline-message.message-type-info {
        background: #fff3cd;
        color: #664d03;
      }

      #admin-footer {
        text-align: center;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: .5rem;
        background: white;
        font-size: .7rem;
        color: gray;
      }
      #admin-footer a {
        color: gray;
      }

      #anoncheckin-participantNameLock {
        color: green;
      }
      a#change-p-link {
        font-size: .5em;
        color: green;
      }
    </style>
    <script>
      {literal}
      // Attach click handlers to s and p buttons.
      $(document).ready(function () {
        $('#anoncheckin-scan-badge').click(
          {
            validateCallback: anoncheckinAppCallbacks.urlIsValid,
            dataSuccessCallback: anoncheckinAppCallbacks.onDataSuccess
          }, 
          anoncheckinQrScanner.openScanner
        );
        $('#anoncheckin-scan-session').click(
          {
            validateCallback: anoncheckinAppCallbacks.urlIsValid,
            dataSuccessCallback: anoncheckinAppCallbacks.onDataSuccess
          }, 
          anoncheckinQrScanner.openScanner
        );
      });

      {/literal}
      
    </script>
  </head>
  <body>
    <h1>Session Attendance</h1>

    <!-- Intro -->
    {* Only show this if there's no participantId -- i.e., if they're now
     * scanning a badge or have already locked to a badge, this is not needed *}
    {if !$participantId}
    <div class="card center">
      <h2>Welcome</h2>
      <p>Record your sessions here.</p>
    </div>
    {/if}
    {if !empty($messages)}
      {foreach from=$messages item=message}
        <div class="card center message message-type-{$message.type}">
          <span>{$message.message}</span>
        </div>
      {/foreach}
    {/if}

    {if $isStaffInfo}
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
    {/if}
    {if $isFatal || $isStaffInfo}
      <div class="card center">
        <a class="button secondary" href="{$appUrl}">Go Back</a>
      </div>
    {else}
      {if $participantId}
        <div class="card center">
          {if $action == 'action_get_p'}
            <p>You've scanned the badge for</p>
          {/if}
          {if $deviceIsLocked}
            <p>This device belongs to</p>
          {/if}
          <h2>
            {$participantName}
            {if $deviceIsLocked} <i id="anoncheckin-participantNameLock" class="fa fa-lock"></i> <a id="change-p-link" href="?a=change_p">Change</a>{/if}
          </h2>
          {if $participantEventTitle}
            <h3>At event: "{$participantEventTitle}"</h3>
          {/if}
        </div>
      {/if}

      {if !$deviceIsLocked && $action != "action_get_p"}
        {* device is not locked, and they're not trying to scan a badge, so prompt to scan a badge. *}
        <div class="card center">
          <p></p>
          {assign var="buttonClass" value="success"}
          {assign var="buttonLabel" value="Scan my badge"}
          <button id="anoncheckin-scan-badge" data-scan-type="p" class="button {$buttonClass}">{$buttonLabel}</button>
        </div>
      {/if}

      {if $action == 'action_get_p'}
        {* They've scanned a badge, so ask them if they're sure. *}
        <div class="card center">
          <h2>Is this you?</h2>
          <p>You are about to lock this device to the badge for <strong>{$participantName}</strong>.</p>
          <p>Once locked, you will need staff assistance to unlock.</p>
          <form method="post">
            <input type="hidden" name="p" value="{$p}">
            <input type="hidden" name="ph" value="{$ph}">
            <!-- Confirm -->
            <input type="submit" class="button success" value="Yes, lock my device to this badge.">
            <button id="anoncheckin-scan-badge" data-scan-type="p" class="button secondary">No, that's not me. Scan another badge.</button>
          </form>
        </div>
      {/if}

      {if $deviceIsLocked}
        {* device is locked. Good, now they can work with sessions *}
        {if $action == "action_get_s"}
          {* They've scanned a session QR. Ask them if they're sure. *}
          <div class="card center">
            <!-- Session selection -->
            <h2>{$sessionTitle}</h2>
            {if !empty($sessionOverwriteWarning)}
              <div class="inline-message message-type-error">
                WARNING: If you continue below, your session "{$sessionOverwriteWarning.oldTitle}" will be replaced with this session, "{$sessionOverwriteWarning.newTitle}".
              </div>
            {/if}
            <p>Record your attendance at this session?</p>
            <form method="post">
              <input type="hidden" name="s" value="{$s}">
              <input type="hidden" name="sh" value="{$sh}">
              <!-- Confirm -->
              <input type="submit" class="button success" value="Yes, confirm and save">
              <button id="anoncheckin-scan-session" data-scan-type="s" class="button secondary">No, scan a different session</button>
            </form>
          </div>
        {else}
          {* they're not trying to scan a session, so prompt them to do so. *}
          <button id="anoncheckin-scan-session" data-scan-type="s" class="button success">Scan a session QR code</button>          
        {/if}
        
        <!-- Show saved sessions -->
        {if !empty($participantSessions)}
          <div class="card">
            <h2>Your Attended Sessions</h2>
            <ul class="list">
              {foreach from=$participantSessions item=participantSession}
                <li>{$participantSession.title}</li>
              {/foreach}
            </ul>
          </div>
        {/if}
        
      {/if}

    {/if}

    {if $never}
      <!-- User -->
{*
      <div class="card center">
        {if $participantName}
          <h2>{$participantName} <i id="anoncheckin-participantNameLock" class="fa fa-lock"></i></h2>
          {assign var="buttonClass" value="secondary"}
          <button id="anoncheckin-not-me" class="button secondary">This is not me!</button>
        {else}
          <p></p>
          {assign var="buttonClass" value="success"}
          {assign var="buttonLabel" value="Scan my badge"}
          <button id="anoncheckin-scan-badge" data-scan-type="p" class="button {$buttonClass}">{$buttonLabel}</button>
        {/if}
*}
{*        <!-- Session selection -->
        {if $sessionTitle}
          <h2>{$sessionTitle}</h2>
          {assign var="buttonClass" value="secondary"}
          {assign var="buttonLabel" value="Re-scan session QR code"}
        {else}
          <p></p>
          {assign var="buttonClass" value="success"}
          {assign var="buttonLabel" value="Scan a session QR code"}
        {/if}
        <button id="anoncheckin-scan-session" data-scan-type="s" class="button {$buttonClass}">{$buttonLabel}</button>
*}
{*        {if $p && $s}
          <form method="post">
            <input type="hidden" name="p" value="{$p}">
            <input type="hidden" name="ph" value="{$ph}">
            <input type="hidden" name="s" value="{$s}">
            <input type="hidden" name="sh" value="{$sh}">
            <!-- Confirm -->
            <input type="submit" class="button success" value="Confirm and save">
          </form>

        {/if}
*}
{*      </div>*}

{*      <!-- Saved sessions -->
      {if !empty($attendedSessionNames)}
        <div class="card">
          <h2>Your Attended Sessions</h2>
          <ul class="list">
            {foreach from=$attendedSessionNames item=attendedSessionName}
              <li>{$attendedSessionName}</li>
              {/foreach}
          </ul>
        </div>
      {/if}
    {/if}
*}
      {/if}

    <!-- debug messages -->
    {if !empty($debugMessages)}
      <div class="card">
        <h2>Debug messages</h2>
        <ul class="list">
          {foreach from=$debugMessages item=debugMessage}
            <li>{$debugMessage}</li>
            {/foreach}
        </ul>
      </div>
    {/if}
    <div id="admin-footer">
      <a href="?a=staff_info">Staff Info</a> 
      {if $isDebug}
        | <a target="_blank" href="/civicrm/?page=CiviCRM&q=civicrm%2Fanoncheckin%2Ftestqrcodes">QR Codes</a>
      {/if}
    </div>
    {include file="{$extensionBasePath}/templates/CRM/Anoncheckin/common/qrScanner.tpl"}
  </body>
</html>




