<?php
/*
Plugin Name: Last Email Validator (LEV)
Plugin URI: https://github.com/smings/last-email-validator/
Description: Last Email Validator (LEV) by smings provides email address validation for WP's user registration, comments, WooCommerce, Contact Form 7'
Version: 1.1.3
Author: Dirk Tornow
Author URI: https://smings.de
Text Domain: last-email-validator
*/

// Example of how to get the current translation strings from the 
// WordPress plugin code. 
// `xgettext --default-domain=last-email-validator --language=PHP --keyword=__ --keyword=_e --sort-by-file --copyright-holder="Dirk Tornow" --package-name=last-email-validator --package-version=1.0.3 --from-code=UTF-8 --msgid-bugs-address="translastions@smings.com" -i -p languages/ *.php`

// Example of how to merge a newer version with an existing po file
// `msgmerge -i -o new_merged.po last-email-validator-de_DE.po last-email-validator.po`

// Example of how to create an mo file
// `msgfmt -o last-email-validator-de_DE.mo last-email-validator-de_DE.po`

// for debugging only
// 
$d = false;
if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}

require_once('includes/last-email-validator.inc.php');

// <-- i18n textdomain -->
load_plugin_textdomain('last-email-validator');
if($d)
    write_log("Loaded text domain");
// <-- trash mail service blacklist -->
$disposable_email_service_domain_list_file = plugin_dir_path(__FILE__) . 'data/disposable_email_service_provider_domain_list.txt';

// <-- plugin options -->
$last_email_validator_options = array();

if (get_option('last_email_validator_options'))
    $last_email_validator_options = get_option('last_email_validator_options');

if (empty($last_email_validator_options['spam_email_addresses_blocked_count']))
    $last_email_validator_options['spam_email_addresses_blocked_count'] = '0';

if (empty($last_email_validator_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed']))
    $last_email_validator_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed'] = 'no';

if (empty($last_email_validator_options['default_gateway']))
    $last_email_validator_options['default_gateway'] = '';

if (empty($last_email_validator_options['accept_pingbacks']))
    $last_email_validator_options['accept_pingbacks'] = 'yes';

if (empty($last_email_validator_options['accept_trackbacks']))
    $last_email_validator_options['accept_trackbacks'] = 'yes';

if (empty($last_email_validator_options['use_user_defined_blacklist']))
    $last_email_validator_options['use_user_defined_blacklist'] = 'yes';

if (empty($last_email_validator_options['user_defined_blacklist']))
    $last_email_validator_options['user_defined_blacklist'] = "your_blacklisted_domain1.here\nyour_blacklisted_domain2.here";

if (empty($last_email_validator_options['block_disposable_email_service_domains']))
    $last_email_validator_options['block_disposable_email_service_domains'] = 'yes';

if (empty($last_email_validator_options['disposable_email_service_domain_list'])) 
{
    $disposable_email_service_domains = file_exists($disposable_email_service_domain_list_file) ? file_get_contents($disposable_email_service_domain_list_file) : '';
    $last_email_validator_options['disposable_email_service_domain_list'] = $disposable_email_service_domains;
}

if (empty($last_email_validator_options['validate_wp_standard_user_registration_email_addresses']))
    $last_email_validator_options['validate_wp_standard_user_registration_email_addresses'] = 'yes';

if (empty($last_email_validator_options['validate_wp_comment_user_email_addresses']))
    $last_email_validator_options['validate_wp_comment_user_email_addresses'] = 'yes';

if (empty($last_email_validator_options['validate_woocommerce_registration']))
    $last_email_validator_options['validate_woocommerce_registration'] = 'yes';

if (empty($last_email_validator_options['validate_cf7_email_fields']))
    $last_email_validator_options['validate_cf7_email_fields'] = 'yes';

// <-- os detection -->
$is_windows = strncasecmp(PHP_OS, 'WIN', 3) == 0 ? true : false;

// make suer we have a `getmxrr` function on windows
if (! function_exists('getmxrr'))
{
    function getmxrr($hostName, &$mxHosts, &$mxPreference)
    {
        global $d;
        global $last_email_validator_options;
        
        $gateway = $last_email_validator_options['default_gateway'];
    
        $nsLookup = shell_exec("nslookup -q=mx {$hostName} {$gateway} 2>nul");
        preg_match_all("'^.*MX preference = (\d{1,10}), mail exchanger = (.*)$'simU", $nsLookup, $mxMatches);

        if (count($mxMatches[2]) > 0)
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

function last_email_validator_init() 
{
    global $d;
    add_filter('pre_comment_approved', 'last_email_validator_validate_comment_email_addresses', 99, 2);
    add_filter('registration_errors', 'last_email_validator_validate_registration_email_addresses', 99, 3);

    # Filtering for WooCommerce, if it is installed and active
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) )
    {
        if($d)
            write_log("WooCommerce validation active");
        add_filter('woocommerce_registration_errors', 'last_email_validator_validate_registration_email_addresses', 10, 3);
    }

    # Filtering for contact form 7 - this doesn't work well yet tho. So I deactivate it
    if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) )
    {
        if($d)
            write_log("CF7 validation active");
        add_filter('wpcf7_validate_email', 'last_email_validator_validate_cf7_email_addresses', 20, 2); // Email field
        add_filter('wpcf7_validate_email*', 'last_email_validator_validate_cf7_email_addresses', 20, 2); // Req. Email field
    }

    if ( is_admin() )
    {
        add_action('admin_menu', 'last_email_validator_add_options_page');
        add_action('admin_enqueue_scripts', 'last_email_validator_enque_scripts');
    }
}

function last_email_validator_validate_comment_email_addresses($approved, $comment_data)
{
    global $d;

    // if comment is already marked as spam or trash
    // no further investigation is done
    if ( 
        $last_email_validator_options['validate_wp_comment_user_email_addresses'] == 'no' ||
        $approved === 'spam' || 
        $approved === 'trash' 
    ) 
    {
        return $approved;
    }

    global $user_ID;
    global $last_email_validator_options;
    
    // currently it is not possible to check trackbacks / pingbacks while there
    // is no 'comment_author_email' given in the trackback values
    
    // check if trackbacks should be left or dropped out
    if ( (isset($comment_data['comment_type'])) && ($comment_data['comment_type'] == 'trackback') )
    {
        if ($last_email_validator_options['accept_trackbacks'] == 'yes') 
        {
            return $approved;
        } 
        else
        {
            return 'trash';
        }
    }
    
    // check if pingbacks should be left or dropped out
    if ((isset($comment_data['comment_type'])) && ($comment_data['comment_type'] == 'pingback'))
    {
        if ($last_email_validator_options['accept_pingbacks'] == 'yes')
        {
            return $approved;
        } 
        else 
        {
            return 'trash';
        }
    }
    
    // if it's a comment and not a logged in user - check mail
    if ( get_option('require_name_email') && !$user_ID )
    {
        $email_address = $comment_data['comment_author_email'];
        return last_email_validator_validate_email_address($approved, $email_address);
    }

    return $approved;
}

function last_email_validator_validate_registration_email_addresses($errors, $sanitized_user_login, $user_email)
{
    global $d;
    global $last_email_validator_options;
    if($d)
        write_log("Trying to validate registration email addresse '$user_email'");
    if ($last_email_validator_options['validate_wp_standard_user_registration_email_addresses'] == 'yes')
    {
        $approved = last_email_validator_validate_email_address('', $user_email);
        if ($approved === 'spam') 
            $errors->add('wp_mail-validator-registration-error', __( 'This email domain is not accepted/valid. Try another one.', 'last-email-validator'));
    }

    return $errors;
}

function last_email_validator_validate_cf7_email_addresses($result, $tags)
{
    global $d;
    $tags = new WPCF7_FormTag( $tags );
    $type = $tags->type;
    $name = $tags->name;
    if($d)
        write_log("Trying to validate CF7 Email address\nType = '$type'");
    if ($type == 'email' || $type == 'email*')
    {
        $user_email = sanitize_email($_POST[$name]);
        if($d)
            write_log("Validating CF7 email address '$user_email'");
        $approved = last_email_validator_validate_email_address('', $user_email);
        if ( $approved === 'spam')
            $result->invalidate( $tags, __( 'The e-mail address entered is invalid.', 'contact-form-7' ));
        if($d)
            write_log("Result of validating CF7 email address '$user_email' => approved = '$approved'");
    }
    return $result;
}


function last_email_validator_validate_email_address($approved, $email_address)
{
    global $d;
    global $last_email_validator_options;
    if($d)
        write_log("Entering function `last_email_validator_validate_email_address`");
    // check mail-address against user defined blacklist (if enabled)

    if ($last_email_validator_options['use_user_defined_blacklist'] == 'yes')
    {
        if($d)
            write_log("Trying to block user-defined blacklist entries");
        $regexps = preg_split('/[\r\n]+/', $last_email_validator_options['user_defined_blacklist'], -1, PREG_SPLIT_NO_EMPTY);

        foreach ($regexps as $regexp)
        {
            if($d)
                write_log("Matching '$regexp' against '$email_address'");
            if (preg_match('/' . $regexp . '/', $email_address))
            {
                if($d)
                    write_log("---> Email address stems from $regexp -> returning 'spam'");
                increment_count_of_blocked_email_addresses();
                return 'spam';
            }
        }
    }

    // check mail-address against disposable email address services domain blacklist (if enabled)
    if ($last_email_validator_options['block_disposable_email_service_domains'] == 'yes')
    {
        if($d)
            write_log("Trying to block disposable email service blacklist entries");
        $regexps = preg_split('/[\r\n]+/', $last_email_validator_options['disposable_email_service_domain_list'], -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($regexps as $regexp)
        {
            if($d)
                write_log("Matching '$regexp' against '$email_address'");
            if (preg_match('/' . $regexp . '/', $email_address))
            {
                if($d)
                    write_log("---> Email address stems from $regexp -> returning 'spam'");
                increment_count_of_blocked_email_addresses();
                return 'spam';
            }
        }
    }

    $mail_validator = new LEVemailValidator();
    $return_code = $mail_validator->validateEmailAddress($email_address);

    if($d)
        write_log("Result of validating email address is: $return_code");
    if ( ( 
           $return_code == SMTP_CONNECTION_ATTEMPTS_TIMED_OUT ||
           $return_code == EMAIL_ADDRESS_SYNTAX_CORRECT_BUT_CONNECTION_FAILED
         ) 
        &&
        $last_email_validator_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed'] == 'yes'
    )
        return $approved;
    elseif($return_code == VALID_EMAIL_ADDRESS)
        return $approved;
    else
    {
        increment_count_of_blocked_email_addresses();
        return 'spam';
    }

}

// <-- database update function -->

function increment_count_of_blocked_email_addresses()
{
    global $d;
    global $last_email_validator_options;

    $last_email_validator_options['spam_email_addresses_blocked_count'] = ($last_email_validator_options['spam_email_addresses_blocked_count'] + 1);
    update_option('last_email_validator_options', $last_email_validator_options);
}

// <-- theme functions / statistics -->

function last_email_validator_powered_by_label($string_before = "", $string_after = "")
{
    global $d;
    $label = $string_before . __('Anti spam protected by', 'last-email-validator') . ': <a href="https://github.com/smings/last-email-validator" title="Last Email Validator (LEV)" target="_blank">Last Email Validator (LEV)</a> - <strong>%s</strong> ' . __('Spam email addresses blocked', 'last-email-validator') . '!' . $string_after;
    return sprintf($label, last_email_validator_get_blocked_email_address_count());
}

function last_email_validator_get_blocked_email_address_count()
{
    global $d;
    global $last_email_validator_options;
    return $last_email_validator_options['spam_email_addresses_blocked_count'];
}

function last_email_validator_version()
{
    global $d;
    $plugin = get_plugin_data( __FILE__ );
    return $plugin['Version'];
}

// <-- admin menu option page -->

function last_email_validator_add_options_page()
{
    global $d;
    add_options_page('Last Email Validator (LEV)', 'Last Email Validator (LEV)', 'edit_pages', basename(__FILE__, ".php"), 'last_email_validator_options_page');
}

function last_email_validator_options_page()
{
    global $d;
    global $last_email_validator_options;
    global $is_windows;
    global $disposable_email_service_domain_list_file;
    $WP_DOMAIN_NAME = getenv( "HTTP_HOST" );

    if (isset($_POST['last_email_validator_options_update_type']))
    {
        $wp_mail_validator_updated_options = $last_email_validator_options;
        $update_notice = '';

        if($d)
            write_log("last_email_validator_options_update_type = '" . $_POST['last_email_validator_options_update_type'] . "'");
        if ($_POST['last_email_validator_options_update_type'] === 'update')
        {
            if($d)
                write_log( "We are in the first if in line 338");
            foreach ($_POST as $key => $value)
            {
                if($d)
                    write_log("key=value => $key=$value");
                if ($key !== 'last_email_validator_options_update_type' && $key !== 'submit')
                {
                    $wp_mail_validator_updated_options[$key] = $value;
                }
            }
            $update_notice = __('Last Email Validator (LEV) options updated', 'last-email-validator');
        } 
        elseif ($_POST['last_email_validator_options_update_type'] === 'restore_disposable_email_service_domain_blacklist')
        {
            if($d)
                write_log( "We are in the elsif part in line 349");
            $wp_mail_validator_updated_options['disposable_email_service_domain_list'] = file_get_contents($disposable_email_service_domain_list_file);
            $update_notice = __('Last Email Validator (LEV) disposable email services domain blacklist restored', 'last-email-validator');
        }
        else
        {
            if($d)
                write_log("nothing matched");
        }

        update_option('last_email_validator_options', $wp_mail_validator_updated_options);
        $last_email_validator_options = get_option('last_email_validator_options');
    ?>
        <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
            <p>
                <strong><?php echo $update_notice ?>.</strong>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php echo __('Dismiss this notice', 'last-email-validator') ?>.</span>
            </button>
        </div>
    <?php
    }
    ?>
        <div class="wrap">
            <h1><?php echo __("Settings for '<strong>Last Email Validator (LEV)</strong> <i>by smings</i>'", 'last-email-validator') ?></h1>
            <?php echo __("Last Email Validator (LEV) validates email addresses of various WordPress functions and plugins in the following ways: <br/><ol><li>Filter against user-defined domain blacklist (if activated)</li><li>Filter against LEV's built-in extensive blacklist of disposable email service domains (if activated)</li><li>Check if the email address is syntactically correct (always)</li><li>Check if the email address's domain has a DNS entry with MX records (always)</li><li>Connect to one of the MX servers and simulate the sending of an email <br/>from <strong>no-reply@$WP_DOMAIN_NAME</strong> to the entered email address (always)</li></ol>Below you can control in which way the selected WordPress functions and plugins will validate entered email adresses" , 'last-email-validator')?>
            <form name="wp_mail_validator_options" method="post">
                <input type="hidden" name="last_email_validator_options_update_type" value="update" />

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php echo __('Reject email adresses from user-defined blacklist', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_blacklist" type="radio" value="yes" <?php if ($last_email_validator_options['use_user_defined_blacklist'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_blacklist" type="radio" value="no" <?php if ($last_email_validator_options['use_user_defined_blacklist'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('Email addresses from the domains on this blacklist will be rejected (if active). <br/><strong>Use one domain per line</strong>.<br/><strong>Default: Yes</strong>', 'last-email-validator') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_blacklist" name="user_defined_blacklist" rows="15" cols="40"><?php echo $last_email_validator_options['user_defined_blacklist'] ?></textarea>
                            </label>
                            <p class="description">
                                <span id="user_defined_blacklist_line_count">0</span>
                                <?php echo __('User-defined blacklisted email domains', 'last-email-validator') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Reject email adresses from LEV\'s comprehensive and frequently updated list of disposable email services', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="filter_against_disposable_email_service_domain_list" type="radio" value="yes" <?php if ($last_email_validator_options['block_disposable_email_service_domains'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="filter_against_disposable_email_service_domain_list" type="radio" value="no" <?php if ($last_email_validator_options['block_disposable_email_service_domains'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('The listed domains are currently known services that provide single use email<br/>addresses (disposable email addresses). Users that make use of these services<br/>might just want to protect their own privacy. Users might also be spammers. <br/>There is no good choice. In doubt we encourage you to value your lifetime and <br/>reject email addresses from these domains. We frequently update this list. For<br/>retrieving updates, click the update button. For blocking domains of your <br/>choosing, use the user-defined blacklist option above.<br/><strong>Default: Yes</strong>', 'last-email-validator') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="disposable_email_service_domain_blacklist" name="disposable_email_service_domain_blacklist" rows="15" cols="40" readonly><?php echo $last_email_validator_options['disposable_email_service_domain_list'] ?></textarea>
                            </label>
                            <p class="description">
                                <span id="disposable_email_service_domain_blacklist_line_count">0</span>
                                <?php echo __('Entries', 'last-email-validator') ?>
                            </p>
                            <p class="submit">
                                <input class="button button-primary" type="submit" id="disposable_email_service_domain_blacklist_restore" name="submit" value="<?php echo __('Update list', 'last-email-validator') ?>" />
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Accept syntactically correct email addresses on connection errors', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_syntactically_correct_email_addresses_when_connection_to_mx_failed" type="radio" value="yes" <?php if ($last_email_validator_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="accept_syntactically_correct_email_addresses_when_connection_to_mx_failed" type="radio" value="no"<?php if ($last_email_validator_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __("In order to thoroughly validate email addresses, LEV tries to connect to at <br/>least one of the MX (Mail eXchange) servers in the email domain's DNS record. <br/>Usually there are ≥2 MX servers. When this option is set to 'Yes', <br/>the failure to connect to any of the MX servers will be ignored and <br/>syntactically correct email addresses will be accepted.<br/>We do not recommend this, since it will result in more spam.  <br/><strong>Default: No</strong>", 'last-email-validator') ?>.
                            </p>
                        </td>
                    </tr>


                </table>
                <h2><?php echo __('Accepting pingbacks / trackbacks without validation', 'last-email-validator') ?></h2>
                <?php echo __('Pingbacks and trackbacks can\'t be validated because they don\'t come with an email address, that could be run through our validator.</br>Therefore <strong>pingbacks and trackbacks pose a certain spam risk</strong>. They are also free marketing.<br/>By default we therefore accept them. But feel free to reject them.', 'last-email-validator') ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php echo __('Accept pingbacks', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="yes" <?php if ($last_email_validator_options['accept_pingbacks'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="no" <?php if ($last_email_validator_options['accept_pingbacks'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <strong><?php echo __('Default:', 'last-email-validator') ?> <?php echo __('Yes', 'last-email-validator') ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Accept trackbacks', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="yes" <?php if ($last_email_validator_options['accept_trackbacks'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="no" <?php if ($last_email_validator_options['accept_trackbacks'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <strong><?php echo __('Default:', 'last-email-validator') ?> <?php echo __('Yes', 'last-email-validator') ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>
                <h2><?php echo __('Control which of WordPress\'s functions and plugins should be email validated by LEV', 'last-email-validator') ?></h2>
                <?php echo __('Last Email Validator (LEV) is currently capable of validating the email<br/>addresses for the following WordPress features and plugins (if installed and activated): <br/><ol><li>WordPress user registration (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)</li><li>WordPress comments (<a href="/wp-admin/options-discussion.php" target="_blank">Settings -> Discussion)</li><li><a href="https://wordpress.org/plugins/woocommerce/" target="_blank"> WooCommerce (plugin)</a></li><li><a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7 (plugin)</a></li></ol></br>', 'last-email-validator') ?>

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php echo __('1. Validate WordPress user registration', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="check_registrations" type="radio" value="yes" <?php if ($last_email_validator_options['validate_wp_standard_user_registration_email_addresses'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="check_registrations" type="radio" value="no" <?php if ($last_email_validator_options['validate_wp_standard_user_registration_email_addresses'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('This validates all registrants email address\'s that register through WordPress\'s standard user registration.<br/><strong>Default: Yes</strong>', 'last-email-validator') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('2. Validate WordPress comments', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="yes" <?php if ($last_email_validator_options['validate_wp_comment_user_email_addresses'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="no" <?php if ($last_email_validator_options['validate_wp_comment_user_email_addresses'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('This validates all commentor email address\'s that comment through WordPress\'s standard comment functionality.<br/><strong>Default: Yes</strong>', 'last-email-validator') ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo __('3. Validate WooCommerce (plugin)', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="validate_woocommerce_registration" type="radio" value="yes" <?php if ($last_email_validator_options['validate_woocommerce_registration'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="validate_woocommerce_registration" type="radio" value="no" <?php if ($last_email_validator_options['validate_wp_comment_user_email_addresses'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('Validate all WooCommerce email addresses during registration and checkout.<br/><strong>Default: Yes</strong>', 'last-email-validator') ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo __('4. Validate Contact Form 7 (plugin)', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="yes" <?php if ($last_email_validator_options['validate_cf7_email_fields'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="no" <?php if ($last_email_validator_options['validate_cf7_email_fields'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('Validate all Contact Form 7 email address fields.<br/><strong>Default: Yes</strong>', 'last-email-validator') ?>
                            </p>
                        </td>
                    </tr>


                </table>
                <?php if ($is_windows) { ?>
                    <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                        <tr>
                            <th scope="row"><?php echo__('Default gateway IP', 'last-email-validator') ?>:</th>
                            <td>
                                <input name="default_gateway" type="text" id="default_gateway" value="<?php echo $last_email_validator_options['default_gateway'] ?>" maxlength="15" size="40" />
                                <br /><?php echo __('Leave blank to use Windows default gateway', 'last-email-validator') ?>.
                            </td>
                        </tr>
                    </table>
                <?php } ?>


                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php echo __('Save Changes', 'last-email-validator') ?>" />
                </p>
            </form>

            <?php echo __('<h1>Feature Requests</h1>If you look for more plugins, we at <a href="smings.com" target="_blank">smings.com</a> (website will soon be online) are always happy to make<br/> Last Email Validator (LEV) better than it is and help you. Just shoot us an email to <br/><a href="mailto:lev-feature-requests@smings.com">lev-feature-requests@smings.com</a>.<br/><br/><h1>Help us help you!</h1>Lastly - if Last Email Validator (LEV) delivers substancial value to you, i.e. saving<br/> lots of your precious non-renewable lifetime, because it filters out tons of <br/>spam attempts, please show us your appreciation and consider a one-time donation', 'last-email-validator') ?>

            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                <input type="hidden" name="cmd" value="_s-xclick" />
                <input type="hidden" name="hosted_button_id" value="4Y6G6JJ7DH4FQ" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" title="PayPal - The safer, easier way to donate online!" alt="Donate with PayPal button" />
                <img alt="" border="0" src="https://www.paypal.com/en_DE/i/scr/pixel.gif" width="1" height="1" />
            </form>

            <?php echo __('or become a patreon on our patreon page at', 'last-email-validator') ?>
                <strong>
                    <a href="https://www.patreon.com/smings" target="_blank">
            <?php echo __('patreon.com/smings.', 'last-email-validator') ?>
                    </a>
                </strong>
        </div>
        <div class="wrap">
            <h1><?php echo __('Statistics', 'last-email-validator') ?></h1>
            <div class="card">
                <p>
                    <?php echo sprintf(__('Version', 'last-email-validator') . ': <strong>%s</strong>', last_email_validator_version()) ?>&nbsp;|
                    <?php echo sprintf(__('Spam attacks fended', 'last-email-validator') . ': <strong>%s</strong>', last_email_validator_get_blocked_email_address_count()) ?>
                </p>
                <p>
                    <a href="https://github.com/smings/last-email-validator/wiki"><?php echo __('Documentation', 'last-email-validator') ?></a>&nbsp;|
                    <a href="https://github.com/smings/last-email-validator/issues"><?php echo __('Bugs', 'last-email-validator') ?></a>
                </p>
            </div>
        </div>
<?php
}

function last_email_validator_enque_scripts($hook)
{
    global $d;
    if($d)
        write_log("Hook = '" . $hook . "'");
    if ('settings_page_last-email-validator' != $hook)
    {
        return;
    }

    wp_enqueue_script('jquery.mask', plugin_dir_url(__FILE__) . 'scripts/jquery.mask.min.js', array(), '1.14.15');
    wp_enqueue_script('lev', plugin_dir_url(__FILE__) . 'scripts/lev.min.js', array(), '1.0.0');
}

// <-- plugin installation on activation -->

function last_email_validator_activate_plugin()
{
    global $d;
    global $wpdb;
    global $last_email_validator_options;

    // migration of existing data in older versions
    $table_name = $wpdb->prefix . "last_email_validator";

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name)
    {
        $sql = "SELECT eaten FROM " . $table_name . " LIMIT 1;";
        $count = $wpdb->get_var($sql);

        $last_email_validator_options['spam_email_addresses_blocked_count'] = $count;
        update_option('lasr_email_validator_options', $last_email_validator_options);

        $sql = "DROP TABLE IF EXISTS " . $table_name . ";";
        $wpdb->query($sql);
    }
}

function last_email_validator_uninstall_plugin()
{
    global $d;
    // global $wpdb;
    // global $last_email_validator_options;

    // $table_name = $wpdb->prefix . "last_email_validator";

    delete_option('last_email_validator_options');

    // if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name)
    // {
    //     $sql = "DROP TABLE IF EXISTS " . $table_name . ";";
    //     $wpdb->query($sql);
    // }
    // $table_name = $wpdb->prefix . "options";
    // $sql = 'DELETE FROM ' . $table_name . ' WHERE option_name="last_email_validator_options;';
}


// <-- hooks -->
register_activation_hook( __FILE__, 'last_email_validator_activate_plugin');
register_uninstall_hook( __FILE__, 'last_email_validator_uninstall_plugin');
add_action( 'init', 'last_email_validator_init' );
?>