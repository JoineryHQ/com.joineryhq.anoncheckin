<h2>Help for: Device locked to wrong badge</h2>

<h3>Device status</h3>
{if !empty($userVars.device)}
  <table style="margin-bottom: 1em;">
    <tr><td><strong>Device Key:</strong></td><td>{$userVars.device.deviceKey}</td></tr>
    <tr><td><strong>Participant ID:</strong></td><td>{$userVars.device.participantId|default:"[none]"}</td></tr>
    <tr><td><strong>Participant Name:</strong></td><td>{$userVars.device.displayName|default:"[none]"}</td></tr>
    <tr><td><strong>Description:</strong></td><td>{$userVars.device.userAgentShort}</td></tr>
    <tr><td><strong>Device Status:</strong></td><td>{$userVars.device.status}</td></tr>
  </table>
  <a id="anoncheckin-scan-staffinfo" class="button" href="#">Re-Scan Device "Staff Info"</a>
{else}  
  <p>
    <a id="anoncheckin-scan-staffinfo" class="button" href="#" style="display: inline !important;">Scan Device "Staff Info"</a>  Press "Staff Info" on participant's device and scan the resulting QR code.
  </p>
{/if}

<h3 style="margin-top: 1em;">Participant badge</h3>
{if !empty($userVars.badge)}
  <table style="margin-bottom: 1em;">
    <tr><td><strong>Participant ID:</strong></td><td>{$userVars.badge.participantId}</td></tr>
    <tr><td><strong>Participant Name:</strong></td><td>{$userVars.badge.displayName}</td></tr>
    <tr><td><strong>Event Titlte:</strong></td><td>{$userVars.badge.eventTitle}</td></tr>
  </table>
  <a id="anoncheckin-scan-badge" class="button" href="#" data-scan-type="p">Re-scan participant badge</a>
{else}  
  <p>
    <a id="anoncheckin-scan-badge" class="button" href="#" data-scan-type="p">Scan participant badge</a>
  </p>
{/if}

{if !empty($sessionElementNames)}
  <h3>Record sessions for badge participant "{$userVars.badge.displayName}"</h3>
  {foreach from=$sessionElementNames item=sessionElementName}
    <br />
    {$form[$sessionElementName].label}
    {$form[$sessionElementName].html}
  {/foreach}
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/if}

{include file="CRM/Anoncheckin/common/qrScanner.tpl"}
