<!-- Intro -->
{* Only show this if:
 * - There's no participantId: i.e., if they're now scanning a badge or have already locked to a badge, this is not needed 
 *}
{if !$participantId}
<div class="card center">
  <h2>Welcome</h2>
  <p>Record your sessions here.</p>
</div>
{/if}

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
      {if $deviceIsLocked} <i id="anoncheckin-participantNameLock" class="fa fa-lock"></i> <a id="change-p-link" href="#" data-participantName="{$participantName}">Change</a>{/if}
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

{include file="{$extensionBasePath}/templates/CRM/Anoncheckin/common/qrScanner.tpl"}

