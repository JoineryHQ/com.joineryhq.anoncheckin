CRM.$(function($) {
  // Store the original value of the 'Display QR code for Anonymous QR session check-in?' option.
  var isAnoncheckinqrOriginalValue = $('input#is_anoncheckinqr').is(':checked');

  // Change handler for 'Display QR code for Anonymous QR session check-in?' option.
  function isAnoncheckinqrChange() {
    // If the 'Display QR code for Anonymous QR session check-in?' option has changed
    // on-page, then the 'save and preview' button will not reflect that change,
    // so disable that button.
    var currentValue = $(this).is(':checked');
    $('#_qf_Layout_refresh-bottom').attr('disabled', (currentValue != isAnoncheckinqrOriginalValue));
  }

  // Add id attribute to bhfe table, so it's easy to reference later.
  CRM.$('input#is_anoncheckin').closest('table').addClass('anoncheckin-bhfe-table');

  // Move the 'Display QR code for Anonymous QR session check-in?' option into the main form.
  CRM.$('tr.crm-badge-layout-form-block-add_barcode').before(CRM.$('input#is_anoncheckinqr').closest('tr'));

  // Remove the bhfe table, but only if it's empty.
  if ($('table.anoncheckin-bhfe-table tr').length == 0) {
    $('table.anoncheckin-bhfe-table').remove();
  }

  // Set change handler for 'Display QR code for Anonymous QR session check-in?' option.
  CRM.$('input#is_anoncheckinqr').change(isAnoncheckinqrChange);
});