CRM.$(function($) {
  var isAnoncheckinqrOriginalValue = $('input#is_anoncheckinqr').is(':checked');
  console.log('isAnoncheckinqrOriginalValue', isAnoncheckinqrOriginalValue);
  
  function isAnoncheckinqrChange() {
    var currentValue = $(this).is(':checked');
    $('#_qf_Layout_refresh-bottom').attr('disabled', (currentValue != isAnoncheckinqrOriginalValue));
  }
  // Add id attribute to bhfe table, so it's easy to reference later.
  CRM.$('input#is_anoncheckin').closest('table').addClass('anoncheckin-bhfe-table');

  CRM.$('tr.crm-badge-layout-form-block-add_barcode').before(CRM.$('input#is_anoncheckinqr').closest('tr'));  
  
  // Remove the bhfe table, but only if it's empty.
  if ($('table.anoncheckin-bhfe-table tr').length == 0) {
    $('table.anoncheckin-bhfe-table').remove();
  }

  CRM.$('input#is_anoncheckinqr').change(isAnoncheckinqrChange);
});