CRM.$(function($){

  var elucidateTimeoutValue = function elucidateTimeoutValue() {
    var spanIdSelector = '#anoncheckin_device_max_age_minutes-elucidate';
    if (!$(spanIdSelector).length) {
      $('#anoncheckin_device_max_age_minutes').after(' <em id=' + spanIdSelector.replace(/^#/, '') + '>');
    }
    var minutes = $('#anoncheckin_device_max_age_minutes').val();
    var elucidatedHours = Math.floor(minutes / 60);
    var elucidatedMinutes = (minutes % 60);
    var elucidatedValue;
    if (Number.isNaN(elucidatedHours) || Number.isNaN(elucidatedMinutes) || elucidatedMinutes < 0) {
      elucidatedValue = 'Invalid. Please enter a positive whole number of minutes.';
    }
    else {
      elucidatedValue = elucidatedHours + ' hours, ' + elucidatedMinutes + ' minutes';
    }
    $(spanIdSelector).html(' (' + elucidatedValue + ')');
  }

  var toggleSelfUnlockFields = function toggleSelfUnlockFields() {
    var selfUnlockCheckbox = $('#anoncheckin_self_unlock_enabled');
    var selfUnlockFieldsContainer = $('tr#anoncheckin_SelfUnlockFields');
    if(selfUnlockCheckbox.is(':checked')) {
      selfUnlockFieldsContainer.show();
    }
    else {
      selfUnlockFieldsContainer.hide();
    }
  }

  // Place 'allow self-unlock'-related fields in a collabsible sub-table.
  $('#anoncheckin_self_unlock_enabled').closest('tr').after('<tr id="anoncheckin_SelfUnlockFields" style="display: none;"><td colspan="2"><table style="background: rgba(0,0,0,.1)"><tbody></tbody></td></tr>');
  $('tr.crm-setting-form-block-anoncheckin_self_unlock_template').appendTo('tr#anoncheckin_SelfUnlockFields tbody');
  $('tr.crm-setting-form-block-anoncheckin_self_unlock_link_ttl').appendTo('tr#anoncheckin_SelfUnlockFields tbody');

  // Define a change handler for 'timeout minutes' field.
  $('#anoncheckin_device_max_age_minutes').keyup(elucidateTimeoutValue);
  // Go ahead and fire that change handler now.
  elucidateTimeoutValue();

  // Define a change handler for 'allow self-unlock' field.
  $('#anoncheckin_self_unlock_enabled').change(toggleSelfUnlockFields);
  // Go ahead and fire that change handler now.
  toggleSelfUnlockFields();

  // Hide all 'self-unlock' related fields if emailapi extension is not installed.
  if (!CRM.vars.anoncheckin.isEmailApiInstalled) {
    $('#anoncheckin_self_unlock_enabled').closest('tr').before('<tr><td colspan="2"><div class="messages warning">Settings for "Allow self-service badge unlock?" are not available. To expose them, install the "Email API" extension.<div></td></tr>');
    $('#anoncheckin_self_unlock_enabled').closest('tr').hide();
    $('tr#anoncheckin_SelfUnlockFields').hide();
  }

});
