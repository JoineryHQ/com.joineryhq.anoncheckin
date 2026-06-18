<h2>Help for: Sesssions Incorrect</h2>

<h3 style="margin-top: 1em;">Participant badge</h3>
{if !empty($userVars.badge)}
  <table style="margin-bottom: 1em;">
    <tr><td><strong>Participant ID:</strong></td><td>{$userVars.badge.participantId}</td></tr>
    <tr><td><strong>Participant Name:</strong></td><td>{$userVars.badge.displayName}</td></tr>
    <tr><td><strong>Event Title:</strong></td><td>{$userVars.badge.eventTitle}</td></tr>
  </table>
  <a id="anoncheckin-scan-badge" class="button" href="#" data-scan-type="p">Re-scan participant badge</a>
{else}  
  <p>
    <a id="anoncheckin-scan-badge" class="button" href="#" data-scan-type="p">Scan participant badge</a>
  </p>
{/if}

{if !empty($sessionElementNames)}
  <h3>Record sessions for badge participant "{$userVars.badge.displayName}"</h3>
  <p class="anoncheckin-session-suggestion">Likely suggestions appear in this style.</p>
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
