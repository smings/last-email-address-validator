<?php
/*
Plugin Name: Last Email Address Validator
Plugin URI: https://smings.com/leav/
Description: LEAV provides the best email address validation for WP registration/comments, WooCommerce, Contact Form 7, WPForms, Ninja Forms and more plugins to come...
Version: 1.2.1
Author: smings
Author URI: https://smings.com/leav/
Text Domain: leav
*/

// for debugging only
$d = false;

$leav_plugin_file_name = plugin_basename( __FILE__ );
$leav_plugin_name = 'last-email-address-validator';

$WP_DOMAIN_PARTS = explode( '.', getenv( "HTTP_HOST" ) );
$WP_MAIL_DOMAIN = $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 2 ] . '.' .  $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 1 ];

$leav_options = array();
$is_windows = strncasecmp(PHP_OS, 'WIN', 3) == 0 ? true : false;


$disposable_email_service_domain_list_file = plugin_dir_path(__FILE__) . 'data/disposable_email_service_provider_domain_list.txt';
$disposable_email_service_mx_servers_file =  plugin_dir_path(__FILE__) . 'data/disposable_email_service_provider_mx_server_list.txt';

require_once('includes/leav.inc.php');
require_once('includes/leav-helper-functions.inc.php');

$LEAV = new LastEmailAddressValidator();
load_plugin_textdomain('leav');


// -----------------------------------------------------------------------------
// plugin functionality

function leav_init() 
{
    global $d;
    global $leav_options;
    global $disposable_email_service_domain_list_url;
    global $disposable_email_service_domain_list_file;
    global $WP_MAIL_DOMAIN;


    // Now we set and persist the default values for the plugin
    if ( get_option('leav_options') )
        $leav_options = get_option('leav_options');
    
    if ( empty($leav_options['wp_mail_domain']) )
        $leav_options['wp_mail_domain'] = $WP_MAIL_DOMAIN;
    
    if ( empty($leav_options['spam_email_addresses_blocked_count']) )
        $leav_options['spam_email_addresses_blocked_count'] = '0';
    
    if ( empty($leav_options['default_gateway']) )
        $leav_options['default_gateway'] = '';
    
    if ( empty($leav_options['accept_pingbacks']) )
        $leav_options['accept_pingbacks'] = 'yes';
    
    if ( empty($leav_options['accept_trackbacks']) )
        $leav_options['accept_trackbacks'] = 'yes';

    if ( empty($leav_options['use_user_defined_domain_whitelist']) )
        $leav_options['use_user_defined_domain_whitelist'] = 'no';

    if ( empty($leav_options['use_user_defined_email_whitelist']) )
        $leav_options['use_user_defined_email_whitelist'] = 'no';
    
    if ( empty($leav_options['use_user_defined_domain_blacklist']) )
        $leav_options['use_user_defined_domain_blacklist'] = 'no';

    if ( empty($leav_options['use_user_defined_email_blacklist']) )
        $leav_options['use_user_defined_email_blacklist'] = 'no';
    
    if ( empty($leav_options['block_disposable_email_service_domains']) )
        $leav_options['block_disposable_email_service_domains'] = 'yes';

    if (empty($leav_options['disposable_email_service_domain_list'])) {
        $disposable_email_service_domains = file_exists($disposable_email_service_domain_list_file) ?   file_get_contents($disposable_email_service_domain_list_file) : '';
        $leav_options['disposable_email_service_domain_list'] = $disposable_email_service_domains;
    }    

    if ( empty($leav_options['validate_wp_standard_user_registration_email_addresses']) )
        $leav_options['validate_wp_standard_user_registration_email_addresses'] = 'yes';
    
    if ( empty($leav_options['validate_wp_comment_user_email_addresses']) )
        $leav_options['validate_wp_comment_user_email_addresses'] = 'yes';
    
    if ( empty($leav_options['validate_woocommerce_registration']) )
        $leav_options['validate_woocommerce_registration'] = 'yes';
    
    if ( empty($leav_options['validate_cf7_email_fields']) )
        $leav_options['validate_cf7_email_fields'] = 'yes';

    if ( empty($leav_options['validate_wpforms_email_fields']) )
        $leav_options['validate_wpforms_email_fields'] = 'yes';

    if ( empty($leav_options['validate_ninja_forms_email_fields']) )
        $leav_options['validate_ninja_forms_email_fields'] = 'yes';
    
    update_option('leav_options', $leav_options);


    // Now after setting all defaults, we can add filters and actions
    if(  $leav_options['validate_wp_standard_user_registration_email_addresses'] == 'yes' && get_option('users_can_register') == 1 )
        add_filter('registration_errors', 'leav_validate_registration_email_addresses', 99, 3);

    if(  $leav_options['validate_wp_comment_user_email_addresses'] == 'yes' )
        add_filter('pre_comment_approved', 'leav_validate_comment_email_addresses', 99, 2);


    # Filtering for WooCommerce, if it is installed and active
    if (    is_plugin_active( 'woocommerce/woocommerce.php' )
         && $leav_options['validate_woocommerce_registration'] == 'yes'
    )
    {
        if($d)
            write_log("WooCommerce validation active");
        add_filter('woocommerce_registration_errors', 'leav_validate_registration_email_addresses', 10, 3);
    }

    # Filtering for contact form 7, if it is installed and active
    if (    is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) 
         && $leav_options['validate_cf7_email_fields'] == 'yes'
    )
    {
        if($d)
            write_log("CF7 validation active");
        add_filter('wpcf7_validate_email', 'leav_validate_cf7_email_addresses', 20, 2);
        add_filter('wpcf7_validate_email*', 'leav_validate_cf7_email_addresses', 20, 2);
    }

    # Filtering for WPforms, if it is installed and active
    if ( ( 
               is_plugin_active( 'wpforms-lite/wpforms.php' )  
            || is_plugin_active( 'wpforms/wpforms.php'      ) 
         )
         &&
         $leav_options['validate_wpforms_email_fields'] == 'yes'
       )
    {
        if($d)
            write_log("WPForms validation active");
        add_action( 'wpforms_process', 'leav_validate_wpforms_email_addresses', 10, 3 );
    }

    # Filtering for ninja forms, if it is installed and active
    if (    is_plugin_active( 'ninja-forms/ninja-forms.php' )
         && $leav_options['validate_ninja_forms_email_fields'] == 'yes'
    )
    {
        if($d)
            write_log("Ninja forms validation active");
        add_filter('ninja_forms_submit_data', 'leav_validate_ninja_forms_email_addresses', 10, 3);
    }

    # adding the options page and enqueing scripts for admins
    if ( is_admin() )
    {
        add_action('admin_menu', 'leav_add_options_page');
        // add_action('admin_enqueue_scripts', 'leav_enque_scripts');
    }


}

function leav_validate_comment_email_addresses($approval_status, $comment_data)
{
    global $d;
    global $user_ID;
    global $leav_options;

    // if a comment is already marked as spam or trash
    // we can return right away
    if ( 
        $leav_options['validate_wp_comment_user_email_addresses'] == 'no' ||
        $approval_status === 'spam' || 
        $approval_status === 'trash' 
    ) 
        return $approval_status;
   
    // check if trackbacks are allowed
    if ( (isset($comment_data['comment_type'])) && ($comment_data['comment_type'] == 'trackback') )
    {
        if ($leav_options['accept_trackbacks'] == 'yes') 
            return $approval_status;
        else
            return 'trash';
    }
    
    // check if pingbacks are allowed
    if ((isset($comment_data['comment_type'])) && ($comment_data['comment_type'] == 'pingback'))
    {
        if ($leav_options['accept_pingbacks'] == 'yes')
            return $approval_status;
        else 
            return 'trash';
    }
    
    // if it's a comment and not a logged in user - check mail
    if ( get_option('require_name_email') && !$user_ID )
    {
        $email_address = $comment_data['comment_author_email'];
        return leav_validate_email_address($approval_status, $email_address);
    }
    return $approval_status;
}

function leav_validate_registration_email_addresses($errors, $sanitized_user_login, $entered_email_address)
{
    global $d;
    global $LEAV;
    global $leav_options;

    if( $leav_options['validate_wp_standard_user_registration_email_addresses'] == 'no' )
        return $errors;

    $result = leav_validate_email_address('', $entered_email_address);
    if ( $result === 'spam') 
        $errors->add('wp_mail-validator-registration-error', __( 'The entered email address\'s domain is invalid or not accepted.', 'leav'));
    elseif( $result == 'invalid_syntax' )
         $errors->add('wp_mail-validator-registration-error', __( 'entered email address is invalid.', 'leav'));

    return $errors;
}

function leav_validate_cf7_email_addresses($result, $tag)
{
    global $d;
    global $LEAV;
    global $leav_options;

    $tag = new WPCF7_FormTag( $tag );
    $type = $tag->type;
    $name = $tag->name;
    if ($type == 'email' || $type == 'email*')
    {
        $entered_email_address = sanitize_email($_POST[$name]);
        $result = leav_validate_email_address('', $entered_email_address);
        if ( $result === 'spam')
            $result->invalidate( $tag, __( 'The entered email address\'s domain is invalid or not accepted.', 'leav' ));
        elseif( $result == 'invalid_syntax' )
            $result->invalidate( $tag, __( 'The entered email address is invalid.', 'leav' ));
    }
    return $result;
}

function leav_validate_wpforms_email_addresses( $fields, $entry, $form_data ) {
    global $d;
    $size = count( $fields );
    for( $i = 0; $i < $size; $i++ )
    {
        if( $fields[$i]['type'] == 'email' )
        {
            $result = leav_validate_email_address( '', $fields[$i]['value'] );
            if( $result == 'spam')
                wpforms()->process->errors[ $form_data['id'] ] [ $i ] = esc_html__( 'The entered email address\'s domain is invalid or not accepted.', 'leav' );
            elseif( $result == 'invalid_syntax' )
                wpforms()->process->errors[ $form_data['id'] ] [ $i ] = esc_html__( 'The entered email address is invalid.', 'leav' );
        }
    }

    return $fields;
}

function leav_validate_ninja_forms_email_addresses($form_data) {
    global $d;
    global $LEAV;
    $size = count( $form_data['fields'] );
    for( $i = 1; $i <= $size; $i++ )
    {
        if( $LEAV->leav_check_field_name_for_email( $form_data['fields'][$i]['key'] ) )
        {
            $result = leav_validate_email_address( '', $form_data['fields'][$i]['value'] );
            if( $result == 'spam' )
                $form_data['errors']['fields'][$i] = __( 'The entered email address\'s domain is invalid or not accepted.', 'leav' );
            elseif ( $result == 'invalid_syntax' )
                $form_data['errors']['fields'][$i] = __( 'The entered email address is invalid.', 'leav' );
        }

    }
    return $form_data;
}

function leav_validate_email_address($approval_status, $email_address)
{
    global $d;
    global $LEAV;
    global $leav_options;

    // First we check the email address syntax
    // 
    if( ! $LEAV->leav_check_email_adress_syntax($email_address) )
        return 'invalid_syntax';

    // check mail-address against user defined blacklist (if enabled)
    // 
    if ($leav_options['use_user_defined_domain_blacklist'] == 'yes')
    {
        if($d)
            write_log("Trying to block user-defined blacklist entries");
        $regexps = preg_split('/[\r\n]+/', $leav_options['user_defined_blacklist'], -1, PREG_SPLIT_NO_EMPTY);

        foreach ($regexps as $regexp)
        {
            if (preg_match('/' . $regexp . '/', $email_address))
            {
                if($d)
                    write_log("---> Email address stems from $regexp -> returning 'spam'");
                leav_increment_count_of_blocked_email_addresses();
                return 'spam';
            }
        }
    }

    // check mail-address against disposable email address services domain blacklist (if enabled)
    if ($leav_options['block_disposable_email_service_domains'] == 'yes')
    {
        if($d)
            write_log("Trying to block disposable email service blacklist entries");
        $regexps = preg_split('/[\r\n]+/', $leav_options['disposable_email_service_domain_list'], -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($regexps as $regexp)
        {
            if($d)
                write_log("Matching '$regexp' against '$email_address'");
            if (preg_match('/' . $regexp . '/', $email_address))
            {
                if($d)
                    write_log("---> Email address stems from $regexp -> returning 'spam'");
                leav_increment_count_of_blocked_email_addresses();
                return 'spam';
            }
        }
    }

    $return_code = $LEAV->leav_validate_email_address($email_address);

    if($d)
        write_log("Result of validating email address is: $return_code");

    if($return_code == VALID_EMAIL_ADDRESS)
        return $approval_status;
    else
    {
        leav_increment_count_of_blocked_email_addresses();
        return 'spam';
    }
}


// database update function
function leav_increment_count_of_blocked_email_addresses()
{
    global $d;
    global $leav_options;

    $leav_options['spam_email_addresses_blocked_count'] = ($leav_options['spam_email_addresses_blocked_count'] + 1);
    update_option('leav_options', $leav_options);
}

// theme functions / statistics
function leav_powered_by_label($string_before = "", $string_after = "")
{
    global $d;
    $label = $string_before . __('Anti spam protected by', 'leav') . ': <a href="https://smings.com/leav" title="LEAV - Last Email Address Validator" target="_blank">LEAV - Last Email Address Validator</a> - <strong>%s</strong> ' . __('Spam email addresses blocked', 'leav') . '!' . $string_after;
    return sprintf($label, leav_get_blocked_email_address_count());
}

function leav_get_blocked_email_address_count()
{
    global $d;
    global $leav_options;
    return $leav_options['spam_email_addresses_blocked_count'];
}

function leav_version()
{
    global $d;
    $plugin = get_plugin_data( __FILE__ );
    return $plugin['Version'];
}

// <-- admin menu option page -->

function leav_add_options_page()
{
    global $d;
    add_options_page('LEAV - Last Email Address Validator', 'LEAV - Last Email Address Validator', 'edit_pages', basename(__FILE__, ".php"), 'leav_options_page');
}



function leav_enque_scripts($hook)
{
    global $d;
    if($d)
        write_log("Hook = '" . $hook . "'");
    if ('settings_page_leav' != $hook)
    {
        return;
    }

    wp_enqueue_script('jquery.mask', plugin_dir_url(__FILE__) . 'scripts/jquery.mask.min.js', array(), '1.14.15');
    wp_enqueue_script('leav', plugin_dir_url(__FILE__) . 'scripts/leav.min.js', array(), '1.0.0');
}

// <-- plugin installation on activation -->

function leav_activate_plugin()
{
    global $d;
    global $wpdb;
    global $leav_options;

    // migration of existing data in older versions
    $table_name = $wpdb->prefix . "leav";

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name)
    {
        $sql = "SELECT eaten FROM " . $table_name . " LIMIT 1;";
        $count = $wpdb->get_var($sql);

        $leav_options['spam_email_addresses_blocked_count'] = $count;
        update_option('leav_options', $leav_options);

        $sql = "DROP TABLE IF EXISTS " . $table_name . ";";
        $wpdb->query($sql);
    }
    update_option('leav_options', $leav_options);
}

function leav_uninstall_plugin()
{
    global $d;
    delete_option('leav_options');
}


function leav_add_plugin_overview_links( $links ) {
    $settings_link = '<a href="options-general.php?page=last-email-address-validator">' . __( 'Settings' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'leav_add_plugin_overview_links' );
register_activation_hook( __FILE__, 'leav_activate_plugin');
register_uninstall_hook( __FILE__, 'leav_uninstall_plugin');
add_action( 'init', 'leav_init' );
?>