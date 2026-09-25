<div class="card center">
  {foreach from=$buttons item=button}
    <a class="button {$button.class}" href="{$button.url}">{$button.label}</a>
  {/foreach}
</div>
