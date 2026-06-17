CRM.$(function($){
  var anoncheckinStaffCallbacks = {
    validateStaffInfo: function validateStaffInfo(qrData, e) {
      return qrData.startsWith('anoncheckin_deviceKey:');
    },
    onDataSuccessStaffInfo: function onDataSuccessStaffInfo(qrData) {
      var deviceKey = qrData.replace(/^anoncheckin_deviceKey:/, '');
      console.log('found device key: ', deviceKey);
      var destination = replaceLocationParams({deviceKey: deviceKey});
      window.location.href = destination;
    },
    validateBadge: function validateBadge(qrData, e) {
      console.log('validateBadge', qrData);

      var paramName = 'p';

      const externAppUrl = new URL(CRM.vars.anoncheckin.externAppUrl);
      const testedUrl = new URL(qrData);

      // (a) compare origin + pathname (ignore query + hash)
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
    onDataSuccessBadge: function onDataSuccessBadge(qrData) {      
      var url = new URL(qrData);
      var p =  url.searchParams.get('p');
      var ph = url.searchParams.get('ph');
      var destination = replaceLocationParams({p: p, ph: ph});
      window.location.href = destination;
    }
  };
  
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

  var highlightSessionSuggestions = function highlightSessionSuggestions() {
    if(CRM.vars.anoncheckin.sessionSuggestions && CRM.vars.anoncheckin.sessionSuggestions.length) {
      $('input[type=radio][name^="sessionGroup_"]').each(function(idx, el){
        var radioValue = parseInt($(el).val());
        if(CRM.vars.anoncheckin.sessionSuggestions.indexOf(radioValue) != -1) {
          $(el).closest('div.crm-option-label-pair').addClass('anoncheckin-session-suggestion');
        }
      });
    }
  }
  
  $('#anoncheckin-scan-badge').click(
    {
      validateCallback: anoncheckinStaffCallbacks.validateBadge,
      dataSuccessCallback: anoncheckinStaffCallbacks.onDataSuccessBadge
    }, 
    anoncheckinQrScanner.openScanner
  );
  $('#anoncheckin-scan-staffinfo').click(
    {
      validateCallback: anoncheckinStaffCallbacks.validateStaffInfo,
      dataSuccessCallback: anoncheckinStaffCallbacks.onDataSuccessStaffInfo
    }, 
    anoncheckinQrScanner.openScanner
  );

  highlightSessionSuggestions();
});
