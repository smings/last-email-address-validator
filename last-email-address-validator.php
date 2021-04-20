<?php
/*
 * Plugin Name: Last Email Address Validator
 * Plugin URI: https://wordpress.org/plugins/last-email-address-validator/
 * Description: LEAV provides free deep email validation for WP registration/comments, WooCommerce, Elementor Pro, CF7, WPForms, Gravity Forms, Ninja Forms ...
 * Version: 1.7.1
 * Author: smings
 * Author URI: https://wordpress.org/plugins/last-email-address-validator/
 * Text Domain: last-email-address-validator
 * Domain Path: /languages/
 */

defined( 'ABSPATH' ) or die( 'Not today!' );

require_once( 'includes/leav-central.inc.php' );
require_once( 'includes/leav-class.inc.php' );
require_once( 'includes/leav-settings-page.inc.php' );
require_once( 'includes/leav-helper-functions.inc.php' );

if ( ! function_exists( 'is_plugin_active' ) ){
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

class LeavPlugin
{
    private $disposable_email_service_provider_list_file = '';
    private $free_email_address_provider_list_file = '';
    private $role_based_recipient_name_file = '';
    private $leav;
    private $central;


    // ----- initialization functions ------------------------------------------

    public function __construct()
    {
        $this->central = new LeavCentral();
        $this->leav = new LastEmailAddressValidator( $this->central );
        $this->disposable_email_service_provider_list_file = plugin_dir_path( __FILE__ ) . $this->central::$DEA_SERVICE_FILE_RELATIVE_PATH;
        $this->free_email_address_provider_list_file = plugin_dir_path( __FILE__ ) . $this->central::$FREE_EMAIL_ADDRESS_PROVIDER_DOMAIN_LIST_FILE;
        $this->role_based_recipient_name_file = plugin_dir_path( __FILE__ ) . $this->central::$ROLE_BASED_RECIPIENT_NAME_FILE_RELATIVE_PATH;
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'plugins_loaded', [$this, 'load_text_domain' ] );
    }


    public function init() : void
    {
        $this->init_options();
        if ( is_admin() )
        {
            // if we couldn't determine the email domain and are not on the
            // leav's settings page, we show a global admin notification
            if (    empty( $this->central::$OPTIONS[ 'wp_email_domain' ] ) 
                 && strtok($_SERVER[ 'REQUEST_URI' ], '#' ) != $this->central::$PLUGIN_SETTING_PAGE
            )
                add_action( 'admin_notices', array( new LeavSettingsPage( $this, $this->central, $this->leav ), 'add_global_warning_wp_email_domain_not_detected' ) );
            add_action( 'admin_menu', array( new LeavSettingsPage( $this, $this->central, $this->leav ), 'add_settings_page_to_menu' ) );
        }

        $this->init_validation_filters();
        $this->init_custom_error_messages();
    }


    public function set_debug( bool $state ) : void
    {
        $this->central::$DEBUG = $state;
    }


    public function activate() : void
    {
        $this->init();
    }


    public function deactivate() : void {}


    public function load_text_domain() : void 
    {
        load_plugin_textdomain( 'last-email-address-validator' );
    }


    public function add_plugin_overview_page_links( $links ) : array
    {
        // before we add the settings link, we need to differentiate where the actual settings page is
        // currently located
        if( $this->central::$OPTIONS[ 'use_main_menu' ] == 'no' )
            $settings_link = '<a href="options-general.php?page=leav-settings-page.inc">' . esc_html__( 'Settings', 'last-email-address-validator' ) . '</a>';
        else
            $settings_link = '<a href="admin.php?page=leav-settings-page.inc">' . esc_html__( 'Settings', 'last-email-address-validator' ) . '</a>';

        array_unshift( $links, $settings_link );
        return $links;
    }


    public function get_email_validation_error_message() : string
    {
        if( ! $this->get_email_validation_error_type() )
            return 'Error: Could not find corresponding error message for error type "' . $this->get_email_validation_error_type() . '"' ;
        return $this->central::$VALIDATION_ERROR_LIST[ $this->get_email_validation_error_type() ];
    }


    public function get_email_validation_error_type() : string
    {
        return $this->leav->error_type;
    }


    // ----- Main functionality for validating email addresses -----------------
    //
    public function validate_email_address( string $email_address, bool $increment_counter = true ) : bool
    {
        $is_domain_whitelisted = false;
        $is_email_whitelisted = false;
        $is_recipient_name_whitelisted = false;

        if( ! $this->leav->validate_email_address_syntax( $email_address ) )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        // ----- allow catch-all recipient name email addresses ----------------

        elseif(    $this->central::$OPTIONS[ 'allow_recipient_name_catch_all_email_addresses' ] == 'no'
                && $this->leav->is_email_recipient_name_catch_all()
        )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        // ----- Whitelists -----------------------------------------------------

        if(    $this->central::$OPTIONS[ 'use_user_defined_domain_whitelist' ] == 'yes'
            && ! empty( $this->central::$OPTIONS[ 'user_defined_domain_whitelist' ] )
            && $this->leav->is_email_domain_on_user_defined_whitelist( $this->central::$OPTIONS[ 'user_defined_domain_whitelist' ] )
          )
            $is_domain_whitelisted = true;

        if(    $this->central::$OPTIONS[ 'use_user_defined_email_whitelist' ] == 'yes'
            && ! empty( $this->central::$OPTIONS[ 'user_defined_email_whitelist' ] )
            && $this->leav->check_if_email_address_is_on_user_defined_whitelist( $this->central::$OPTIONS[ 'user_defined_email_whitelist' ] )
          )
            $is_email_whitelisted = true;

        if(    $this->central::$OPTIONS[ 'use_user_defined_recipient_name_whitelist' ] == 'yes'
            && ! empty( $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist' ] )
            && $this->leav->is_recipient_name_on_list( $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist' ], '', false )
          )
            $is_recipient_name_whitelisted = true;

        // ----- Blacklists -----------------------------------------------------

        if(    $this->central::$OPTIONS[ 'use_user_defined_domain_blacklist' ] == 'yes'
            && ! $is_domain_whitelisted
            && ! $is_email_whitelisted
            && ! empty( $this->central::$OPTIONS[ 'user_defined_domain_blacklist' ] )
            && $this->leav->is_email_domain_on_user_defined_blacklist( $this->central::$OPTIONS[ 'user_defined_domain_blacklist' ] )
        )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        if(    $this->central::$OPTIONS[ 'use_free_email_address_provider_domain_blacklist' ] == 'yes'
            && ! $is_domain_whitelisted
            && ! $is_email_whitelisted
            && ! empty( $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ] )
            && $this->leav->is_email_domain_on_free_email_address_provider_domain_list( $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ] )
        )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }


        if(    $this->central::$OPTIONS[ 'use_user_defined_email_blacklist' ] == 'yes'
            && ! $is_email_whitelisted
            && ! empty( $this->central::$OPTIONS[ 'user_defined_email_blacklist' ] )
            && $this->leav->check_if_email_address_is_on_user_defined_blacklist( $this->central::$OPTIONS[ 'user_defined_email_blacklist' ] )
        )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        if(    $this->central::$OPTIONS[ 'use_user_defined_recipient_name_blacklist' ] == 'yes'
                && ! $is_recipient_name_whitelisted
                && ! $is_email_whitelisted
                && $this->leav->is_recipient_name_on_list( $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist' ], 'recipient_name_is_blacklisted' )
        )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        if(    $this->central::$OPTIONS[ 'use_role_based_recipient_name_blacklist' ] == 'yes'
                && ! $is_recipient_name_whitelisted
                && ! $is_email_whitelisted
                && (    $this->leav->is_collapsed_recipient_name_empty()
                     || $this->leav->is_recipient_name_on_list( $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ], 'recipient_name_is_role_based' )
                   )
        )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        if(    $this->central::$OPTIONS[ 'block_disposable_email_address_services' ] == 'yes'
                && $this->leav->check_if_email_address_is_from_dea_service( $this->central::$OPTIONS[ 'dea_domains' ], $this->central::$OPTIONS[ 'dea_mx_domains' ], $this->central::$OPTIONS[ 'dea_mx_ips' ] )
        )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        // if we already tried to collect the MX data and there is none, we can
        // just return false right away
        if(    $this->central::$OPTIONS[ 'block_disposable_email_address_services' ] == 'yes'
            && empty( $this->leav->mx_server_ips )
        )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        if(    $this->central::$OPTIONS[ 'simulate_email_sending' ] == 'yes'
                && ! $this->leav->simulate_sending_an_email()
        )
        {
            $this->leav->set_error_type( 'simulated_sending_of_email_failed' );
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        if(    $this->central::$OPTIONS[ 'allow_catch_all_domains' ] == 'no'
            && $this->leav->is_catch_all_domain()
        )
        {
            if( $increment_counter )
                $this->increment_count_of_blocked_email_addresses();
            return false;
        }

        // when we are done with all validations, we return true
        return true;

    }


    // ----- Validating WordPress registration email address field -------------

    public function validate_registration_email_addresses( $errors, $sanitized_user_login, $entered_email_address )
    {
        if( $this->central::$OPTIONS[ 'validate_wp_standard_user_registration_email_addresses' ] == 'no' )
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
                $this->central::$OPTIONS[ 'validate_wp_comment_user_email_addresses' ] == 'no'
             || $approval_status === "spam"
             || $approval_status === "trash"
             || ! $approval_status
        )
            return $approval_status;

        // check if trackbacks/pingbacks are allowed
        if (    isset( $comment_data[ 'comment_type' ] )
             && in_array( $comment_data[ 'comment_type' ], array( 'trackback', 'pingback' ) )
        )
        {
            if( (    $comment_data[ 'comment_type' ] == 'trackback'
                  && $this->central::$OPTIONS[ 'accept_trackbacks' ] == 'yes'
                )
               ||
                (    $comment_data[ 'comment_type' ] == 'pingback'
                  && $this->central::$OPTIONS[ 'accept_pingback' ] == 'yes'
                )
            )
                return $approval_status;
            return 'spam';
        }

        // if it is a comment and not a logged in user - check mail
        if (    get_option( "require_name_email" )
             && !$user_ID
             && ! $this->validate_email_address( $comment_data[ 'comment_author_email' ] )
        )
            return new WP_error( 'leav_email_address_validation_failed', '<strong>' . esc_html__( 'Error: ', 'last-email-address-validator' ) . '</strong>' . esc_html( $this->get_email_validation_error_message(), 200 ) );
        return $approval_status;
    }


    // ----- Validating Contact Form 7 WordPress Plugin ------------------------

    public function validate_cf7_email_addresses($result, $tag)
    {
        if( $this->central::$OPTIONS[ 'validate_cf7_email_fields' ] == 'no' )
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
        if( $this->central::$OPTIONS[ 'validate_wpforms_email_fields' ] == 'no' )
            return $fields;

        $size = count( $fields );
        for( $i = 0; $i < $size; $i++ )
        {
            if(      $fields[$i][ 'type' ] == "email"
                && ! $this->validate_email_address( $fields[$i][ 'value' ] )
            )
                wpforms()->process->errors[ $form_data[ 'id' ] ] [ $i ] = $this->get_email_validation_error_message();
        }
        return $fields;
    }


    // ----- Validating Ninja Forms Plugin -------------------------------------

    public function validate_ninja_forms_email_addresses( $form_data )
    {
        if( $this->central::$OPTIONS[ 'validate_ninja_forms_email_fields' ] == 'no' )
            return $form_data;

        foreach( $form_data[ 'fields' ] as $id => $data )
        {
            if(      preg_match( $this->central::$EMAIL_FIELD_NAME_REGEX, $data[ 'key' ] )
                && ! $this->validate_email_address( $data[ 'value' ] )
            )
                $form_data[ 'errors' ][ 'fields' ][$id] = $this->get_email_validation_error_message();
        }
        return $form_data;
    }


    // ----- Validating MC4WP Mailchimp for WordPress Plugin -------------------

    public function validate_mc4wp_email_addresses( array $errors ) : array
    {
        if( $this->central::$OPTIONS[ 'validate_mc4wp_email_fields' ] != 'yes' )
            return $errors;

        foreach( $_POST as $key => $value )
        {
            if(      preg_match( $this->central::$EMAIL_FIELD_NAME_REGEX, $key )
                && ! $this->validate_email_address( $value )
            )
                $errors[] = $this->get_email_validation_error_type();
                // $errors[] = $this->get_email_validation_error_type();
        }
        return $errors;
    }


    public function add_mc4wp_error_message( array $messages ) : array
    {
        if( $this->central::$OPTIONS[ 'validate_mc4wp_email_fields' ] != 'yes' )
            return $messages;

        foreach( $this->central::$VALIDATION_ERROR_LIST as $error_type => $error_message )
            $messages[ $error_type ] = array( 'type' => 'error', 'text' => $error_message );

        return $messages;
    }


    // ----- Validating Formidable Forms Plugin lite ---------------------------

    public function validate_formidable_forms_email_addresses( $errors,  $field, $value, $args ) : array
    {
        if( $this->central::$OPTIONS[ 'validate_formidable_forms_email_fields' ] != 'yes' )
            return $errors;

        if(    $field->type == 'email'
            && ! $this->validate_email_address( $value )
        )
            $errors[ 'field' . $field->id ] = $this->get_email_validation_error_message();
        return $errors;
    }


    // ----- Validating Kali Forms Plugin --------------------------------------

    public function validate_kali_forms_email_addresses( $data )
    {

        foreach( $data as $key => $value )
        {
            if(      preg_match( $this->central::$EMAIL_FIELD_NAME_REGEX, $key )
                && ! $this->validate_email_address( $value )
            )
            {
                $data[ 'admin_external_change' ] = true;
                $data[ 'admin_stop_execution' ]  = true;
                $data[ 'admin_stop_reason' ] = $this->get_email_validation_error_message();
            }
        }
        return $data;
    }


    // ----- Validating Elementor Pro Plugin --------------------------------------

    public function validate_elementor_pro_email_addresses( $field, $record, $ajax_handler )
    {
        if ( ! $this->validate_email_address( $field['value'] ) ) {
            $ajax_handler->add_error( $field['id'], $this->get_email_validation_error_message() );
        }
    }


    // ----- Validating Gravity Forms Plugin ----------------------------------------

    public function validate_gravity_forms_email_addresses( $result, $value, $form, $field )
    {
        if (    $field->get_input_type() === 'email' 
             && $result['is_valid'] 
             && ! $this->validate_email_address( $value )
        ) {  
            $result['is_valid'] = false;
            $result['message']  = $this->get_email_validation_error_message();
        }
        return $result;
    }







    // ---------------- private functions of the class -------------------------

    private function init_options()
    {
        if ( get_option( $this->central::$OPTIONS_NAME ) )
            $this->central::$OPTIONS = get_option( $this->central::$OPTIONS_NAME );

        // ----- Email Domain --------------------------------------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'wp_email_domain' ] ) )
            $this->central::$OPTIONS[ 'wp_email_domain' ] = $this->leav->get_detected_wp_email_domain();

        $this->leav->set_wordpress_email_domain( $this->central::$OPTIONS[ 'wp_email_domain' ] );


        // ----- Allow recipient name catch-all syntax --------------------------------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'allow_recipient_name_catch_all_email_addresses' ] ) )
            $this->central::$OPTIONS[ 'allow_recipient_name_catch_all_email_addresses' ] = 'yes';


        // ----- Whitelists ----------------------------------------------------
        //
        // ----- Allow email adresses from user-defined domain whitelist -------
        //
        if ( empty( $this->central::$OPTIONS[ 'use_user_defined_domain_whitelist' ] ) )
            $this->central::$OPTIONS[ 'use_user_defined_domain_whitelist' ] = 'no';

        if ( empty( $this->central::$OPTIONS[ 'user_defined_domain_whitelist' ] ) )
            $this->central::$OPTIONS[ 'user_defined_domain_whitelist' ] = array();

        if ( empty( $this->central::$OPTIONS[ 'user_defined_domain_whitelist_string' ] ) )
            $this->central::$OPTIONS[ 'user_defined_domain_whitelist_string' ] = '';

        // ----- Allow email adresses from user-defined email address whitelist
        //
        if ( empty( $this->central::$OPTIONS[ 'use_user_defined_email_whitelist' ] ) )
            $this->central::$OPTIONS[ 'use_user_defined_email_whitelist' ] = 'no';

        if ( empty( $this->central::$OPTIONS[ 'user_defined_email_whitelist' ] ) )
            $this->central::$OPTIONS[ 'user_defined_email_whitelist' ] = array();

        if ( empty( $this->central::$OPTIONS[ 'user_defined_email_whitelist_string' ] ) )
            $this->central::$OPTIONS[ 'user_defined_email_whitelist_string' ] = '';

        // ----- Allow recipient names from user-defined recipient name whitelist
        //
        if ( empty( $this->central::$OPTIONS[ 'use_user_defined_recipient_name_whitelist' ] ) )
            $this->central::$OPTIONS[ 'use_user_defined_recipient_name_whitelist' ] = 'no';

        if ( empty( $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist' ] ) )
            $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist' ] = array();

        if ( empty( $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist_string' ] ) )
            $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist_string' ] = '';


        // ----- Blacklists ----------------------------------------------------
        //
        // ----- Block email adresses from user-defined domain blacklist -------
        //
        if ( empty( $this->central::$OPTIONS[ 'use_user_defined_domain_blacklist' ] ) )
            $this->central::$OPTIONS[ 'use_user_defined_domain_blacklist' ] = 'no';

        if ( empty( $this->central::$OPTIONS[ 'user_defined_domain_blacklist' ] ) )
            $this->central::$OPTIONS[ 'user_defined_domain_blacklist' ] = array();

        if ( empty( $this->central::$OPTIONS[ 'user_defined_domain_blacklist_string' ] ) )
            $this->central::$OPTIONS[ 'user_defined_domain_blacklist_string' ] = '';

        // ----- Block email adresses from free email address provider domains -
        //
        //
        if ( empty( $this->central::$OPTIONS[ 'use_free_email_address_provider_domain_blacklist' ] ) )
            $this->central::$OPTIONS[ 'use_free_email_address_provider_domain_blacklist' ] = 'no';


        if(    empty( $this->central::$OPTIONS[ 'free_email_address_provider_domain_list_version' ] )
            || $this->central::$OPTIONS[ 'free_email_address_provider_domain_list_version' ] != $this->central::$PLUGIN_VERSION
            || empty($this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ] )
            || empty($this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist_string' ] )
        )
            $this->read_free_email_address_provider_domain_list_file();

        // ----- If the blacklist is still empty, the file isn't present or
        // ----- something else went wrong. then we have to terminate the plugin

        if(    empty( $this->central::$OPTIONS[ 'free_email_address_provider_domain_list_version' ] )
            || empty($this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ] )
            || empty($this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist_string' ] )
        )
            die( 'Something went wrong with the free email address provider domain list file. We are cautious and abort the execution here!' );


        // ----- Block email adresses from user-defined email blacklist -------
        //
        if ( empty( $this->central::$OPTIONS[ 'use_user_defined_email_blacklist' ] ) )
            $this->central::$OPTIONS[ 'use_user_defined_email_blacklist' ] = 'no';

        if ( empty( $this->central::$OPTIONS[ 'user_defined_email_blacklist' ] ) )
            $this->central::$OPTIONS[ 'user_defined_email_blacklist' ] = array();

        if ( empty( $this->central::$OPTIONS[ 'user_defined_email_blacklist_string' ] ) )
            $this->central::$OPTIONS[ 'user_defined_email_blacklist_string' ] = '';

        // ----- User-defined recipient name blacklist -------------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'use_user_defined_recipient_name_blacklist' ] ) )
            $this->central::$OPTIONS[ 'use_user_defined_recipient_name_blacklist' ] = 'no';

        if ( empty( $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist' ] ) )
            $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist' ] = array();

        if ( empty( $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist_string' ] ) )
            $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist_string' ] = '';

        // ----- Role-based recipient name blacklist -------------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'use_role_based_recipient_name_blacklist' ] ) )
            $this->central::$OPTIONS[ 'use_role_based_recipient_name_blacklist' ] = 'no';

        if(    empty( $this->central::$OPTIONS[ 'role_based_recipient_name_list_version' ] )
            || $this->central::$OPTIONS[ 'role_based_recipient_name_list_version' ] != $this->central::$PLUGIN_VERSION
            || empty( $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ] )
        )
            $this->read_role_based_recipient_name_file();

        // ----- If the blacklist is still empty, the file isn't present or
        // ----- something else went wrong. then we have to terminate the plugin
        if (    empty( $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ] )
             || empty( $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist_string' ] )
             || empty( $this->central::$OPTIONS[ 'role_based_recipient_name_list_version' ] )
        )
        {
            write_log($this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ]);
            write_log($this->central::$OPTIONS[ 'role_based_recipient_name_blacklist_string' ]);
            write_log($this->central::$OPTIONS[ 'role_based_recipient_name_list_version' ]);
            die( 'Something went wrong with the role-based recipient names file. We are cautious and abort the execution here!' );
        }



        // ----- Disposable Email Address Blocking -----------------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'block_disposable_email_address_services' ] ) )
            $this->central::$OPTIONS[ 'block_disposable_email_address_services' ] = 'yes';

        if(    empty( $this->central::$OPTIONS[ 'dea_list_version' ] )
            || $this->central::$OPTIONS[ 'dea_list_version' ] != $this->central::$PLUGIN_VERSION
            || empty($this->central::$OPTIONS[ 'dea_domains' ] )
        )
            $this->read_dea_list_file();

        if( empty( $this->central::$OPTIONS[ 'dea_domains' ] ) )
            die( 'Something went wrong with the dea file. We are careful and abort the execution here!' );

        if( empty( $this->central::$OPTIONS[ 'dea_mx_domains' ] ) )
            die( 'Something went wrong with the dea file. We are careful and abort the execution here!' );

        if( empty( $this->central::$OPTIONS[ 'dea_mx_ips' ] ) )
            die( 'Something went wrong with the dea file. We are careful and abort the execution here!' );

        // ----- Simulate Email Sending ----------------------------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'simulate_email_sending' ] ) )
            $this->central::$OPTIONS[ 'simulate_email_sending' ] = 'yes';

        // ----- Allow catch-all domains ---------------------------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'allow_catch_all_domains' ] ) )
            $this->central::$OPTIONS[ 'allow_catch_all_domains' ] = 'yes';

        // ----- Pingbacks / Trackbacks ----------------------------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'accept_pingbacks' ] ) )
            $this->central::$OPTIONS[ 'accept_pingbacks' ] = 'yes';

        if ( empty( $this->central::$OPTIONS[ 'accept_trackbacks' ] ) )
            $this->central::$OPTIONS[ 'accept_trackbacks' ] = 'yes';

        // ------ Validation of functions / plugins switches ---
        //
        if ( empty( $this->central::$OPTIONS[ 'validate_wp_standard_user_registration_email_addresses' ] ) )
            $this->central::$OPTIONS[ 'validate_wp_standard_user_registration_email_addresses' ] = 'yes';

        if ( empty( $this->central::$OPTIONS[ 'validate_wp_comment_user_email_addresses' ] ) )
            $this->central::$OPTIONS[ 'validate_wp_comment_user_email_addresses' ] = 'yes';

        if ( empty( $this->central::$OPTIONS[ 'validate_woocommerce_email_fields' ] ) )
            $this->central::$OPTIONS[ 'validate_woocommerce_email_fields' ] = 'yes';

        if ( empty( $this->central::$OPTIONS[ 'validate_cf7_email_fields' ] ) )
            $this->central::$OPTIONS[ 'validate_cf7_email_fields' ] = 'yes';

        if ( empty( $this->central::$OPTIONS[ 'validate_wpforms_email_fields' ] ) )
            $this->central::$OPTIONS[ 'validate_wpforms_email_fields' ] = 'yes';

        if ( empty( $this->central::$OPTIONS[ 'validate_ninja_forms_email_fields' ] ) )
            $this->central::$OPTIONS[ 'validate_ninja_forms_email_fields' ] = 'yes';

        if ( empty( $this->central::$OPTIONS[ 'validate_mc4wp_email_fields' ] ) )
            $this->central::$OPTIONS[ 'validate_mc4wp_email_fields' ] = 'yes';

        if( empty( $this->central::$OPTIONS[ 'validate_formidable_forms_email_fields' ] ) )
            $this->central::$OPTIONS[ 'validate_formidable_forms_email_fields' ] = 'yes';

        if( empty( $this->central::$OPTIONS[ 'validate_kali_forms_email_fields' ] ) )
            $this->central::$OPTIONS[ 'validate_kali_forms_email_fields' ] = 'yes';

        if( empty( $this->central::$OPTIONS[ 'validate_elementor_pro_email_fields' ] ) )
            $this->central::$OPTIONS[ 'validate_elementor_pro_email_fields' ] = 'yes';

        if( empty( $this->central::$OPTIONS[ 'validate_gravity_forms_email_fields' ] ) )
            $this->central::$OPTIONS[ 'validate_gravity_forms_email_fields' ] = 'yes';

        // ------ CEM = Custom error message override fields -------------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'cem_email_address_contains_invalid_characters' ] ) )
            $this->central::$OPTIONS[ 'cem_email_address_contains_invalid_characters' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_email_address_syntax_error' ] ) )
            $this->central::$OPTIONS[ 'cem_email_address_syntax_error' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_recipient_name_catch_all_email_address_error' ] ) )
            $this->central::$OPTIONS[ 'cem_recipient_name_catch_all_email_address_error' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_email_domain_is_blacklisted' ] ) )
            $this->central::$OPTIONS[ 'cem_email_domain_is_blacklisted' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_email_domain_is_on_free_email_address_provider_domain_list' ] ) )
            $this->central::$OPTIONS[ 'cem_email_domain_is_on_free_email_address_provider_domain_list' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_email_address_is_blacklisted' ] ) )
            $this->central::$OPTIONS[ 'cem_email_address_is_blacklisted' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_recipient_name_is_blacklisted' ] ) )
            $this->central::$OPTIONS[ 'cem_recipient_name_is_blacklisted' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_recipient_name_is_role_based' ] ) )
            $this->central::$OPTIONS[ 'cem_recipient_name_is_role_based' ] = '';


        if ( empty( $this->central::$OPTIONS[ 'cem_email_domain_has_no_mx_record' ] ) )
            $this->central::$OPTIONS[ 'cem_email_domain_has_no_mx_record' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_email_domain_is_on_dea_blacklist' ] ) )
            $this->central::$OPTIONS[ 'cem_email_domain_is_on_dea_blacklist' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_simulated_sending_of_email_failed' ] ) )
            $this->central::$OPTIONS[ 'cem_simulated_sending_of_email_failed' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_email_from_catch_all_domain' ] ) )
            $this->central::$OPTIONS[ 'cem_email_from_catch_all_domain' ] = '';

        if ( empty( $this->central::$OPTIONS[ 'cem_general_email_validation_error' ] ) )
            $this->central::$OPTIONS[ 'cem_general_email_validation_error' ] = '';


        // ------ Main Menu Use & Positions -------------------
        //
        if ( empty( $this->central::$OPTIONS[ 'use_main_menu' ] ) )
        {
            $this->central::$OPTIONS[ 'use_main_menu' ] = 'yes';
            $this->central->determine_menu_link( 'main' );
        }
        elseif( $this->central::$OPTIONS[ 'use_main_menu' ] == 'yes' )
            $this->central->determine_menu_link( 'main' );
        else
            $this->central->determine_menu_link( 'general_options' );   

        if (    empty( $this->central::$OPTIONS[ 'main_menu_position' ] )
             || $this->central::$OPTIONS[ 'main_menu_position' ] ==''
         )
            $this->central::$OPTIONS[ 'main_menu_position' ] = 24;

        if (    empty( $this->central::$OPTIONS[ 'settings_menu_position' ] )
             || $this->central::$OPTIONS[ 'settings_menu_position' ] == '' )
            $this->central::$OPTIONS[ 'settings_menu_position' ] = 3;

        // ----- Non-visible options
        //
        if ( empty( $this->central::$OPTIONS[ 'spam_email_addresses_blocked_count' ] ) )
            $this->central::$OPTIONS[ 'spam_email_addresses_blocked_count' ] = "0";


        update_option($this->central::$OPTIONS_NAME, $this->central::$OPTIONS);
    }


    private function init_validation_filters()
    {

        if(    $this->central::$OPTIONS[ 'validate_wp_standard_user_registration_email_addresses' ] == 'yes'
            && get_option("users_can_register") == 1 )
            add_filter("registration_errors", array( $this, 'validate_registration_email_addresses' ), 99, 3);

        if(  $this->central::$OPTIONS[ 'validate_wp_comment_user_email_addresses' ] == 'yes' )
            add_filter("pre_comment_approved", array( $this, 'validate_comment_email_addresses' ), 99, 2);

        if (    is_plugin_active( "woocommerce/woocommerce.php" )
             && $this->central::$OPTIONS[ 'validate_woocommerce_email_fields' ] == 'yes'
        )
            add_filter("woocommerce_registration_errors", array( $this, 'validate_registration_email_addresses' ), 99, 3   );

        if (    is_plugin_active( "contact-form-7/wp-contact-form-7.php" )
             && $this->central::$OPTIONS[ 'validate_cf7_email_fields' ] == 'yes'
        )
        {
            add_filter("wpcf7_validate_email", array( $this, 'validate_cf7_email_addresses' ), 20, 2);
            add_filter("wpcf7_validate_email*", array( $this, 'validate_cf7_email_addresses' ), 20, 2);
        }

        if ( (
                   is_plugin_active( "wpforms-lite/wpforms.php" )
                || is_plugin_active( "wpforms/wpforms.php"      )
             )
             &&
             $this->central::$OPTIONS[ 'validate_wpforms_email_fields' ] == 'yes'
           )
            add_action( "wpforms_process", array( $this, 'validate_wpforms_email_addresses' ), 10, 3 );

        if (    is_plugin_active( "ninja-forms/ninja-forms.php" )
             && $this->central::$OPTIONS[ 'validate_ninja_forms_email_fields' ] == 'yes'
        )
            add_filter("ninja_forms_submit_data", array( $this, 'validate_ninja_forms_email_addresses' ), 20, 2);

        if (    is_plugin_active( 'mailchimp-for-wp/mailchimp-for-wp.php' )
             && $this->central::$OPTIONS[ 'validate_mc4wp_email_fields' ] == 'yes'
        )
        {
            add_filter( 'mc4wp_form_messages', array( $this, 'add_mc4wp_error_message' ), 10, 2 );
            add_filter( 'mc4wp_form_errors', array( $this, 'validate_mc4wp_email_addresses' ), 10, 2 );
        }
        if (    is_plugin_active( "formidable/formidable.php" )
             && $this->central::$OPTIONS[ 'validate_formidable_forms_email_fields' ] == 'yes'
         )
            add_filter( 'frm_validate_field_entry', array( $this, 'validate_formidable_forms_email_addresses' ), 20, 4 );

        if (    is_plugin_active( "kali-forms/kali-forms.php" )
             && $this->central::$OPTIONS[ 'validate_kali_forms_email_fields' ] == 'yes'
        )
            add_filter( "kaliforms_before_form_process", array( $this, 'validate_kali_forms_email_addresses' ) );

        if (    is_plugin_active( "elementor-pro/elementor-pro.php" )
             && $this->central::$OPTIONS[ 'validate_elementor_pro_email_fields' ] == 'yes'
        )
            add_action( "elementor_pro/forms/validation/email", array( $this, 'validate_elementor_pro_email_addresses' ), 10, 3 );

        if (    is_plugin_active( "gravityforms/gravityforms.php" )
             && $this->central::$OPTIONS[ 'validate_gravity_forms_email_fields' ] == 'yes'
        )
            add_filter( 'gform_field_validation', array( $this, 'validate_gravity_forms_email_addresses' ), 10, 4 );

    }


    private function init_custom_error_messages() : void
    {
        if( ! empty ( $this->central::$OPTIONS[ 'cem_email_address_contains_invalid_characters' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'email_address_contains_invalid_characters' ] = $this->central::$OPTIONS[ 'cem_email_address_contains_invalid_characters' ];


        if( ! empty ( $this->central::$OPTIONS[ 'cem_email_address_syntax_error' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'email_address_syntax_error' ] = $this->central::$OPTIONS[ 'cem_email_address_syntax_error' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_recipient_name_catch_all_email_address_error' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'recipient_name_catch_all_email_address_error' ] = $this->central::$OPTIONS[ 'cem_recipient_name_catch_all_email_address_error' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_email_domain_is_blacklisted' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'email_domain_is_blacklisted' ] = $this->central::$OPTIONS[ 'cem_email_domain_is_blacklisted' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_email_domain_is_on_free_email_address_provider_domain_list' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'email_domain_is_on_free_email_address_provider_domain_list' ] = $this->central::$OPTIONS[ 'cem_email_domain_is_on_free_email_address_provider_domain_list' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_email_address_is_blacklisted' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'email_address_is_blacklisted' ] = $this->central::$OPTIONS[ 'cem_email_address_is_blacklisted' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_recipient_name_is_blacklisted' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'recipient_name_is_blacklisted' ] = $this->central::$OPTIONS[ 'cem_recipient_name_is_blacklisted' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_recipient_name_is_role_based' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'recipient_name_is_role_based' ] = $this->central::$OPTIONS[ 'cem_recipient_name_is_role_based' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_email_domain_has_no_mx_record' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'email_domain_has_no_mx_record' ] = $this->central::$OPTIONS[ 'cem_email_domain_has_no_mx_record' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_email_domain_is_on_dea_blacklist' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'email_domain_is_on_dea_blacklist' ] = $this->central::$OPTIONS[ 'cem_email_domain_is_on_dea_blacklist' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_email_domain_is_on_dea_blacklist' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'email_domain_is_on_dea_blacklist' ] = $this->central::$OPTIONS[ 'cem_email_domain_is_on_dea_blacklist' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_simulated_sending_of_email_failed' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'simulated_sending_of_email_failed' ] = $this->central::$OPTIONS[ 'cem_simulated_sending_of_email_failed' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_email_from_catch_all_domain' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'email_from_catch_all_domain' ] = $this->central::$OPTIONS[ 'cem_email_from_catch_all_domain' ];

        if( ! empty ( $this->central::$OPTIONS[ 'cem_general_email_validation_error' ] ) )
            $this->central::$VALIDATION_ERROR_LIST[ 'general_email_validation_error' ] = $this->central::$OPTIONS[ 'cem_general_email_validation_error' ];
    }


    private function read_role_based_recipient_name_file() : bool
    {
        $lines = array();
        if( ! read_file_into_array_ignore_newlines( $this->role_based_recipient_name_file, $lines) )
            return false;

        $this->central::$OPTIONS[ 'role_based_recipient_name_list_version' ] = array_shift($lines);
        $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ] = array();
        $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ][ 'recipient_names' ] = array();
        $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ][ 'regexps' ] = array();
        $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist_string' ] = '';

        foreach( $lines as $id => $line )
        {
            if( $this->leav->is_comment_line( $line ) )
                continue;
            elseif( $this->leav->sanitize_and_validate_recipient_name_internally( $line ) )
            {
                $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist_string' ] .= $line . "\n";
                if( $this->leav->line_contains_wildcard( $line ) )
                    array_push( $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ][ 'regexps' ], $line );
                else
                    array_push( $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ][ 'recipient_names' ], $line );
            }
        }
        if( empty( $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ] ) )
            return false;

        $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist_string' ] = rtrim($this->central::$OPTIONS[ 'role_based_recipient_name_blacklist_string' ] );
        return true;
    }


    private function read_free_email_address_provider_domain_list_file() : bool
    {
        $lines = array();
        if( ! read_file_into_array_ignore_newlines( $this->free_email_address_provider_list_file, $lines) )
            return false;
        $this->central::$OPTIONS[ 'free_email_address_provider_domain_list_version' ] = array_shift($lines);
        $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ] = array();
        $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ][ 'domains' ] = array();
        $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ][ 'regexps' ] = array();
        $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist_string' ] = '';

        foreach( $lines as $id => $line )
        {
            if(    $this->leav->is_comment_line( $line ) )
                // we continue, since we don't want to display comments from out internal files
                continue;
            // elseif( preg_match( $this->central::$DOMAIN_REGEX, $line ) )
            elseif( $this->leav->line_contains_wildcard( $line )

            )

            {
                array_push( $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ][ 'domains' ], $line );
                $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist_string' ] .= $line . "\n";
            }
            elseif( preg_match( $this->central::$DOMAIN_INTERNAL_REGEX, $line ) )
            {
                $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist_string' ] .= $line . "\n";
                $pattern = '/' . preg_replace( "/\*/", '[a-z0-9-]*', $line ) . '/';
                array_push( $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ][ 'regexps' ], $pattern );
            }
        }

        if(     empty( $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ][ 'domain' ] ) && empty( $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ][ 'regexps' ] )
        )
            return false;

        $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist_string' ] = rtrim( $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist_string' ] );
        return true;
    }


    private function read_dea_list_file() : bool
    {
        $lines = array();
        if( ! read_file_into_array_ignore_newlines( $this->disposable_email_service_provider_list_file, $lines) )
            return false;

        $this->central::$OPTIONS[ 'dea_list_version' ] = array_shift($lines);
        $this->central::$OPTIONS[ 'dea_domains' ] = array();
        $this->central::$OPTIONS[ 'dea_mx_domains' ] = array();
        $this->central::$OPTIONS[ 'dea_mx_ips' ] = array();

        foreach( $lines as $id => $line )
        {
            if(    preg_match( $this->central::$COMMENT_LINE_REGEX, $line )
                || preg_match( $this->central::$EMPTY_LINE_REGEX,   $line )
            )
                continue;

            if( substr( $line, 0, 7 ) == 'domain:' )
            {
                $domain = substr( $line, 7 );
                if( $this->leav->sanitize_and_validate_domain( $domain ) )
                    array_push( $this->central::$OPTIONS[ 'dea_domains' ], $domain );
            }
            elseif( substr( $line, 0, 3 ) == 'mx:' )
            {
                $domain = substr( $line, 3 );
                if( $this->leav->sanitize_and_validate_domain( $domain ) )
                    array_push( $this->central::$OPTIONS[ 'dea_mx_domains' ], $domain );
            }
            elseif( substr( $line, 0, 3 ) == 'ip:' )
            {
                $ip = substr( $line, 3 );
                if( $this->leav->sanitize_and_validate_ip( $ip ) )
                    array_push( $this->central::$OPTIONS[ 'dea_mx_ips' ], $ip );
            }
        }
        if(    ! empty( $this->central::$OPTIONS[ 'dea_domains' ] )
            && ! empty( $this->central::$OPTIONS[ 'dea_mx_domains' ] )
            && ! empty( $this->central::$OPTIONS[ 'dea_mx_ips' ] )
        )
            return true;
        return false;
    }


    private function increment_count_of_blocked_email_addresses() : bool
    {
        $this->central::$OPTIONS[ 'spam_email_addresses_blocked_count' ] = ($this->central::$OPTIONS[ 'spam_email_addresses_blocked_count' ] + 1);
        if( update_option( $this->central::$OPTIONS_NAME, $this->central::$OPTIONS ) )
            return true;
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

?>