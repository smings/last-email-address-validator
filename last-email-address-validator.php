<?php
/*
Plugin Name: Last Email Address Validator
Plugin URI: https://smings.com/leav/
Description: LEAV provides email address validation and disposable email address blocking for WP registration/comments, WooCommerce, Contact Form 7, WPForms, Ninja Forms and more plugins to come...
Version: 1.4.0
Author: smings
Author URI: https://smings.com/leav/
Text Domain: leav
*/

defined('ABSPATH') or die('Not today!');

require_once("includes/leav-class.inc.php");
require_once("includes/leav-settings-page.inc.php");
require_once("includes/leav-helper-functions.inc.php");

class LeavPlugin
{

    private $debug = false;
    private $disposable_email_service_list_file;
    private $leav;
    private $options;
    private static $options_name = "leav_options";

    public function __construct()
    {
        add_action( 'init', array( $this, 'init') );
    }


    public function init()
    {
        $this->leav = new LastEmailAddressValidator();
        $this->init_options();
        // $this->disposable_email_service_list_file = plugin_dir_path(__FILE__) . "data/disposable_email_service_provider_list.txt";
        
        if ( is_admin() )
            add_action('admin_menu', array( new LeavSettingsPage( self::$options_name, $this->options), 'add_settings_page_to_menu' ) );

        $this->init_validation_filters();
    }


    public function set_debug(bool $state) : void
    {
        $this->debug = $state;
    }


    private function activate()
    {
        $this->init();
    }

    private function deactivate()
    {

    }

    public function validate_registration_email_addresses($errors, $sanitized_user_login, $entered_email_address)
    {
        if( $this->options['validate_wp_standard_user_registration_email_addresses'] == 'no' )
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
            $this->options['validate_wp_comment_user_email_addresses'] == 'no' ||
            $approval_status === "spam" || 
            $approval_status === "trash" 
        ) 
            return $approval_status;
       
        // check if trackbacks are allowed
        if (    isset( $comment_data["comment_type"] )
             && $comment_data["comment_type"] == "trackback"
             && $this->options['accept_trackbacks'] == "yes"
        )
        {
            return $approval_status;
        }
        else
        {
            return "trash";
        }
        
        // check if pingbacks are allowed
        if (    isset( $comment_data["comment_type"] ) 
             && $comment_data["comment_type"] == "pingback"
             && $this->options['accept_pingbacks'] == "yes"
        )
        {
            return $approval_status;
        }
        else 
        {
                return "trash";
        }
        
        // if it is a comment and not a logged in user - check mail
        if (    get_option("require_name_email") 
             && !$user_ID 
             && ! $this->validate_email_address( $comment_data["comment_author_email"] )
        )
        {
            $approval_status = "spam";
        }
        return $approval_status;
    }


    public function validate_cf7_email_addresses($result, $tag)
    {
        if( $this->options['validate_cf7_email_fields'] == 'no' )
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
        if( $this->options['validate_wpforms_email_fields'] == 'no' )
            return $fields;
    
        $size = count( $fields );
        for( $i = 0; $i < $size; $i++ )
        {
            if(      $fields[$i]["type"] == "email" 
                && ! $this->validate_email_address( $fields[$i]["value"] )
            )
            {
                wpforms()->process->errors[ $form_data["id"] ] [ $i ] = $this->get_email_validation_error_text(   );
            }
        }
        return $fields;
    }


    public function validate_ninja_forms_email_addresses( $form_data ) 
    {    
        if( $this->options['validate_ninja_forms_email_fields'] == 'no' )
            return $form_data;
    
        foreach( $form_data["fields"] as $field )
        {
            if( preg_match( "/^.*e[^a-zA-Z0-9]{0,2}mail.*$/i", $field["key"] ) == 1   
                && ! $this->validate_email_address( $field["value"] )
            )
                $form_data["errors"]["fields"][$field['id']] = $this->get_email_validation_error_text();
        }
        return $form_data;
    }


    // ---------------- private functions of the class -------------------------

    private function init_options()
    {
        if ( get_option( self::$options_name ) )
            $this->options = get_option( self::$options_name );
        
        if ( empty( $this->options['wp_email_domain'] ) )
            $this->options['wp_email_domain'] = $this->leav->get_detected_wp_email_domain();
        else
            $this->leav->set_wordpress_email_domain( $this->options['wp_email_domain'] );
        
        if ( empty( $this->options['spam_email_addresses_blocked_count'] ) )
            $this->options['spam_email_addresses_blocked_count'] = "0";
                
        if ( empty( $this->options['accept_pingbacks'] ) )
            $this->options['accept_pingbacks'] = "yes";
        
        if ( empty( $this->options['accept_trackbacks'] ) )
            $this->options['accept_trackbacks'] = "yes";
    

        // ------- USER DEFINED WHITELISTS & BLACKLISTS AS WELL AS THE INTERNAL LISTS --------------

        if ( empty( $this->options['use_user_defined_domain_whitelist'] ) )
            $this->options['use_user_defined_domain_whitelist'] = 'no';
    
        if ( empty( $this->options['use_user_defined_email_whitelist'] ) )
            $this->options['use_user_defined_email_whitelist'] = 'no';

        if ( empty( $this->options['use_user_defined_domain_blacklist'] ) )
            $this->options['use_user_defined_domain_blacklist'] = 'no';

        if ( empty( $this->options['use_user_defined_email_blacklist'] ) )
            $this->options['use_user_defined_email_blacklist'] = 'no';

        if ( empty( $this->options['user_defined_domain_whitelist'] ) )
            $this->options['user_defined_domain_whitelist'] = '';

        if ( empty( $this->options['user_defined_email_whitelist'] ) )
            $this->options['user_defined_email_whitelist'] = '';
        
        if ( empty( $this->options['user_defined_domain_blacklist'] ) )
            $this->options['user_defined_domain_blacklist'] = '';

        if ( empty( $this->options['user_defined_email_blacklist'] ) )
            $this->options['user_defined_email_blacklist'] = '';

        if ( empty( $this->options['internal_user_defined_domain_whitelist'] ) )
            $this->options['internal_user_defined_domain_whitelist'] = array();

        if ( empty( $this->options['internal_user_defined_email_whitelist'] ) )
            $this->options['user_defined_email_whitelist'] = array();
        
        if ( empty( $this->options['internal_user_defined_domain_blacklist'] ) )
            $this->options['user_defined_domain_blacklist'] = array();

        if ( empty( $this->options['internal_user_defined_email_blacklist'] ) )
            $this->options['user_defined_email_blacklist'] = array();

    

        
        if ( empty( $this->options['block_disposable_email_address_services'] ) )
            $this->options['block_disposable_email_address_services'] = "yes";
        
        if( empty( $this->options['dea_domains'] ) )
            $this->read_dea_data();

        if ( empty( $this->options['validate_wp_standard_user_registration_email_addresses'] ) )
            $this->options['validate_wp_standard_user_registration_email_addresses'] = "yes";
        
        if ( empty( $this->options['validate_wp_comment_user_email_addresses'] ) )
            $this->options['validate_wp_comment_user_email_addresses'] = "yes";
        
        if ( empty( $this->options['validate_woocommerce_email_fields'] ) )
            $this->options['validate_woocommerce_email_fields'] = "yes";
        
        if ( empty( $this->options['validate_cf7_email_fields'] ) )
            $this->options['validate_cf7_email_fields'] = "yes";
    
        if ( empty( $this->options['validate_wpforms_email_fields'] ) )
            $this->options['validate_wpforms_email_fields'] = "yes";
    
        if ( empty( $this->options['validate_ninja_forms_email_fields'] ) )
            $this->options['validate_ninja_forms_email_fields'] = "yes";
        
        update_option(self::$options_name, $this->options);
    }


    private function init_validation_filters()
    {

        if(    $this->options['validate_wp_standard_user_registration_email_addresses'] == "yes" 
            && get_option("users_can_register") == 1 )
            add_filter("registration_errors", array( $this, 'validate_registration_email_addresses' ), 99, 3);
    
        if(  $this->options['validate_wp_comment_user_email_addresses'] == "yes" )
            add_filter("pre_comment_approved", array( $this, 'validate_comment_email_addresses' ), 99, 2);
    
        if (    is_plugin_active( "woocommerce/woocommerce.php" )
             && $this->options['validate_woocommerce_email_fields'] == "yes"
        )
            add_filter("woocommerce_registration_errors", array( $this, 'validate_registration_email_addresses'), 99, 3   );
    
        if (    is_plugin_active( "contact-form-7/wp-contact-form-7.php" ) 
             && $this->options['validate_cf7_email_fields'] == "yes"
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
             $this->options['validate_wpforms_email_fields'] == "yes"
           )
            add_action( "wpforms_process", array( $this, 'validate_wpforms_email_addresses'), 10, 3 );
    
        if (    is_plugin_active( "ninja-forms/ninja-forms.php" )
             && $this->options['validate_ninja_forms_email_fields'] == "yes"
        )
            add_filter("ninja_forms_submit_data", array( $this, 'validate_ninja_forms_email_addresses'), 99, 3);
    }


    private function validate_email_address( string $email_address ) : bool
    {
        if( ! $this->leav->validate_email_address_syntax( $email_address ) )
        {
            if( $this->debug )
                write_log("Email address syntax validation failed");
            $this->increment_count_of_blocked_email_addresses();
            return false;
        }
        if( $this->debug )
            write_log("Email address syntax validation succeeded");


        if(    $this->options['use_user_defined_domain_blacklist'] == 'yes' 
            && ! empty( $this->options['user_defined_domain_blacklist'] )
            && $this->leav->check_if_email_is_on_user_defined_blacklist( $this->options['user_defined_domain_blacklist'] )
          )
        {
            if( $this->debug )
                write_log("Email address is on user-defined domain blacklist");
            return false;
        }


        if(! $this->leav->simulate_sending_an_email() )
        {
            if( $this->debug )
                write_log("Simulated sending failed");
    
            $this->increment_count_of_blocked_email_addresses();
            return false;
        }
        if( $this->debug )
            write_log("Simulated email sending succeeded");
    
        // when we are done with all validations, we return true
        return true;

    }


    private function get_email_validation_error_text()
    {
        if    ( $this->leav->is_email_address_syntax_valid === false ) 
             return __( "The entered email address syntax is invalid.", "leav");

        elseif( $this->leav->is_email_domain_on_user_defined_blacklist === true ) 
             return __( "The entered email address's domain is blacklisted.", "leav");

        elseif( $this->leav->is_email_address_on_user_defined_blacklist === true ) 
             return __( "The entered email address's is blacklisted.", "leav");
    
        elseif( $this->leav->email_domain_has_MX_records   === false ) 
            return __( "The entered email address's domain doesn't have any mail servers.", "leav");
    
        elseif( $this->leav->simulated_sending_succeeded   === false ) 
            return __( "The entered email address got rejected while trying to send an email to it.", "leav")   ;
        else
            return __( "The entered email address is invalid.", "leav");
    }


    private function read_dea_data()
    {

    }


    private function increment_count_of_blocked_email_addresses()
    {
        $this->options['spam_email_addresses_blocked_count'] = ($this->options['spam_email_addresses_blocked_count'] + 1);
        update_option( self::$options_name, $this->options );
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