// Attach click handlers to s and p buttons.
$(document).ready(function () {
  var change_p_link_click = function change_p_link_click(e) {
    e.preventDefault();
    Swal.fire({
      html: "Your device is locked to the badge for <strong>" + $(this).data('participantname') + "</strong>. If that's incorrect, please see a staff member for help.",
      icon: "warning"
    });
  }

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
  $('#change-p-link').click(change_p_link_click);
});
