<h1>QR Codes for simple testing</h1>
<p>Print these on paper, or scan directly on screen.</p>

{foreach from=$indivUrls item=indivUrl}
  <h2>Badge: {$indivUrl.title}:</h2>
  <p><a target="_blank" href="{$indivUrl.app}">{$indivUrl.app}</a></p>
  <a target="_blank" href="{$indivUrl.app}"><img src="{$indivUrl.qr}" style="display: block; margin-bottom: 2em;"></a>;
{/foreach}

{foreach from=$sessionUrls item=sessionUrl}
  <h2>{$sessionUrl.title}:</h2>
  <p><a target="_blank" href="{$sessionUrl.app}">{$sessionUrl.app}</a></p>
  <a target="_blank" href="{$sessionUrl.app}"><img src="{$sessionUrl.qr}" style="display: block; margin-bottom: 2em;"></a>;
{/foreach}


