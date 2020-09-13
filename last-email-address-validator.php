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

class LeavPlugin
{
    private $disposable_email_service_provider_list_file = '';
    private $leav;
    private $central;


    public function __construct()
    {
        $this->central = new LeavCentral();
        $this->leav = new LastEmailAddressValidator( $this->central );
        $this->disposable_email_service_provider_list_file = plugin_dir_path(__FILE__) . $this->central::$dea_service_file_relative_path;
        add_action( 'init', array( $this, 'init') );
    }


    public function init() : void
    {
        $this->init_options();
        
        if ( is_admin() )
            add_action('admin_menu', array( new LeavSettingsPage( $this->central, $this->leav ), 'add_settings_page_to_menu' ) );
        $this->init_validation_filters();
    }


    public function set_debug(bool $state) : void
    {
        $this->central::$debug = $state;
    }


    public function activate() : void 
    { 
        $this->init();
    }

    public function deactivate() : void {}

    public function validate_registration_email_addresses( $errors, $sanitized_user_login, $entered_email_address )
    {
        if( $this->central::$options['validate_wp_standard_user_registration_email_addresses'] == 'no' )
            return $errors;
    
        if( ! $this->validate_email_address( $entered_email_address ) )
            $errors->add( "wp_mail-validator-registration-error", $this->get_email_validation_error_text() );
    
        return $errors;
    }
    

    public function validate_comment_email_addresses($approval_status, $comment_data) : string
    {
        global $user_ID;
    
        // if a comment is already marked as spam or trash
        // we can return right away
        if ( 
            $this->central::$options['validate_wp_comment_user_email_addresses'] == 'no' ||
            $approval_status === "spam" || 
            $approval_status === "trash" 
        ) 
            return $approval_status;
       
        // check if trackbacks are allowed
        if (    isset( $comment_data['comment_type'] )
             && $comment_data['comment_type'] == "trackback"
             && $this->central::$options['accept_trackbacks'] == "yes"
        )
            return $approval_status;
        else
            return "trash";
        
        // check if pingbacks are allowed
        if (    isset( $comment_data['comment_type'] ) 
             && $comment_data['comment_type'] == "pingback"
             && $this->central::$options['accept_pingbacks'] == "yes"
        )
            return $approval_status;
        else
            return "trash";
        
        // if it is a comment and not a logged in user - check mail
        if (    get_option("require_name_email") 
             && !$user_ID 
             && ! $this->validate_email_address( $comment_data['comment_author_email'] )
        )
        {
            // now we try to return a new WP_error
            wp_die( $this->get_email_validation_error_text() );
            return new WP_error('email_validation_error', $this->get_email_validation_error_text() );


            // this is the old version
            // $approval_status = "spam";
        }
        return $approval_status;
    }


    public function validate_cf7_email_addresses($result, $tag)
    {
        if( $this->central::$options['validate_cf7_email_fields'] == 'no' )
            return $result;
    
        $tag = new WPCF7_FormTag( $tag );
        $type = $tag->type;
        $name = $tag->name;
        if ( ( $type == "email" || $type == "email*" ) && ! $this->validate_email_address( $_POST[$name] ) )
            $result->invalidate( $tag, $this->get_email_validation_error_text() );
        return $result;
    }


    public function validate_wpforms_email_addresses( $fields, $entry, $form_data ) 
    {    
        if( $this->central::$options['validate_wpforms_email_fields'] == 'no' )
            return $fields;
    
        $size = count( $fields );
        for( $i = 0; $i < $size; $i++ )
        {
            if(      $fields[$i]['type'] == "email" 
                && ! $this->validate_email_address( $fields[$i]['value'] )
            )
            {
                wpforms()->process->errors[ $form_data['id'] ] [ $i ] = $this->get_email_validation_error_text();
            }
        }
        return $fields;
    }


    public function validate_ninja_forms_email_addresses( $form_data ) 
    {    
        if( $this->central::$options['validate_ninja_forms_email_fields'] == 'no' )
            return $form_data;
    
        if( $this->central::$debug )
        {
            write_log("");
            write_log("=======================================");
            write_log("Ninja Forms Form Data BEFORE validation");
            write_log("=======================================");
            write_log($form_data);
            write_log("=======================================");
        }

        foreach( $form_data['fields'] as $id => $data )
        {

            if( $this->central::$debug )
            {
                write_log( "==================================" );
                write_log( "Looking at field id  " . $id );
                write_log( "Looking at field id2 " . $data['id'] );
                write_log( "Looking at field key " . $data['key'] );
                write_log( "Looking at field key " . $data['value'] );
            }


            if(      preg_match( "/^.*e[^a-zA-Z0-9]{0,2}mail.*$/i", $data['key'] )
                && ! $this->validate_email_address( $data['value'] )
            )
            {
                if( $this->central::$debug )
                {
                    write_log( "==================================" );
                    write_log( "   Validation for field failed" );
                    write_log( "==================================" );
                }
                $form_data['errors']['fields'][$id] = $this->get_email_validation_error_text();
            }
            elseif(    $this->central::$debug 
                    && preg_match( "/^.*e[^a-zA-Z0-9]{0,2}mail.*$/i", $data['key'] )
                    && $this->validate_email_address( $data['value'] )
            )
            {
                write_log( "==================================" );
                write_log( "Validation for field succeded" );
                write_log( "==================================" );
            }
            elseif(      $this->central::$debug 
                    && ! preg_match( "/^.*e[^a-zA-Z0-9]{0,2}mail.*$/i", $data['key'] )
            )
            {
                write_log( "==================================" );
                write_log( "     NO Validation necessary" );
                write_log( "==================================" );
            }

        }

        if( $this->central::$debug )
        {
            write_log("");
            write_log("=======================================");
            write_log("Ninja Forms Form Data AFTER validation");
            write_log("=======================================");
            write_log($form_data);
            write_log("=======================================");


            // $orig = '"äÄöÖüÜß_ _=-Email Field 2-=+=_&^%$#@!~"';
            // $filtered = normalize_field_name_string( $orig );
            
            // write_log("Original string: '$orig'");
            // write_log("Filtered string: '$filtered'");

        }

        return $form_data;
    }


    // ---------------- private functions of the class -------------------------

    private function init_options()
    {
        if ( get_option( $this->central::$options_name ) )
            $this->central::$options = get_option( $this->central::$options_name );
        
        if ( empty( $this->central::$options['wp_email_domain'] ) )
            $this->central::$options['wp_email_domain'] = $this->leav->get_detected_wp_email_domain();
        else
            $this->leav->set_wordpress_email_domain( $this->central::$options['wp_email_domain'] );
            

        // ------- USER DEFINED WHITELISTS & BLACKLISTS AS WELL AS THE INTERNAL LISTS --------------
        // ------- radio button switches
        if ( empty( $this->central::$options['use_user_defined_domain_whitelist'] ) )
            $this->central::$options['use_user_defined_domain_whitelist'] = 'no';
    
        if ( empty( $this->central::$options['use_user_defined_email_whitelist'] ) )
            $this->central::$options['use_user_defined_email_whitelist'] = 'no';

        if ( empty( $this->central::$options['use_user_defined_domain_blacklist'] ) )
            $this->central::$options['use_user_defined_domain_blacklist'] = 'no';

        if ( empty( $this->central::$options['use_user_defined_email_blacklist'] ) )
            $this->central::$options['use_user_defined_email_blacklist'] = 'no';

        // ------- user-defined lists
        if ( empty( $this->central::$options['user_defined_domain_whitelist'] ) )
            $this->central::$options['user_defined_domain_whitelist'] = '';

        if ( empty( $this->central::$options['user_defined_email_whitelist'] ) )
            $this->central::$options['user_defined_email_whitelist'] = '';
        
        if ( empty( $this->central::$options['user_defined_domain_blacklist'] ) )
            $this->central::$options['user_defined_domain_blacklist'] = '';

        if ( empty( $this->central::$options['user_defined_email_blacklist'] ) )
            $this->central::$options['user_defined_email_blacklist'] = '';

        // ------- internally used lists
        if ( empty( $this->central::$options['internal_user_defined_domain_whitelist'] ) )
            $this->central::$options['internal_user_defined_domain_whitelist'] = array();

        if ( empty( $this->central::$options['internal_user_defined_email_whitelist'] ) )
            $this->central::$options['internal_user_defined_email_whitelist'] = array();
        
        if ( empty( $this->central::$options['internal_user_defined_domain_blacklist'] ) )
            $this->central::$options['internal_user_defined_domain_blacklist'] = array();

        if ( empty( $this->central::$options['internal_user_defined_email_blacklist'] ) )
            $this->central::$options['internal_user_defined_email_blacklist'] = array();



        // ----- ing  -----------------------

        if ( empty( $this->central::$options['spam_email_addresses_blocked_count'] ) )
            $this->central::$options['spam_email_addresses_blocked_count'] = "0";
                
        if ( empty( $this->central::$options['accept_pingbacks'] ) )
            $this->central::$options['accept_pingbacks'] = "yes";
        
        if ( empty( $this->central::$options['accept_trackbacks'] ) )
            $this->central::$options['accept_trackbacks'] = "yes";

    
        // ----- DEA list option values -----------------------
        
        if ( empty( $this->central::$options['block_disposable_email_address_services'] ) )
            $this->central::$options['block_disposable_email_address_services'] = "yes";

        if(    empty( $this->central::$options['dea_list_version'] ) 
            || $this->central::$options['dea_list_version'] != $this->central::$plugin_version 
        )
            $this->read_dea_list_file();
        
        if( empty( $this->central::$options['dea_domains'] ) )
            $this->central::$options['dea_domains'] = array();

        if( empty( $this->central::$options['dea_mx_domains'] ) )
            $this->central::$options['dea_mx_domains'] = array();

        if( empty( $this->central::$options['dea_mx_ips'] ) )
            $this->central::$options['dea_mx_ips'] = array();

        // ------ Validation of functions / plugins switches ---

        if ( empty( $this->central::$options['validate_wp_standard_user_registration_email_addresses'] ) )
            $this->central::$options['validate_wp_standard_user_registration_email_addresses'] = "yes";
        
        if ( empty( $this->central::$options['validate_wp_comment_user_email_addresses'] ) )
            $this->central::$options['validate_wp_comment_user_email_addresses'] = "yes";
        
        if ( empty( $this->central::$options['validate_woocommerce_email_fields'] ) )
            $this->central::$options['validate_woocommerce_email_fields'] = "yes";
        
        if ( empty( $this->central::$options['validate_cf7_email_fields'] ) )
            $this->central::$options['validate_cf7_email_fields'] = "yes";
    
        if ( empty( $this->central::$options['validate_wpforms_email_fields'] ) )
            $this->central::$options['validate_wpforms_email_fields'] = "yes";
    
        if ( empty( $this->central::$options['validate_ninja_forms_email_fields'] ) )
            $this->central::$options['validate_ninja_forms_email_fields'] = "yes";
        
        update_option($this->central::$options_name, $this->central::$options);
    }


    private function init_validation_filters()
    {

        if(    $this->central::$options['validate_wp_standard_user_registration_email_addresses'] == "yes" 
            && get_option("users_can_register") == 1 )
            add_filter("registration_errors", array( $this, 'validate_registration_email_addresses' ), 99, 3);
    
        if(  $this->central::$options['validate_wp_comment_user_email_addresses'] == "yes" )
            add_filter("pre_comment_approved", array( $this, 'validate_comment_email_addresses' ), 99, 2);
    
        if (    is_plugin_active( "woocommerce/woocommerce.php" )
             && $this->central::$options['validate_woocommerce_email_fields'] == "yes"
        )
            add_filter("woocommerce_registration_errors", array( $this, 'validate_registration_email_addresses'), 99, 3   );
    
        if (    is_plugin_active( "contact-form-7/wp-contact-form-7.php" ) 
             && $this->central::$options['validate_cf7_email_fields'] == "yes"
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
             $this->central::$options['validate_wpforms_email_fields'] == "yes"
           )
            add_action( "wpforms_process", array( $this, 'validate_wpforms_email_addresses'), 10, 3 );
    
        if (    is_plugin_active( "ninja-forms/ninja-forms.php" )
             && $this->central::$options['validate_ninja_forms_email_fields'] == "yes"
        )
            add_filter("ninja_forms_submit_data", array( $this, 'validate_ninja_forms_email_addresses'), 99, 3);
    }


    private function validate_email_address( string $email_address ) : bool
    {
        if( ! $this->leav->validate_email_address_syntax( $email_address ) )
        {
            if( $this->central::$debug )
                write_log("Email address syntax validation failed");
            $this->increment_count_of_blocked_email_addresses();
            return false;
        }
        if( $this->central::$debug )
            write_log("Email address syntax validation succeeded");

        if(    $this->central::$options['use_user_defined_domain_whitelist'] == 'yes' 
            && ! empty( $this->central::$options['internal_user_defined_domain_whitelist'] )
            && $this->leav->check_if_email_domain_is_on_user_defined_whitelist( $this->central::$options['internal_user_defined_domain_whitelist'] )
          )
        {
            if( $this->central::$debug )
                write_log("Email address is on user-defined domain whitelist");
            return true;
        }

        if(    $this->central::$options['use_user_defined_email_whitelist'] == 'yes' 
            && ! empty( $this->central::$options['internal_user_defined_email_whitelist'] )
            && $this->leav->check_if_email_address_is_on_user_defined_whitelist( $this->central::$options['internal_user_defined_email_whitelist'] )
          )
        {
            if( $this->central::$debug )
                write_log("Email address is on user-defined email address whitelist");
            return true;
        }


        if(    $this->central::$options['use_user_defined_domain_blacklist'] == 'yes' 
            && ! empty( $this->central::$options['internal_user_defined_domain_blacklist'] )
            && $this->leav->check_if_email_domain_is_on_user_defined_blacklist( $this->central::$options['internal_user_defined_domain_blacklist'] )
          )
        {
            if( $this->central::$debug )
                write_log("Email address is on user-defined domain blacklist");
            return false;
        }

        if(    $this->central::$options['use_user_defined_email_blacklist'] == 'yes' 
            && ! empty( $this->central::$options['internal_user_defined_email_blacklist'] )
            && $this->leav->check_if_email_address_is_on_user_defined_blacklist( $this->central::$options['internal_user_defined_email_blacklist'] )
          )
        {
            if( $this->central::$debug )
                write_log("Email address is on user-defined email blacklist");
            return false;
        }

        if(    $this->central::$options['block_disposable_email_address_services'] == 'yes' 
            && $this->leav->check_if_email_address_is_from_dea_service( $this->central::$options['dea_domains'], $this->central::$options['dea_mx_domains'], $this->central::$options['dea_mx_ips'] )
        )
        {

            if( $this->central::$debug )
                write_log("Email address is on DEA blacklist.");
            return false;
        }

        // if we already tried to collect the MX data and there is none, we can 
        // just return false right away
        if(    $this->central::$options['block_disposable_email_address_services'] == 'yes'
            && empty( $this->leav->mx_server_ips )
        )
            return false;

        if(! $this->leav->simulate_sending_an_email() )
        {
            if( $this->central::$debug )
                write_log("Simulated sending failed");
    
            $this->increment_count_of_blocked_email_addresses();
            return false;
        }
    
        // when we are done with all validations, we return true
        return true;

    }


    private function get_email_validation_error_text()
    {
        // if ( $this->central::$debug )
        // {
        //     write_log("\$this->leav->is_email_address_syntax_valid = " . $this->leav->is_email_address_syntax_valid );
        //     write_log("\$this->leav->is_email_domain_on_user_defined_blacklist = " . $this->leav->is_email_domain_on_user_defined_blacklist );
        //     write_log("\$this->leav->is_email_address_on_user_defined_blacklist = " . $this->leav->is_email_address_on_user_defined_blacklist );
        //     write_log("\$this->leav->email_domain_has_MX_records = " . $this->leav->email_domain_has_MX_records );
        //     write_log("\$this->leav->is_email_address_from_dea_service = " . $this->leav->is_email_address_from_dea_service );
        //     write_log("\$this->leav->simulated_sending_succeeded = " . $this->leav->simulated_sending_succeeded );
        // }

        if    ( $this->leav->is_email_address_syntax_valid === false ) 
             return __( "The entered email address syntax is invalid.", "leav");

        elseif( $this->leav->is_email_domain_on_user_defined_blacklist === true ) 
             return __( "The entered email address's domain is blacklisted.", "leav");

        elseif( $this->leav->is_email_address_on_user_defined_blacklist === true ) 
             return __( "The entered email address is blacklisted.", "leav");

        elseif( $this->leav->email_domain_has_MX_records === false ) 
            return __( "The entered email address's domain doesn't have any mail servers.", "leav");

        elseif( $this->leav->is_email_address_from_dea_service === true ) 
            return __( "We don't accept email addresses from disposable email address services (DEA). Please use a regular email address.", "leav");
    
        elseif( $this->leav->simulated_sending_succeeded === false ) 
            return __( "The entered email address got rejected while trying to send an email to it.", "leav");

        else
            return __( "The entered email address is invalid.", "leav");
    }


    private function read_dea_list_file() : bool
    {
        if(    ! file_exists( $this->disposable_email_service_provider_list_file ) 
            || ! is_readable( $this->disposable_email_service_provider_list_file ) 
        )
            return false;

        $lines = file( $this->disposable_email_service_provider_list_file, FILE_IGNORE_NEW_LINES );
        $this->central::$options['dea_list_version'] = array_shift($lines);

        $this->central::$options['dea_domains'] = array();
        $this->central::$options['dea_mx_domains'] = array();
        $this->central::$options['dea_mx_ips'] = array();

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
                    array_push( $this->central::$options['dea_domains'], $domain );
            }
            elseif( substr( $line, 0, 3 ) == 'mx:' )
            {
                $domain = substr( $line, 3 );
                if( $this->leav->sanitize_and_validate_domain( $domain ) )
                    array_push( $this->central::$options['dea_mx_domains'], $domain );
            }
            elseif( substr( $line, 0, 3 ) == 'ip:' )
            {
                $ip = substr( $line, 3 );
                if( $this->leav->sanitize_and_validate_ip( $ip ) )
                    array_push( $this->central::$options['dea_mx_ips'], $ip );
            }
        }
        if(    ! empty( $this->central::$options['dea_domains'] ) 
            && ! empty( $this->central::$options['dea_mx_domains'] ) 
            && ! empty( $this->central::$options['dea_mx_ips'] ) 
        )
            return true;
        return false;
    }


    private function increment_count_of_blocked_email_addresses()
    {
        $this->central::$options['spam_email_addresses_blocked_count'] = ($this->central::$options['spam_email_addresses_blocked_count'] + 1);
        update_option( $this->central::$options_name, $this->central::$options );
    }

}

if( class_exists( 'LeavPlugin' ) )
{
    $leav_plugin = new LeavPlugin();
    $leav_plugin->set_debug( true );
}

register_activation_hook(   __FILE__, array( $leav_plugin, 'activate' ) );
register_deactivation_hook( __FILE__, array( $leav_plugin, 'deactivate' ) );
// we use uninstall.php as a safe method for uninstalling the plugin.
// the register_uninstall_hook method doesn't allow a class function for this
// so that we are forced to do it within the uninstall.php file

?>