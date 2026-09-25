<div class="card center">
  <h2>{$title|default:"Unlock via email"}</h2>
  {if $action == "action_get_request"}
    {if !$sent}
      {assign var="message" value="<p>Click below, and we'll send a link to unlock your badge to your email address: <strong>{$emailTruncated}</strong>.</p>"}
      {assign var="buttonLabel" value="Send me the link."}
      {assign var="buttonClass" value="success"}
    {else}
      {assign var="message" value="<p>We've sent a link to unlock your badge to your email address: <strong>{$emailTruncated}</strong>.</p><p>If you don't see it, please check your Junk folder.</p><p>You may also request a new link with the button below.</p>"}
      {assign var="buttonLabel" value="Send me a new link"}
      {assign var="buttonClass" value="secondary"}
    {/if}
    {$message}
    <form method="post">
      <input type="hidden" name="a" value="self_unlock">
      <input type="hidden" name="p" value="{$p}">
      <input type="hidden" name="ph" value="{$ph}">
      <input type="submit" class="button {$buttonClass}" value="{$buttonLabel}">
    </form>
  {elseif $action == "action_get_apply"}
    <p>You're about to unlock the badge for <strong>{$displayName}</strong>.</p>
    <form method="post">
      <input type="hidden" name="a" value="self_unlock">
      <input type="hidden" name="dp" value="{$dp}">
      <input type="hidden" name="dt" value="{$dt}">
      <input type="hidden" name="ds" value="{$ds}">
      <input type="submit" class="button success" value="Unlock my badge">  
      <a class="button secondary" href="{$appUrl}">No, that's not me.</a>
    </form>
  {/if}
</div>
