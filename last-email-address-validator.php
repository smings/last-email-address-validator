<?php
/*
Plugin Name: Last Email Address Validator
Plugin URI: https://smings.com/leav/
Description: LEAV provides email address validation and disposable email address blocking for WP registration/comments, WooCommerce, Contact Form 7, WPForms, Ninja Forms and more plugins to come...
Version: 1.4.1
Author: smings
Author URI: https://smings.com/leav/
Text Domain: leav
*/

defined('ABSPATH') or die('Not today!');

require_once("includes/leav-central.inc.php");
require_once("includes/leav-class.inc.php");
require_once("includes/leav-settings-page.inc.php");
require_once("includes/leav-helper-functions.inc.php");

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

class LeavPlugin
{
    private $disposable_email_service_provider_list_file = '';
    private $leav;
    private $central;


    // ----- initialization functions ------------------------------------------

    public function __construct()
    {
        $this->central = new LeavCentral();
        $this->leav = new LastEmailAddressValidator( $this->central );
        $this->disposable_email_service_provider_list_file = plugin_dir_path(__FILE__) . $this->central::$DEA_SERVICE_FILE_RELATIVE_PATH;
        add_action( 'init', array( $this, 'init') );
    }


    public function init() : void
    {
        $this->init_options();
        
        if ( is_admin() )
            add_action('admin_menu', array( new LeavSettingsPage( $this->central, $this->leav ), 'add_settings_page_to_menu' ) );
        $this->init_validation_filters();
        $this->init_custom_error_messages();
    }


    public function set_debug(bool $state) : void
    {
        $this->central::$DEBUG = $state;
    }


    public function activate() : void 
    { 
        $this->init();
    }


    public function deactivate() : void {}


    public function add_plugin_overview_page_links( $links ) : array
    {
        $settings_link = '<a href="options-general.php?page=last-email-address-validator">' . __( 'Settings' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }


    // ----- Validating WordPress registration email address field -------------

    public function validate_registration_email_addresses( $errors, $sanitized_user_login, $entered_email_address )
    {
        if( $this->central::$OPTIONS['validate_wp_standard_user_registration_email_addresses'] == 'no' )
            return $errors;
    
        if( ! $this->validate_email_address( $entered_email_address ) )
            $errors->add( "wp_mail-validator-registration-error", $this->get_email_validation_error_message() );
    
        return $errors;
    }
    

    // ----- Validating WordPress commentator email addresses ------------------

    public function validate_comment_email_addresses($approval_status, $comment_data)
    {
        global $user_ID;
    
        // if a comment is already marked as spam or trash
        // we can return right away
        if ( 
                $this->central::$OPTIONS['validate_wp_comment_user_email_addresses'] == 'no' 
             || $approval_status === "spam"
             || $approval_status === "trash" 
             || ! $approval_status
        ) 
            return $approval_status;
       
        // check if trackbacks/pingbacks are allowed
        if (    isset( $comment_data['comment_type'] )
             && in_array( $comment_data['comment_type'], array( 'trackback', 'pingback' ) )
        )
        {
            if( (    $comment_data['comment_type'] == 'trackback'
                  && $this->central::$OPTIONS['accept_trackbacks'] == 'yes' 
                )
               ||
                (    $comment_data['comment_type'] == 'pingback'
                  && $this->central::$OPTIONS['accept_pingback'] == 'yes' 
                )
            )
                return $approval_status;
            return 'spam';
        }
              
        // if it is a comment and not a logged in user - check mail
        if (    get_option("require_name_email") 
             && !$user_ID 
             && ! $this->validate_email_address( $comment_data['comment_author_email'] )
        )
            return new WP_error('leav_email_address_validation_failed', __('<strong>Error: </strong>', 'leav') . $this->get_email_validation_error_message(), 200 );
        return $approval_status;
    }


    // ----- Validating Contact Form 7 WordPress Plugin ------------------------

    public function validate_cf7_email_addresses($result, $tag)
    {
        if( $this->central::$OPTIONS['validate_cf7_email_fields'] == 'no' )
            return $result;
    
        $tag = new WPCF7_FormTag( $tag );
        $type = $tag->type;
        $name = $tag->name;
        if ( ( $type == "email" || $type == "email*" ) && ! $this->validate_email_address( $_POST[$name] ) )
            $result->invalidate( $tag, $this->get_email_validation_error_message() );
        return $result;
    }


    // ----- Validating WPForms (lite & pro) WordPress Plugin ------------------

    public function validate_wpforms_email_addresses( $fields, $entry, $form_data ) 
    {    
        if( $this->central::$OPTIONS['validate_wpforms_email_fields'] == 'no' )
            return $fields;
    
        $size = count( $fields );
        for( $i = 0; $i < $size; $i++ )
        {
            if(      $fields[$i]['type'] == "email" 
                && ! $this->validate_email_address( $fields[$i]['value'] )
            )
                wpforms()->process->errors[ $form_data['id'] ] [ $i ] = $this->get_email_validation_error_message();
        }
        return $fields;
    }


    // ----- Validating Ninja Forms Plugin -------------------------------------

    public function validate_ninja_forms_email_addresses( $form_data ) 
    {    
        if( $this->central::$OPTIONS['validate_ninja_forms_email_fields'] == 'no' )
            return $form_data;
    
        foreach( $form_data['fields'] as $id => $data )
        {
            if(      preg_match( "/^.*e[^a-zA-Z0-9]{0,2}mail.*$/i", $data['key'] )
                && ! $this->validate_email_address( $data['value'] )
            )         
                $form_data['errors']['fields'][$id] = $this->get_email_validation_error_message();
        }
        return $form_data;
    }


    // ----- Validating MC4WP Mailchimp for WordPress Plugin -------------------

    public function validate_mc4wp_email_addresses( array $errors ) : array
    {    
        if( $this->central::$OPTIONS['validate_mc4wp_email_fields'] != 'yes' )
            return $errors;
        
        foreach( $_POST as $key => $value )
        {
            if(      preg_match( "/^.*e[^a-zA-Z0-9]{0,2}mail.*$/i", $key )
                && ! $this->validate_email_address( $value )
            )
                $errors[] = $this->get_email_validation_error_type();
        }
        return $errors;
    }


    public function add_mc4wp_error_message( array $messages ) : array
    {
        if( $this->central::$OPTIONS['validate_mc4wp_email_fields'] != 'yes' )
            return $messages;

        foreach( $this->central::$VALIDATION_ERROR_LIST as $error_type => $error_message )
            $messages[ $error_type ] = array( 'type' => 'error', 'text' => $error_message );

        return $messages;
    }


    // ---------------- private functions of the class -------------------------

    private function init_options()
    {
        if ( get_option( $this->central::$OPTIONS_NAME ) )
            $this->central::$OPTIONS = get_option( $this->central::$OPTIONS_NAME );
        
        if ( empty( $this->central::$OPTIONS['wp_email_domain'] ) )
            $this->central::$OPTIONS['wp_email_domain'] = $this->leav->get_detected_wp_email_domain();

        $this->leav->set_wordpress_email_domain( $this->central::$OPTIONS['wp_email_domain'] );
            

        // ------- USER DEFINED WHITELISTS & BLACKLISTS AS WELL AS THE INTERNAL LISTS --------------
        // ------- radio button switches
        if ( empty( $this->central::$OPTIONS['use_user_defined_domain_whitelist'] ) )
            $this->central::$OPTIONS['use_user_defined_domain_whitelist'] = 'no';
    
        if ( empty( $this->central::$OPTIONS['use_user_defined_email_whitelist'] ) )
            $this->central::$OPTIONS['use_user_defined_email_whitelist'] = 'no';

        if ( empty( $this->central::$OPTIONS['use_user_defined_domain_blacklist'] ) )
            $this->central::$OPTIONS['use_user_defined_domain_blacklist'] = 'no';

        if ( empty( $this->central::$OPTIONS['use_user_defined_email_blacklist'] ) )
            $this->central::$OPTIONS['use_user_defined_email_blacklist'] = 'no';

        if ( empty( $this->central::$OPTIONS['simulate_email_sending'] ) )
            $this->central::$OPTIONS['simulate_email_sending'] = 'yes';

        // ------- user-defined lists
        if ( empty( $this->central::$OPTIONS['user_defined_domain_whitelist'] ) )
            $this->central::$OPTIONS['user_defined_domain_whitelist'] = '';

        if ( empty( $this->central::$OPTIONS['user_defined_email_whitelist'] ) )
            $this->central::$OPTIONS['user_defined_email_whitelist'] = '';
        
        if ( empty( $this->central::$OPTIONS['user_defined_domain_blacklist'] ) )
            $this->central::$OPTIONS['user_defined_domain_blacklist'] = '';

        if ( empty( $this->central::$OPTIONS['user_defined_email_blacklist'] ) )
            $this->central::$OPTIONS['user_defined_email_blacklist'] = '';

        // ------- internally used lists
        if ( empty( $this->central::$OPTIONS['internal_user_defined_domain_whitelist'] ) )
            $this->central::$OPTIONS['internal_user_defined_domain_whitelist'] = array();

        if ( empty( $this->central::$OPTIONS['internal_user_defined_email_whitelist'] ) )
            $this->central::$OPTIONS['internal_user_defined_email_whitelist'] = array();
        
        if ( empty( $this->central::$OPTIONS['internal_user_defined_domain_blacklist'] ) )
            $this->central::$OPTIONS['internal_user_defined_domain_blacklist'] = array();

        if ( empty( $this->central::$OPTIONS['internal_user_defined_email_blacklist'] ) )
            $this->central::$OPTIONS['internal_user_defined_email_blacklist'] = array();



        // ----- ing  -----------------------

        if ( empty( $this->central::$OPTIONS['spam_email_addresses_blocked_count'] ) )
            $this->central::$OPTIONS['spam_email_addresses_blocked_count'] = "0";
                
        if ( empty( $this->central::$OPTIONS['accept_pingbacks'] ) )
            $this->central::$OPTIONS['accept_pingbacks'] = 'yes';
        
        if ( empty( $this->central::$OPTIONS['accept_trackbacks'] ) )
            $this->central::$OPTIONS['accept_trackbacks'] = 'yes';

    
        // ----- DEA list option values -----------------------
        
        if ( empty( $this->central::$OPTIONS['block_disposable_email_address_services'] ) )
            $this->central::$OPTIONS['block_disposable_email_address_services'] = 'yes';

        if(    empty( $this->central::$OPTIONS['dea_list_version'] ) 
            || $this->central::$OPTIONS['dea_list_version'] != $this->central::$PLUGIN_VERSION 
        )
            $this->read_dea_list_file();
        
        if( empty( $this->central::$OPTIONS['dea_domains'] ) )
            $this->central::$OPTIONS['dea_domains'] = array();

        if( empty( $this->central::$OPTIONS['dea_mx_domains'] ) )
            $this->central::$OPTIONS['dea_mx_domains'] = array();

        if( empty( $this->central::$OPTIONS['dea_mx_ips'] ) )
            $this->central::$OPTIONS['dea_mx_ips'] = array();

        // ------ Validation of functions / plugins switches ---

        if ( empty( $this->central::$OPTIONS['validate_wp_standard_user_registration_email_addresses'] ) )
            $this->central::$OPTIONS['validate_wp_standard_user_registration_email_addresses'] = 'yes';
        
        if ( empty( $this->central::$OPTIONS['validate_wp_comment_user_email_addresses'] ) )
            $this->central::$OPTIONS['validate_wp_comment_user_email_addresses'] = 'yes';
        
        if ( empty( $this->central::$OPTIONS['validate_woocommerce_email_fields'] ) )
            $this->central::$OPTIONS['validate_woocommerce_email_fields'] = 'yes';
        
        if ( empty( $this->central::$OPTIONS['validate_cf7_email_fields'] ) )
            $this->central::$OPTIONS['validate_cf7_email_fields'] = 'yes';
    
        if ( empty( $this->central::$OPTIONS['validate_wpforms_email_fields'] ) )
            $this->central::$OPTIONS['validate_wpforms_email_fields'] = 'yes';
    
        if ( empty( $this->central::$OPTIONS['validate_ninja_forms_email_fields'] ) )
            $this->central::$OPTIONS['validate_ninja_forms_email_fields'] = 'yes';

        if ( empty( $this->central::$OPTIONS['validate_mc4wp_email_fields'] ) )
            $this->central::$OPTIONS['validate_mc4wp_email_fields'] = 'yes';


        // ------ Custom error message override fields -------------------------

        if ( empty( $this->central::$OPTIONS['cem_email_addess_syntax_error'] ) )
            $this->central::$OPTIONS['cem_email_addess_syntax_error'] = '';

        if ( empty( $this->central::$OPTIONS['cem_email_domain_is_blacklisted'] ) )
            $this->central::$OPTIONS['cem_email_domain_is_blacklisted'] = '';

        if ( empty( $this->central::$OPTIONS['cem_email_address_is_blacklisted'] ) )
            $this->central::$OPTIONS['cem_email_address_is_blacklisted'] = '';

        if ( empty( $this->central::$OPTIONS['cem_email_domain_has_no_mx_record'] ) )
            $this->central::$OPTIONS['cem_email_domain_has_no_mx_record'] = '';

        if ( empty( $this->central::$OPTIONS['cem_email_domain_on_dea_blacklist'] ) )
            $this->central::$OPTIONS['cem_email_domain_on_dea_blacklist'] = '';

        if ( empty( $this->central::$OPTIONS['cem_simulated_sending_of_email_failed'] ) )
            $this->central::$OPTIONS['cem_simulated_sending_of_email_failed'] = '';

        if ( empty( $this->central::$OPTIONS['cem_general_email_validation_error'] ) )
            $this->central::$OPTIONS['cem_general_email_validation_error'] = '';
        

        // ------ Main Menu Use & Positions -------------------

        if ( empty( $this->central::$OPTIONS['use_main_menu'] ) )
            $this->central::$OPTIONS['use_main_menu'] = 'no';

        if ( empty( $this->central::$OPTIONS['main_menu_position'] ) )
            $this->central::$OPTIONS['main_menu_position'] = 24;

        if ( empty( $this->central::$OPTIONS['settings_menu_position'] ) )
            $this->central::$OPTIONS['main_menu_position'] = 24;

        update_option($this->central::$OPTIONS_NAME, $this->central::$OPTIONS);
    }


    private function init_validation_filters()
    {

        if(    $this->central::$OPTIONS['validate_wp_standard_user_registration_email_addresses'] == 'yes' 
            && get_option("users_can_register") == 1 )
            add_filter("registration_errors", array( $this, 'validate_registration_email_addresses' ), 99, 3);
    
        if(  $this->central::$OPTIONS['validate_wp_comment_user_email_addresses'] == 'yes' )
            add_filter("pre_comment_approved", array( $this, 'validate_comment_email_addresses' ), 99, 2);
    
        if (    is_plugin_active( "woocommerce/woocommerce.php" )
             && $this->central::$OPTIONS['validate_woocommerce_email_fields'] == 'yes'
        )
            add_filter("woocommerce_registration_errors", array( $this, 'validate_registration_email_addresses'), 99, 3   );
    
        if (    is_plugin_active( "contact-form-7/wp-contact-form-7.php" ) 
             && $this->central::$OPTIONS['validate_cf7_email_fields'] == 'yes'
        )
        {
            add_filter("wpcf7_validate_email", array( $this, 'validate_cf7_email_addresses'), 20, 2);
            add_filter("wpcf7_validate_email*", array( $this, 'validate_cf7_email_addresses'), 20, 2);
        }
    
        if ( ( 
                   is_plugin_active( "wpforms-lite/wpforms.php" )  
                || is_plugin_active( "wpforms/wpforms.php"      ) 
             )
             &&
             $this->central::$OPTIONS['validate_wpforms_email_fields'] == 'yes'
           )
            add_action( "wpforms_process", array( $this, 'validate_wpforms_email_addresses'), 10, 3 );
    
        if (    is_plugin_active( "ninja-forms/ninja-forms.php" )
             && $this->central::$OPTIONS['validate_ninja_forms_email_fields'] == 'yes'
        )
            add_filter("ninja_forms_submit_data", array( $this, 'validate_ninja_forms_email_addresses'), 20, 2);

        if (    is_plugin_active( 'mailchimp-for-wp/mailchimp-for-wp.php' )
             && $this->central::$OPTIONS['validate_mc4wp_email_fields'] == 'yes'
        )
        {
            add_filter('mc4wp_form_messages', array( $this, 'add_mc4wp_error_message'), 10, 2 );
            add_filter('mc4wp_form_errors', array( $this, 'validate_mc4wp_email_addresses'), 10, 2 );
        }
    }


    private function init_custom_error_messages() : void
    {
        if( ! empty ( $this->central::$OPTIONS['cem_email_addess_syntax_error'] ) )
            $this->central::$VALIDATION_ERROR_LIST['email_addess_syntax_error'] = $this->central::$OPTIONS['cem_email_addess_syntax_error'];

        if( ! empty ( $this->central::$OPTIONS['cem_email_domain_is_blacklisted'] ) )
            $this->central::$VALIDATION_ERROR_LIST['email_domain_is_blacklisted'] = $this->central::$OPTIONS['cem_email_domain_is_blacklisted'];

        if( ! empty ( $this->central::$OPTIONS['cem_email_address_is_blacklisted'] ) )
            $this->central::$VALIDATION_ERROR_LIST['email_address_is_blacklisted'] = $this->central::$OPTIONS['cem_email_address_is_blacklisted'];

        if( ! empty ( $this->central::$OPTIONS['cem_email_domain_has_no_mx_record'] ) )
            $this->central::$VALIDATION_ERROR_LIST['email_domain_has_no_mx_record'] = $this->central::$OPTIONS['cem_email_domain_has_no_mx_record'];

        if( ! empty ( $this->central::$OPTIONS['cem_email_domain_on_dea_blacklist'] ) )
            $this->central::$VALIDATION_ERROR_LIST['email_domain_on_dea_blacklist'] = $this->central::$OPTIONS['cem_email_domain_on_dea_blacklist'];

        if( ! empty ( $this->central::$OPTIONS['cem_simulated_sending_of_email_failed'] ) )
            $this->central::$VALIDATION_ERROR_LIST['simulated_sending_of_email_failed'] = $this->central::$OPTIONS['cem_simulated_sending_of_email_failed'];

        if( ! empty ( $this->central::$OPTIONS['cem_general_email_validation_error'] ) )
            $this->central::$VALIDATION_ERROR_LIST['general_email_validation_error'] = $this->central::$OPTIONS['cem_general_email_validation_error'];
    }


    private function validate_email_address( string $email_address ) : bool
    {
        if( ! $this->leav->validate_email_address_syntax( $email_address ) )
            return $this->increment_count_of_blocked_email_addresses();

        elseif(    $this->central::$OPTIONS['use_user_defined_domain_whitelist'] == 'yes' 
            && ! empty( $this->central::$OPTIONS['internal_user_defined_domain_whitelist'] )
            && $this->leav->check_if_email_domain_is_on_user_defined_whitelist( $this->central::$OPTIONS['internal_user_defined_domain_whitelist'] )
          )
            return true;

        elseif(    $this->central::$OPTIONS['use_user_defined_email_whitelist'] == 'yes' 
            && ! empty( $this->central::$OPTIONS['internal_user_defined_email_whitelist'] )
            && $this->leav->check_if_email_address_is_on_user_defined_whitelist( $this->central::$OPTIONS['internal_user_defined_email_whitelist'] )
          )
            return true;

        elseif(    $this->central::$OPTIONS['use_user_defined_domain_blacklist'] == 'yes' 
            && ! empty( $this->central::$OPTIONS['internal_user_defined_domain_blacklist'] )
            && $this->leav->check_if_email_domain_is_on_user_defined_blacklist( $this->central::$OPTIONS['internal_user_defined_domain_blacklist'] )
          )
            return $this->increment_count_of_blocked_email_addresses();

        elseif(    $this->central::$OPTIONS['use_user_defined_email_blacklist'] == 'yes' 
            && ! empty( $this->central::$OPTIONS['internal_user_defined_email_blacklist'] )
            && $this->leav->check_if_email_address_is_on_user_defined_blacklist( $this->central::$OPTIONS['internal_user_defined_email_blacklist'] )
          )
            return $this->increment_count_of_blocked_email_addresses();

        elseif(    $this->central::$OPTIONS['block_disposable_email_address_services'] == 'yes' 
                && $this->leav->check_if_email_address_is_from_dea_service( $this->central::$OPTIONS['dea_domains'], $this->central::$OPTIONS['dea_mx_domains'], $this->central::$OPTIONS['dea_mx_ips'] )
        )
            return $this->increment_count_of_blocked_email_addresses();

        // if we already tried to collect the MX data and there is none, we can 
        // just return false right away
        elseif(    $this->central::$OPTIONS['block_disposable_email_address_services'] == 'yes'
            && empty( $this->leav->mx_server_ips )
        )
            return $this->increment_count_of_blocked_email_addresses();

        elseif(    $this->central::$OPTIONS['simulate_email_sending'] = 'yes'
                && ! $this->leav->simulate_sending_an_email() )
            return $this->increment_count_of_blocked_email_addresses();
    
        // when we are done with all validations, we return true
        return true;

    }


    private function get_email_validation_error_message() : string
    {
        $error_type = $this->get_email_validation_error_type();
        return $this->central::$VALIDATION_ERROR_LIST[$error_type];
    }


    private function get_email_validation_error_type() : string
    {
        if    ( ! $this->leav->is_email_address_syntax_valid )
            return 'email_addess_syntax_error';
        elseif( $this->leav->is_email_domain_on_user_defined_blacklist ) 
            return 'email_domain_is_blacklisted';
        elseif( $this->leav->is_email_address_on_user_defined_blacklist ) 
            return 'email_address_is_blacklisted';
        elseif( ! $this->leav->email_domain_has_MX_records ) 
            return 'email_domain_has_no_mx_record';
        elseif( $this->leav->is_email_address_from_dea_service ) 
            return 'email_domain_on_dea_blacklist';            
        elseif( ! $this->leav->simulated_sending_succeeded ) 
            return 'simulated_sending_of_email_failed';
        return 'general_email_validation_error';
    }


    private function read_dea_list_file() : bool
    {
        if(    ! file_exists( $this->disposable_email_service_provider_list_file ) 
            || ! is_readable( $this->disposable_email_service_provider_list_file ) 
        )
            return false;

        $lines = file( $this->disposable_email_service_provider_list_file, FILE_IGNORE_NEW_LINES );
        $this->central::$OPTIONS['dea_list_version'] = array_shift($lines);

        $this->central::$OPTIONS['dea_domains'] = array();
        $this->central::$OPTIONS['dea_mx_domains'] = array();
        $this->central::$OPTIONS['dea_mx_ips'] = array();

        foreach( $lines as $id => $line )
        {
            if(    preg_match( $this->central::$COMMENT_LINE_REGEX, $line )
                || preg_match( $this->central::$EMPTY_LINE_REGEX,   $line )
            )
                continue;

            if( substr( $line, 0, 7 ) == 'domain:')
            {
                $domain = substr( $line, 7 );
                if( $this->leav->sanitize_and_validate_domain( $domain ) )
                    array_push( $this->central::$OPTIONS['dea_domains'], $domain );
            }
            elseif( substr( $line, 0, 3 ) == 'mx:' )
            {
                $domain = substr( $line, 3 );
                if( $this->leav->sanitize_and_validate_domain( $domain ) )
                    array_push( $this->central::$OPTIONS['dea_mx_domains'], $domain );
            }
            elseif( substr( $line, 0, 3 ) == 'ip:' )
            {
                $ip = substr( $line, 3 );
                if( $this->leav->sanitize_and_validate_ip( $ip ) )
                    array_push( $this->central::$OPTIONS['dea_mx_ips'], $ip );
            }
        }
        if(    ! empty( $this->central::$OPTIONS['dea_domains'] ) 
            && ! empty( $this->central::$OPTIONS['dea_mx_domains'] ) 
            && ! empty( $this->central::$OPTIONS['dea_mx_ips'] ) 
        )
            return true;
        return false;
    }


    private function increment_count_of_blocked_email_addresses() : bool
    {
        $this->central::$OPTIONS['spam_email_addresses_blocked_count'] = ($this->central::$OPTIONS['spam_email_addresses_blocked_count'] + 1);
        update_option( $this->central::$OPTIONS_NAME, $this->central::$OPTIONS );
        // always returns false for use in failed validation if-statements for less code
        return false;
    }

}

if( class_exists( 'LeavPlugin' ) )
{
    $leav_plugin = new LeavPlugin();
    $leav_plugin->set_debug( true );
}

add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), array( $leav_plugin, 'add_plugin_overview_page_links' ) );
register_activation_hook(   __FILE__, array( $leav_plugin, 'activate' ) );
register_deactivation_hook( __FILE__, array( $leav_plugin, 'deactivate' ) );
// we use uninstall.php as a safe method for uninstalling the plugin.
// the register_uninstall_hook method doesn't allow a class function for this
// so that we are forced to do it within the uninstall.php file

?>