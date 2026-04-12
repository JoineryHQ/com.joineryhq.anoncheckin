<div class="crm-block crm-form-block crm-loadtest-form-block">

  <h3>Load Test Form</h3>

  {if $message}
    <p>{$message}</p>
  {/if}

  <div class="crm-section">
    <div class="label">Contact name:</div>
    <div class="content">{$contact_name|default:'(none)'}</div>
  </div>

  <div class="crm-section">
    <div class="label">Participant Count:</div>
    <div class="content">{$participant_count|default:'0'}</div>
  </div>

  <div class="crm-section">
    <div class="label">Event Title:</div>
    <div class="content">{$event_title|default:'(none)'}</div>
  </div>
  
  <div class="crm-section">
    <div class="label">Session Counter:</div>
    <div class="content">{$counter}</div>
  </div>
  
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>