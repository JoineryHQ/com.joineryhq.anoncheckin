<html>
  <head>
    <title>Session Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- $jsUrlsContent HERE: -->        
    {$jsUrlsContent}
    <!-- $cssUrlsContent HERE: -->    
    {$cssUrlsContent}
    <!-- $cssFilesContent HERE: -->
    {$cssFilesContent}
    <!-- $jsFilesContent HERE: -->
    {$jsFilesContent}
  </head>
  <body>
    <h1>Session Attendance</h1>
    {if !empty($messages)}
      {foreach from=$messages item=message}
        <div class="card message message-type-{$message.type}">
          <span>{$message.message}</span>
        </div>
      {/foreach}
    {/if}

    {include file="{$extensionBasePath}/templates/CRM/Anoncheckin/Extern/{$appTpl}"}
    
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
  </body>
</html>




