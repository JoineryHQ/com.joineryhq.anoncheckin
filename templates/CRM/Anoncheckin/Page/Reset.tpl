<p><strong>Check-in process reset attempt:</strong></p>
{if $isReset}
  <p>Success. Reset completed.</p>
{else}
  <p>Failure. Try again.</p>
{/if}

<a class="button" href="{crmURL p="civicrm/anoncheckin/status"}">View Status page.</a>