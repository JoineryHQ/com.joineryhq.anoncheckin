CRM.$(function($){
  var anoncheckinStaffCallbacks = {
    // Callback to validate format of "Staff Info" qr code.
    validateStaffInfo: function validateStaffInfo(qrData, e) {
      return qrData.startsWith('anoncheckin_deviceKey:');
    },
    // Success handler after scanning valid "Staff Info" qr code.
    onDataSuccessStaffInfo: function onDataSuccessStaffInfo(qrData) {
      var deviceKey = qrData.replace(/^anoncheckin_deviceKey:/, '');
      console.log('found device key: ', deviceKey);
      var destination = replaceLocationParams({deviceKey: deviceKey});
      window.location.href = destination;
    },
    // Callback to validate format of badge qr code.
    validateBadge: function validateBadge(qrData, e) {
      console.log('validateBadge', qrData);

      var paramName = 'p';

      const externAppUrl = new URL(CRM.vars.anoncheckin.externAppUrl);
      var testedUrl;
      try {
        testedUrl = new URL(qrData);
      } catch (_) {
        // If we're here, qrData is not a valid URL, so clearly this is not valid
        // data for us.
        return false;
      }

      // (a) ensure baseurl/path is what's expected for a badge, i.e. extern app url.
      const samePage =
              externAppUrl.origin === testedUrl.origin &&
              externAppUrl.pathname === testedUrl.pathname;

      // (b) check for param
      const hasParam = testedUrl.searchParams.has(paramName);
      if (samePage && hasParam) {
        return true;
      } else {
        console.log('page url: ' + externAppUrl);
        console.log('invalid url: ' + testedUrl);
        console.log('samePage: ' + samePage);
        console.log('hasParam: ' + hasParam, paramName);
        return false;
      }

      return false;
    },
    // Success handler after scanning valid badge qr code.
    onDataSuccessBadge: function onDataSuccessBadge(qrData) {
      var url = new URL(qrData);
      var p =  url.searchParams.get('p');
      var ph = url.searchParams.get('ph');
      var destination = replaceLocationParams({p: p, ph: ph});
      window.location.href = destination;
    }
  };

  // Modify this page's query parameters with whatever is given.
  var replaceLocationParams = function replaceLocationParams(params) {
    const url = new URL(window.location.href);

    Object.entries(params).forEach(([key, value]) => {
      if (value === null || value === undefined) {
        url.searchParams.delete(key);
      }
      else {
        url.searchParams.set(key, value);
      }
    });

    return url.toString();
  }

  // If we have sessionSuggestions, highlight them, and select those radio buttons.
  var elucidateTimeoutValue = function elucidateTimeoutValue() {
    var spanIdSelector = '#anoncheckin_device_max_age_minutes-elucidate';
    if (!$(spanIdSelector).length) {
      $('#anoncheckin_device_max_age_minutes').after(' <em id=' + spanIdSelector.replace(/^#/, '') + '>');
    }
    var minutes = $('#anoncheckin_device_max_age_minutes').val();
    var elucidatedHours = Math.floor(minutes / 60);
    var elucidatedMinutes = (minutes % 60);
    var elucidatedValue;
    if (Number.isNaN(elucidatedHours) || Number.isNaN(elucidatedMinutes)) {
      elucidatedValue = 'Invalid. Please enter a whole number of minutes.';
    }
    else {
      elucidatedValue = elucidatedHours + ' hours, ' + elucidatedMinutes + ' minutes';
    }
    $(spanIdSelector).html(' (' + elucidatedValue + ')');
  }

  // Define a change handler for 'timeout minutes' field.
  $('#anoncheckin_device_max_age_minutes').keyup(elucidateTimeoutValue);

  // Go ahead and fire that change handler now.
  elucidateTimeoutValue();
});
