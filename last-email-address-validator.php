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


require_once('includes/leav.inc.php');

// for debugging only
$d = false;

$leav_plugin_file_name = plugin_basename( __FILE__ );
$leav_plugin_name = 'last-email-address-validator';

$WP_DOMAIN_PARTS = explode( '.', getenv( "HTTP_HOST" ) );
$WP_MAIL_DOMAIN = $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 2 ] . '.' .  $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 1 ];

$disposable_email_service_domain_list_file = plugin_dir_path(__FILE__) . 'data/disposable_email_service_provider_domain_list.txt';
$disposable_email_service_mx_servers_file =  plugin_dir_path(__FILE__) . 'data/disposable_email_service_provider_mx_server_list.txt';

$LEAV = new LastEmailAddressValidator();
load_plugin_textdomain('leav');
$leav_options = array();
$is_windows = strncasecmp(PHP_OS, 'WIN', 3) == 0 ? true : false;

// Ensuring the existence of functions that are being used throughout 
// the plugin
// 
// Making sure we have a `write_log` function for debugging
if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}

// making sure we have a `getmxrr` function on windows
if (! function_exists('getmxrr'))
{
    function getmxrr($hostName, &$mxHosts, &$mxPreference)
    {
        global $d;
        global $leav_options;
        
        $gateway = $leav_options['default_gateway'];
    
        $nsLookup = shell_exec("nslookup -q=mx {$hostName} {$gateway} 2>nul");
        preg_match_all("'^.*MX preference = (\d{1,10}), mail exchanger = (.*)$'simU", $nsLookup, $mxMatches);

        if ( count($mxMatches[2]) > 0 )
        {
            array_multisort($mxMatches[1], $mxMatches[2]);

            for ($i = 0; $i < count($mxMatches[2]); $i++) 
            {
                $mxHosts[$i] = $mxMatches[2][$i];
                $mxPreference[$i] = $mxMatches[1][$i];
            }

            return true;
        } 
        else
        {
            return false;
        }
    }
}

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

function leav_options_page()
{
    global $d;
    global $leav_options;
    global $disposable_email_service_domain_list_url;
    global $is_windows;

    if (isset($_POST['leav_options_update_type']))
    {
        $update_notice = '';

        foreach ($_POST as $key => $value)
        {
            if($d)
                write_log("key=value => $key=$value");
            if ($key !== 'leav_options_update_type' && $key !== 'submit')
            {
                $leav_options[$key] = $value;
            }
        }
        $update_notice = __('Options updated', 'leav');
        update_option('leav_options', $leav_options);
        $leav_options = get_option('leav_options');
    ?>
        <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
            <p>
                <strong><?php echo $update_notice ?>.</strong>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e('Dismiss this notice', 'leav') ?>.</span>
            </button>
        </div>
    <?php
    }
    ?>
        <div class="wrap">
            <h1><?php _e('Settings for <strong>LEAV - Last Email Address Validator</strong> <i>by smings</i>', 'leav') ?></h1>
            <?php _e('LEAV - Last Email Address Validator <i>by smings</i> validates email addresses of the supported WordPress functions and plugins in the following 9-step process' , 'leav'); ?>
            <ol>
                <li>
                    <?php _e('Check if the email address is syntactically correct. This acts as a backup check for the plugins. Some plugins only have a frontend based email syntax check. This is a proper server-side check (always)', 'leav'); ?>
                </li>
                <li>
                    <?php _e('Filter against user-defined email domain whitelist. (if activated)<br/>If you need to override false positives, you can use this option. We would kindly ask you to <a href="mailto:leav-bugs@smings.com">inform us</a> about wrongfully blacklisted domains though, so that we can correct this.' , 'leav'); ?>
                </li>
                <li>
                    <?php _e('Filter against user-defined email whitelist (if activated)<br/>If you need to override one or multiple specific email addresses that would otherwise get filtered out.' , 'leav'); ?>
                </li>
                <li>
                    <?php _e('Filter against user-defined email domain blacklist (if activated)' , 'leav'); ?>
                </li>
                <li>
                    <?php _e('Filter against user-defined email blacklist (if activated)' , 'leav'); ?>
                </li>
                <li>
                    <?php _e('Filter against LEAV\'s built-in extensive blacklist of disposable email service domains and known spammers (always)', 'leav'); ?>
                </li>
                <li>
                    <?php _e('Check if the email address\'s domain has a DNS entry with MX records (always)', 'leav'); ?>
                </li>

                <li>
                    <?php _e('Filter against LEAV\'s built-in extensive blacklist of MX (MX = Mail eXchange) server domains and IP addresses for disposable email services (always)', 'leav'); ?>
                </li>
                <li>
                    <?php 
                        _e('Connect to one of the MX servers and simulate the sending of an email from <strong>no-reply@', 'leav'); 
                        echo ($leav_options['wp_mail_domain']); 
                        _e('</strong> to the entered email address (always)', 'leav' ); ?>
                </li>
            </ol>
<?php 
_e('Below you can control in which way the selected WordPress functions and plugins will validate entered email adresses.' , 'leav');
?>
            <form name="wp_mail_validator_options" method="post">
                <input type="hidden" name="leav_options_update_type" value="update" />

                <?php if ($is_windows) { ?>
                    <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Default gateway IP', 'leav') ?>:</th>
                            <td>
                                <input name="default_gateway" type="text" id="default_gateway" value="<?php echo $leav_options['default_gateway'] ?>" maxlength="15" size="40" />
                                <br /><?php _e('Leave blank to use Windows default gateway.<br/>If you use a non-default gateway configuration on your windows system, you might have to enter this gateway IP here', 'leav') ?>.
                            </td>
                        </tr>
                    </table>
                <?php } ?>


                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Email domain for simulating sending of emails to entered email addresses', 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="wp_mail_domain" type="text" size="40" value="<?php echo ( $leav_options['wp_mail_domain']); ?>" required="required" minlength="5" pattern="^([A-Za-z0-9]+\.)*[A-Za-z0-9][A-Za-z0-9]+\.[A-Za-z]{2,18}$"/>
                            </label>
                            <p class="description">
                                <?php _e('This Email domain is used for simulating the sending of an email from ', 'leav'); echo("no-reply@<strong>" . $leav_options['wp_mail_domain'] ); _e('</strong> to the entered email address, that gets validated.<br/><strong>Use the email domain that you use for sending emails from your WordPress instance!</strong><br/>You can test all form validations of your WordPress instance as many times as you need to (it doesn\'t count against your daily quota in the light edition) with emails from this domain', 'leav') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Allow email adresses from user-defined domain whitelist', 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="yes" <?php if ($leav_options['use_user_defined_domain_whitelist'] == 'yes') { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="no" <?php if ($leav_options['use_user_defined_domain_whitelist'] == 'no') { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'leav'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Email addresses from the listed domains will be accepted without further checks (if active). <br/><strong>Use one domain per line</strong>.<br/><strong>Default: Yes</strong>', 'leav'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_whitelist" name="user_defined_domain_whitelist" rows="7" cols="40" placeholder="your-whitelisted-domain-1.com
your-whitelisted-domain-2.com"><?php echo $leav_options['user_defined_whitelist'] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Allow email adresses from user-defined email whitelist', 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="yes" <?php if ($leav_options['use_user_defined_email_whitelist'] == 'yes') { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="no" <?php if ($leav_options['use_user_defined_email_whitelist'] == 'no') { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'leav'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Email addresses on this list will be accepted without further checks (if active). <br/><strong>Use one email address per line</strong>.<br/><strong>Default: Yes</strong>', 'leav'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_whitelist" name="user_defined_email_whitelist" rows="7" cols="40" placeholder="your.whitelisted@email-1.com
your.whitelisted@email-2.com"><?php echo $leav_options['user_defined_whitelist'] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Reject email adresses from user-defined domain blacklist', 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="yes" <?php if ($leav_options['use_user_defined_domain_blacklist'] == 'yes') { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="no" <?php if ($leav_options['use_user_defined_domain_blacklist'] == 'no') { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'leav'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Email addresses from these domains will be rejected (if active). <br/><strong>Use one domain per line</strong>.<br/><strong>Default: Yes</strong>', 'leav'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_blacklist" name="user_defined_domain_blacklist" rows="7" cols="40" placeholder="your-blacklisted-domain-1.com
your-blacklisted-domain-2.com"><?php echo $leav_options['user_defined_domain_blacklist'] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Reject email adresses from user-defined email blacklist', 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="yes" <?php if ($leav_options['use_user_defined_email_blacklist'] == 'yes') { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="no" <?php if ($leav_options['use_user_defined_email_blacklist'] == 'no') { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'leav'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Email addresses from this list will be rejected (if active). <br/><strong>Use one email address per line</strong>.<br/><strong>Default: Yes</strong>', 'leav'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_blacklist" name="user_defined_email_blacklist" rows="7" cols="40" placeholder="your-blacklisted-domain-1.com
your-blacklisted-domain-2.com"><?php echo $leav_options['user_defined_email_blacklist'] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Reject email adresses from domains on LEAV\'s comprehensive and frequently updated list of disposable email services', 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="block_disposable_email_service_domains" type="radio" value="yes" <?php if ($leav_options['block_disposable_email_service_domains'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="block_disposable_email_service_domains" type="radio" value="no" <?php if ($leav_options['block_disposable_email_service_domains'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <?php 
                                    _e('Currently we have ', 'leav'); 
                                    echo ('1396 '); 
                                    _e('domains listed as either known disposable email address service providers or spammers. Users that make use of disposable email address services might just want to protect their own privacy. But they might also be spammers. There is no good choice. In doubt we encourage you to value your lifetime and reject email addresses from these domains. We frequently update this list. For retrieving updates, just update the plugin when new versions come out. For blocking domains of your own choosing, use the user-defined blacklist option above.<br/><strong>Default: Yes</strong>', 'leav'); ?>
                            </p>
                        </td>
                    </tr>

                </table>
                <h2><?php _e('Accepting pingbacks / trackbacks', 'leav') ?></h2>
                <?php _e('Pingbacks and trackbacks can\'t be validated because they don\'t come with an email address, that could be run through our validation process.</br>Therefore <strong>pingbacks and trackbacks pose a certain spam risk</strong>. They could also be free marketing.<br/>By default we therefore accept them. But feel free to reject them.', 'leav') ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Accept pingbacks', 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="yes" <?php if ($leav_options['accept_pingbacks'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="no" <?php if ($leav_options['accept_pingbacks'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <strong><?php _e('Default:', 'leav') ?> <?php _e('Yes', 'leav') ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Accept trackbacks', 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="yes" <?php if ($leav_options['accept_trackbacks'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="no" <?php if ($leav_options['accept_trackbacks'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <strong><?php _e('Default:', 'leav') ?> <?php _e('Yes', 'leav') ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>
                <h2><?php _e('Control which of WordPress\'s functions and plugins should be email validated by LEAV', 'leav') ?></h2>
                <?php _e('LEAV - Last Email Address Validator is currently capable of validating the email<br/>addresses for the following WordPress features and plugins (if installed and activated): <br/><ol><li>WordPress user registration (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)</li><li>WordPress comments (<a href="/wp-admin/options-discussion.php" target="_blank">Settings -> Discussion)</li><li><a href="https://wordpress.org/plugins/woocommerce/" target="_blank"> WooCommerce (plugin)</a></li><li><a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7 (plugin)</a></li><li><a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">WPForms (lite and pro) (plugin)</a></li><li><a href="https://wordpress.org/plugins/ninja-forms/" target="_blank">Ninja Forms (plugin)</a></li></ol></br>', 'leav') ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Control which functions and plugins to validate with LEAV', 'leav') ?>:</th>
                        <td/>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('WordPress user registration', 'leav') ?>:</th>
                        <td>
                            <?php if( get_option('users_can_register') == 1 && $leav_options['validate_wp_standard_user_registration_email_addresses'] == 'yes' ) : ?>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="yes" <?php if ($leav_options['validate_wp_standard_user_registration_email_addresses'] == 'yes') { echo 'checked="checked" '; } ?>/><?php _e('Yes', 'leav') ?></label>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="no" <?php if ($leav_options['validate_wp_standard_user_registration_email_addresses'] == 'no') { echo 'checked="checked" '; } ?>/><?php _e('No', 'leav') ?></label>
                            <p class="description">
                                <?php _e('This validates all registrants email address\'s that register through WordPress\'s standard user registration.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( get_option('users_can_register') == 0 || $leav_options['validate_wp_standard_user_registration_email_addresses'] == 'no' )
                                  {
                                      _e('WordPress\'s built-in user registration is currently deactivated (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)', 'leav');
                                  }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('WordPress comments', 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="yes" <?php if ($leav_options['validate_wp_comment_user_email_addresses'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="no" <?php if ($leav_options['validate_wp_comment_user_email_addresses'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e('This validates all (not logged in) commentator\'s email address\'s that comment through WordPress\'s standard comment functionality.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('WooCommerce', 'leav') ?>:</th>
                        <td>
                            <?php if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
                            <label>
                                <input name="validate_woocommerce_registration" type="radio" value="yes" <?php if ($leav_options['validate_woocommerce_registration'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_woocommerce_registration" type="radio" value="no" <?php if ($leav_options['validate_wp_comment_user_email_addresses'] == 'no') { echo 'checked="checked" '; } ?>/><?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e('Validate all WooCommerce email addresses during registration and checkout.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( 'woocommerce/woocommerce.php' ) )
                                  {
                                      echo "WooCommerce "; 
                                      _e('not found in list of active plugins', 'leav');
                                  }
                            ?>

                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Contact Form 7', 'leav') ?>:</th>
                        <td>
                            <?php if( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )  ) : ?>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="yes" <?php if ($leav_options['validate_cf7_email_fields'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="no" <?php if ($leav_options['validate_cf7_email_fields'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e('Validate all Contact Form 7 email address fields.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) )
                                  {
                                      echo "Contact Form 7 "; 
                                      _e('not found in list of active plugins', 'leav');
                                  }
                            ?>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row"><?php _e('WPForms (lite and pro)', 'leav') ?>:</th>
                        <td>
                            <?php if( is_plugin_active( 'wpforms-lite/wpforms.php' ) || is_plugin_active( 'wpforms/wpforms.php' )  ) : ?>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="yes" <?php if ($leav_options['validate_wpforms_email_fields'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="no" <?php if ($leav_options['validate_wpforms_email_fields'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e('Validate all WPForms email address fields.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( 'wpforms-lite/wpforms.php' ) && ! is_plugin_active( 'wpforms/wpforms.php' ) )
                                  {
                                      echo "WPForms "; 
                                      _e('not found in list of active plugins', 'leav');
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Ninja Forms', 'leav') ?>:</th>
                        <td>
                            <?php if( is_plugin_active( 'ninja-forms/ninja-forms.php' )  ) : ?>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="yes" <?php if ($leav_options['validate_ninja_forms_email_fields'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="no" <?php if ($leav_options['validate_ninja_forms_email_fields'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e('Validate all Ninja Forms email address fields.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( 'ninja-forms/ninja-forms.php' ) )
                                  {
                                      echo "Ninja Forms "; 
                                      _e('not found in list of active plugins', 'leav');
                                  }
                            ?>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e('Save Changes', 'leav') ?>" />
                </p>
            </form>

            <?php _e('<h1>Feature Requests</h1>If you look for more plugins, we at <a href="https://smings.com" target="_blank">smings.com</a> (website will soon be online) are always happy to make<br/> LEAV - Last Email Address Validator better than it is and help you. Just shoot us an email to <br/><a href="mailto:leav-feature-requests@smings.com">leav-feature-requests@smings.com</a>.<br/><br/><h1>Help us help you!</h1>Lastly - if LEAV - Last Email Address Validator delivers substancial value to you, i.e. saving<br/> lots of your precious non-renewable lifetime, because it filters out tons of <br/>spam attempts, please show us your appreciation and consider a <strong><a href="https://paypal.me/DirkTornow" target="_blank">one-time donation</a></strong> or become a patreon on our patreon page at <strong><a href="https://www.patreon.com/smings" target="_blank">patreon.com/smings</a></strong>.', 'leav') ?>
        </div>
        <div class="wrap">
            <h1><?php _e('Statistics', 'leav') ?></h1>
            <div class="card">
                <p>
                    <?php echo sprintf(_e('Version', 'leav') . ': <strong>%s</strong>', leav_version()) ?>&nbsp;|
                    <?php echo sprintf(_e('Spam attacks fended', 'leav') . ': <strong>%s</strong>', leav_get_blocked_email_address_count()) ?>
                </p>
                <p>
                    <a href="https://github.com/smings/leav/wiki"><?php _e('Documentation', 'leav') ?></a>&nbsp;|
                    <a href="https://github.com/smings/leav/issues"><?php _e('Bugs', 'leav') ?></a>
                </p>
            </div>
        </div>
<?php
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