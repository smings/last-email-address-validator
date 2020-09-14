<?php

class LeavCentral
{
  public static $COMMENT_LINE_REGEX = "/^\s*(#|\/\/)/";
  public static $dea_service_file_relative_path = 'data/disposable_email_service_provider_list.txt';
  public static $debug = false;
  public static $DOMAIN_REGEX = "/^[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\.[a-z]{2,18}$/i";
  public static $EMAIL_ADDRESS_REGEX = "/^[0-9a-z_]([-_\.]*[0-9a-z])*\+?[0-9a-z]*([-_\.]*[0-9a-z])*@[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\.[a-z]{2,18}$/i";
  public static $EMPTY_LINE_REGEX = "/^\s*[\r\n]+$/";
  public static $IP_ADDRESS_REGEX = "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/";
  public static $menu_inline_icon = 'data:image/svg+xml;base64,PHN2ZyAgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzNTEuNDUgMjA3Ljc4Ij48ZGVmcz48c3R5bGU+LmNscy0xe2ZpbGw6bm9uZTtzdHJva2U6IzIzMWYyMDtzdHJva2UtbWl0ZXJsaW1pdDoxMDtzdHJva2Utd2lkdGg6MjZweDt9PC9zdHlsZT48L2RlZnM+PGcgaWQ9IkxheWVyXzIiIGRhdGEtbmFtZT0iTGF5ZXIgMiI+PGcgaWQ9IkxFQVZfTG9nbyIgZGF0YS1uYW1lPSJMRUFWIExvZ28iPjxwYXRoIGZpbGw9ImJsYWNrIiBjbGFzcz0iY2xzLTEiIGQ9Ik0xNi4zNCwyMDUuNDFjLTE2LjA2LTg2LjUsMjQtMTg5LjksMTU5LjM5LTE5Mi4zMiIvPjxwYXRoIGZpbGw9ImJsYWNrIiBjbGFzcz0iY2xzLTEiIGQ9Ik00LjA3LDE5NC42M2MxMzIuNzEsMCwxNzguODYtNTguODQsMTU4LjMyLTE5Mi42NiIvPjxwYXRoIGZpbGw9ImJsYWNrIiBjbGFzcz0iY2xzLTEiIGQ9Ik0zMzUuMTEsMjA1LjQxYzE2LjA3LTg2LjUtMjQtMTg5LjktMTU5LjM4LTE5Mi4zMiIvPjxwYXRoIGZpbGw9ImJsYWNrIiBjbGFzcz0iY2xzLTEiIGQ9Ik0zNDcuMzgsMTk0LjYzQzIxNC42NywxOTQuNjUsMTY4LjUzLDEzNS43OSwxODkuMDYsMiIvPjwvZz48L2c+PC9zdmc+';
  public static $options;
  public static $options_name = 'leav_options';
  public static $plugin_display_name_full = "LEAV - Last Email Address Validator";
  public static $plugin_display_name_long = "Last Email Address Validator";
  public static $plugin_display_name_short = "LEAV";
  public static $plugin_menu_name = "LEAV - Last Email Address Validator";
  public static $plugin_menu_name_short = "LEAV";
  public static $plugin_settings_page = '/wp-admin/options-general.php?page=leav-settings-page.inc';
  public static $plugin_version = '1.4.2';
  public static $plugin_website = "https://smings.com/last-email-address-validator/";
  public static $settings_page_logo_url = 'assets/icon-128x128.png';
  public static $SANITIZE_DOMAIN_REGEX = "/[^0-9a-zA-Z-\.]/";
  public static $SANITIZE_IP_REGEX = "/[^0-9\.]/";


  public static $radio_button_fields = array(
  	'accept_pingbacks', 
  	'accept_trackbacks', 
  	'use_user_defined_domain_whitelist', 
  	'use_user_defined_email_whitelist', 
  	'use_user_defined_domain_blacklist', 
  	'use_user_defined_email_blacklist', 
  	'block_disposable_email_address_services', 
  	'validate_wp_standard_user_registration_email_addresses', 
  	'validate_wp_comment_user_email_addresses', 
  	'validate_woocommerce_email_fields', 
  	'validate_cf7_email_fields', 
  	'validate_wpforms_email_fields', 
  	'validate_ninja_forms_email_fields' 
  );
  public static $radio_button_values = array( 'yes', 'no' );

  public static $domain_fields = array( 'wp_email_domain' );
  public static $domain_list_fields = array( 
  	'user_defined_domain_whitelist', 
  	'user_defined_domain_blacklist'
  );
  public static $email_list_fields = array( 
  	'user_defined_email_whitelist', 
  	'user_defined_email_blacklist'
  );

}

?>