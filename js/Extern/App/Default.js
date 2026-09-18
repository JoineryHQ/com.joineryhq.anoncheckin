// Attach click handlers to s and p buttons.
$(document).ready(function () {
  $('#anoncheckin-scan-badge').click(
    {
      validateCallback: anoncheckinAppCallbacks.urlIsValid,
      dataSuccessCallback: anoncheckinAppCallbacks.onDataSuccess
    }, 
    anoncheckinQrScanner.openScanner
  );
  $('#anoncheckin-scan-session').click(
    {
      validateCallback: anoncheckinAppCallbacks.urlIsValid,
      dataSuccessCallback: anoncheckinAppCallbacks.onDataSuccess
    }, 
    anoncheckinQrScanner.openScanner
  );
});
