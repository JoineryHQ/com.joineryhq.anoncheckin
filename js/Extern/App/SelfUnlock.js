// Attach click handlers to s and p buttons.
$(document).ready(function () {

  var send_email_form_submit = function send_email_form_submit() {
    $("#anoncheckin-send-email").html('Sending <i class="fa fa-spinner fa-spin"></i>');
    $("#anoncheckin-send-email").attr('disabled', true);
  }

  $("#anoncheckin-send-email").closest('form').on("submit", send_email_form_submit);
});
