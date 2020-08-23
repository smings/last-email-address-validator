jQuery(document).ready(() => {
  if (jQuery("#default_gateway").length) {
    jQuery("#default_gateway").mask("099.099.099.099");
  }
  if (jQuery("#disposable_email_service_domain_blacklist_restore").length) {
      jQuery("#disposable_email_service_domain_blacklist_restore").click((event) => {
        jQuery('input[name=leav_options_update_type]').val('restore_disposable_email_service_domain_blacklist');
      });
  }
  if(jQuery("#disposable_email_service_domain_blacklist").length) {
    jQuery("#disposable_email_service_domain_blacklist_line_count").html(getLineCount(jQuery("#disposable_email_service_domain_blacklist")));
    jQuery("#disposable_email_service_domain_blacklist").on('input', function() {
        jQuery("#disposable_email_service_domain_blacklist_line_count").html(getLineCount(jQuery("#disposable_email_service_domain_blacklist")));
    });
  }
  if(jQuery("#user_defined_blacklist").length) {
    jQuery("#user_defined_blacklist_line_count").html(getLineCount(jQuery("#user_defined_blacklist")));
    jQuery("#user_defined_blacklist").on('input', function() {
        jQuery("#user_defined_blacklist_line_count").html(getLineCount(jQuery("#user_defined_blacklist")));
    });
  }
});

function getLineCount(element) {
    return element.val().split(/\n/).filter(function(el) { return el.length != 0}).length;
}