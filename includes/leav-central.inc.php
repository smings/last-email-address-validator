<?php

class LeavCentral
{

  public static $COMMENT_LINE_REGEX = "/^\s*(#|\/\/)/";
  public static $DEA_SERVICE_FILE_RELATIVE_PATH = 'data/disposable_email_service_provider_list.txt';
  public static $DEBUG = false;
  // public static $DOMAIN_FIELDS = array();
  public static $DOMAIN_LIST_FIELDS = array( 'user_defined_domain_whitelist', 'user_defined_domain_blacklist' );
  public static $DOMAIN_REGEX = "/^[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\.[a-z]{2,18}$/i";
  public static $EMAIL_ADDRESS_REGEX = "/^[0-9a-z_]([-_\.]*[0-9a-z])*\+?[0-9a-z]*([-_\.]*[0-9a-z])*@[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\.[a-z]{2,18}$/i";
  public static $EMAIL_FIELD_NAME_REGEX = "/^.*e[^a-zA-Z0-9]{0,2}mail.*$/i";
  public static $EMAIL_LIST_FIELDS = array( 'user_defined_email_whitelist', 'user_defined_email_blacklist' );
  public static $EMPTY_LINE_REGEX = "/^\s*[\r\n]+$/";
  public static $IP_ADDRESS_REGEX = "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/";
  public static $MENU_INLINE_ICON = 'data:image/svg+xml;base64,PHN2ZyAgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzNTEuNDUgMjA3Ljc4Ij48ZGVmcz48c3R5bGU+LmNscy0xe2ZpbGw6bm9uZTtzdHJva2U6IzIzMWYyMDtzdHJva2UtbWl0ZXJsaW1pdDoxMDtzdHJva2Utd2lkdGg6MjZweDt9PC9zdHlsZT48L2RlZnM+PGcgaWQ9IkxheWVyXzIiIGRhdGEtbmFtZT0iTGF5ZXIgMiI+PGcgaWQ9IkxFQVZfTG9nbyIgZGF0YS1uYW1lPSJMRUFWIExvZ28iPjxwYXRoIGZpbGw9ImJsYWNrIiBjbGFzcz0iY2xzLTEiIGQ9Ik0xNi4zNCwyMDUuNDFjLTE2LjA2LTg2LjUsMjQtMTg5LjksMTU5LjM5LTE5Mi4zMiIvPjxwYXRoIGZpbGw9ImJsYWNrIiBjbGFzcz0iY2xzLTEiIGQ9Ik00LjA3LDE5NC42M2MxMzIuNzEsMCwxNzguODYtNTguODQsMTU4LjMyLTE5Mi42NiIvPjxwYXRoIGZpbGw9ImJsYWNrIiBjbGFzcz0iY2xzLTEiIGQ9Ik0zMzUuMTEsMjA1LjQxYzE2LjA3LTg2LjUtMjQtMTg5LjktMTU5LjM4LTE5Mi4zMiIvPjxwYXRoIGZpbGw9ImJsYWNrIiBjbGFzcz0iY2xzLTEiIGQ9Ik0zNDcuMzgsMTk0LjYzQzIxNC42NywxOTQuNjUsMTY4LjUzLDEzNS43OSwxODkuMDYsMiIvPjwvZz48L2c+PC9zdmc+';
  public static $INTEGER_GEZ_FIELDS = array( 'main_menu_position', 'settings_menu_position' );
  public static $INTEGER_GEZ_REGEX = "/^(0|[1-9]\d*)$/";
  public static $OPTIONS;
  public static $OPTIONS_NAME = 'leav_options';
  public static $PLACEHOLDER_EMAIL_DOMAIN = 'your-domain.com';
  public static $PLUGIN_DISPLAY_NAME_FULL = "LEAV - Last Email Address Validator";
  public static $PLUGIN_DISPLAY_NAME_LONG = "Last Email Address Validator";
  public static $PLUGIN_DISPLAY_NAME_SHORT = "LEAV";
  public static $PLUGIN_MENU_NAME = "LEAV - Last Email Address Validator";
  public static $PLUGIN_MENU_NAME_SHORT = "LEAV";
  public static $PLUGIN_SETTING_PAGE = '/wp-admin/options-general.php?page=leav-settings-page.inc';
  public static $PLUGIN_VERSION = '1.4.3';
  public static $PLUGIN_WEBSITE = 'https://smings.com/last-email-address-validator/';
  public static $RADIO_BUTTON_FIELDS = array(
    'accept_pingbacks', 
    'accept_trackbacks', 
    'block_disposable_email_address_services', 
    'simulate_email_sending',
    'use_main_menu',
    'use_user_defined_domain_blacklist', 
    'use_user_defined_domain_whitelist', 
    'use_user_defined_email_blacklist', 
    'use_user_defined_email_whitelist', 
    'validate_cf7_email_fields', 
    'validate_formidable_forms_email_fields',
    'validate_mc4wp_email_fields',
    'validate_ninja_forms_email_fields', 
    'validate_woocommerce_email_fields', 
    'validate_wp_comment_user_email_addresses', 
    'validate_wp_standard_user_registration_email_addresses', 
    'validate_wpforms_email_fields'
  );
  public static $RADIO_BUTTON_VALUES = array( 'yes', 'no' );
  public static $SETTINGS_PAGE_LOGO_URL = 'assets/icon-128x128.png';
  public static $SANITIZE_DOMAIN_REGEX = "/[^0-9a-zA-Z-\.]/";
  public static $SANITIZE_IP_REGEX = "/[^0-9\.]/";
  public static $TEXT_FIELDS = array(
    'cem_email_addess_syntax_error',
    'cem_email_domain_is_blacklisted',
    'cem_email_address_is_blacklisted',
    'cem_email_domain_has_no_mx_record',
    'cem_email_domain_on_dea_blacklist',
    'cem_simulated_sending_of_email_failed',
    'cem_general_email_validation_error'
  );
  public static $VALIDATION_ERROR_LIST = array();
  public static $VALIDATION_ERROR_LIST_DEFAULTS = array();

  public function __construct()
  {
    $this->init_error_messages();
  }


  private function init_error_messages() : void
  {
    $this::$VALIDATION_ERROR_LIST_DEFAULTS = 
    array
    (
          'email_addess_syntax_error'         => __( 'The entered email address syntax is invalid.', 'leav'),
          'email_domain_is_blacklisted'       => __( 'The entered email address\'s domain is blacklisted.', 'leav'),
          'email_address_is_blacklisted'      => __( 'The entered email address is blacklisted.', 'leav'),
          'email_domain_has_no_mx_record'     => __( 'The entered email address\'s domain doesn\'t have any mail servers.', 'leav'),
          'email_domain_on_dea_blacklist'     => __( 'We don\'t accept email addresses from disposable email address services (DEA). Please use a regular email address.', 'leav'),
          'simulated_sending_of_email_failed' => __( 'The entered email address got rejected while trying to send an email to it.', 'leav'),
          'general_email_validation_error'    => __( 'The entered email address is invalid.', 'leav')
    );    
    $this::$VALIDATION_ERROR_LIST = $this::$VALIDATION_ERROR_LIST_DEFAULTS;
  }

}

?>