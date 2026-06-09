<h1>QR Codes for simple testing</h1>
<p>Print these on paper, or scan directly on screen.</p>

<h2>Badge:</h2>
<p><a href="{$indivAppUrl}">{$indivAppUrl}</a></p>
<img src="{$indivQrUrl}" style="display: block; margin-bottom: 25em;">';


{foreach from=$sessionUrls item=sessionUrl}
  <h2>{$sessionUrl.title}:</h2>
  <p><a href="{$sessionUrl.app}">{$sessionUrl.app}</a></p>
  <img src="{$sessionUrl.qr}" style="display: block; margin-bottom: 25em;">';
{/foreach}


