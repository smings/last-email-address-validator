<?php

class LeavCentral
{
	public static $debug = false;
	public static $plugin_version = '1.4.1';
	public static $dea_service_file_relative_path = 'data/disposable_email_service_provider_list.txt';
	public static $options;
	public static $options_name = 'leav_options';


  public static $plugin_display_name_long = "LEAV - Last Email Address Validator";
  public static $plugin_display_name_short = "LEAV";
  public static $plugin_menu_name = "LEAV - Last Email Address Validator";
  public static $plugin_website = "https://smings.com/last-email-address-validator/";

  public static $COMMENT_LINE_REGEX = "/^\s*(#|\/\/)/";
  public static $DOMAIN_REGEX = "/^[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\.[a-z]{2,18}$/i";
  public static $EMAIL_ADDRESS_REGEX = "/^[0-9a-z_]([-_\.]*[0-9a-z])*\+?[0-9a-z]*([-_\.]*[0-9a-z])*@[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\.[a-z]{2,18}$/i";
  public static $EMPTY_LINE_REGEX = "/^\s*[\r\n]+$/";
  public static $IP_ADDRESS_REGEX = "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/";
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