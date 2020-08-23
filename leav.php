<?php
/*
Plugin Name: LEAV Last Email Adress Validator by smings (light edition)
Plugin URI: https://github.com/smings/leav/
Description: LEAV provides the best free email address validation plugin for WP registration/comments, Contact Form 7, WooCommerce and more plugins to come...
Version: 1.1.3
Author: Dirk Tornow
Author URI: https://smings.de
Text Domain: leav
*/

// Example of how to get the current translation strings from the 
// WordPress plugin code. 
// `xgettext --default-domain=leav --language=PHP --keyword=__ --keyword=_e --sort-by-file --copyright-holder="Dirk Tornow" --package-name=leav --package-version=1.0.3 --from-code=UTF-8 --msgid-bugs-address="translastions@smings.com" -i -p languages/ *.php`

// Example of how to merge a newer version with an existing po file
// `msgmerge -i -o new_merged.po leav-de_DE.po leav.po`

// Example of how to create an mo file
// `msgfmt -o leav-de_DE.mo leav-de_DE.po`

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

$WP_DOMAIN_PARTS = explode( '.', getenv( "HTTP_HOST" ) );
$WP_MAIL_DOMAIN = $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 2 ] . '.' .  $WP_DOMAIN_PARTS[ count($WP_DOMAIN_PARTS) - 1 ];
$disposable_email_service_domain_list_url = 'https://raw.githubusercontent.com/smings/leav-list/master/data/disposable_email_service_provider_domain_list.txt';
require_once('includes/leav.inc.php');
load_plugin_textdomain('leav');
$leav_options = array();

// <-- os detection -->
$is_windows = strncasecmp(PHP_OS, 'WIN', 3) == 0 ? true : false;

// make suer we have a `getmxrr` function on windows
if (! function_exists('getmxrr'))
{
    function getmxrr($hostName, &$mxHosts, &$mxPreference)
    {
        global $d;
        global $leav_options;
        
        $gateway = $leav_options['default_gateway'];
    
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

function leav_init() 
{
    global $d;
    global $leav_options;
    global $disposable_email_service_domain_list_url;
    global $WP_MAIL_DOMAIN;

    add_filter('pre_comment_approved', 'leav_validate_comment_email_addresses', 99, 2);
    add_filter('registration_errors', 'leav_validate_registration_email_addresses', 99, 3);

    # Filtering for WooCommerce, if it is installed and active
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) )
    {
        if($d)
            write_log("WooCommerce validation active");
        add_filter('woocommerce_registration_errors', 'leav_validate_registration_email_addresses', 10, 3);
    }

    # Filtering for contact form 7 - this doesn't work well yet tho. So I deactivate it
    if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) )
    {
        if($d)
            write_log("CF7 validation active");
        add_filter('wpcf7_validate_email', 'leav_validate_cf7_email_addresses', 20, 2); // Email field
        add_filter('wpcf7_validate_email*', 'leav_validate_cf7_email_addresses', 20, 2); // Req. Email field
    }

    if ( is_admin() )
    {
        add_action('admin_menu', 'leav_add_options_page');
        add_action('admin_enqueue_scripts', 'leav_enque_scripts');
    }


    // Now we set and persist the default values for the plugin
    if ( get_option('leav_options') )
        $leav_options = get_option('leav_options');
    
    if ( empty($leav_options['wp_mail_domain']) )
        $leav_options['wp_mail_domain'] = $WP_MAIL_DOMAIN;
    
    if ( empty($leav_options['spam_email_addresses_blocked_count']) )
        $leav_options['spam_email_addresses_blocked_count'] = '0';
    
    if ( empty($leav_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed']))
        $leav_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed'] = 'no';
    
    if ( empty($leav_options['default_gateway']) )
        $leav_options['default_gateway'] = '';
    
    if ( empty($leav_options['accept_pingbacks']) )
        $leav_options['accept_pingbacks'] = 'yes';
    
    if ( empty($leav_options['accept_trackbacks']) )
        $leav_options['accept_trackbacks'] = 'yes';
    
    if ( empty($leav_options['use_user_defined_blacklist']) )
        $leav_options['use_user_defined_blacklist'] = 'yes';
    
    if ( empty($leav_options['user_defined_blacklist']) )
        $leav_options['user_defined_blacklist'] = "your_blacklisted_domain1.here\nyour_blacklisted_domain2.here";
    
    if ( empty($leav_options['block_disposable_email_service_domains']) )
        $leav_options['block_disposable_email_service_domains'] = 'yes';
    
    if ( empty($leav_options['disposable_email_service_domain_list']) ) 
    {
        $disposable_email_service_domains = file_get_contents($disposable_email_service_domain_list_url);
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
    
    update_option('leav_options', $leav_options);

}

function leav_validate_comment_email_addresses($approved, $comment_data)
{
    global $d;

    // if comment is already marked as spam or trash
    // no further investigation is done
    if ( 
        $leav_options['validate_wp_comment_user_email_addresses'] == 'no' ||
        $approved === 'spam' || 
        $approved === 'trash' 
    ) 
    {
        return $approved;
    }

    global $user_ID;
    global $leav_options;
    
    // currently it is not possible to check trackbacks / pingbacks while there
    // is no 'comment_author_email' given in the trackback values
    
    // check if trackbacks should be left or dropped out
    if ( (isset($comment_data['comment_type'])) && ($comment_data['comment_type'] == 'trackback') )
    {
        if ($leav_options['accept_trackbacks'] == 'yes') 
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
        if ($leav_options['accept_pingbacks'] == 'yes')
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
        return leav_validate_email_address($approved, $email_address);
    }

    return $approved;
}

function leav_validate_registration_email_addresses($errors, $sanitized_user_login, $user_email)
{
    global $d;
    global $leav_options;
    if($d)
        write_log("Trying to validate registration email addresse '$user_email'");
    if ($leav_options['validate_wp_standard_user_registration_email_addresses'] == 'yes')
    {
        $approved = leav_validate_email_address('', $user_email);
        if ($approved === 'spam') 
            $errors->add('wp_mail-validator-registration-error', _e( 'This email domain is not accepted/valid. Try another one.', 'leav'));
    }

    return $errors;
}

function leav_validate_cf7_email_addresses($result, $tags)
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
        $approved = leav_validate_email_address('', $user_email);
        if ( $approved === 'spam')
            $result->invalidate( $tags, _e( 'The e-mail address entered is invalid.', 'contact-form-7' ));
        if($d)
            write_log("Result of validating CF7 email address '$user_email' => approved = '$approved'");
    }
    return $result;
}


function leav_validate_email_address($approved, $email_address)
{
    global $d;
    global $leav_options;
    if($d)
        write_log("Entering function `leav_validate_email_address`");
    // check mail-address against user defined blacklist (if enabled)

    if ($leav_options['use_user_defined_blacklist'] == 'yes')
    {
        if($d)
            write_log("Trying to block user-defined blacklist entries");
        $regexps = preg_split('/[\r\n]+/', $leav_options['user_defined_blacklist'], -1, PREG_SPLIT_NO_EMPTY);

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
                increment_count_of_blocked_email_addresses();
                return 'spam';
            }
        }
    }

    $mail_validator = new LastEmailAddressValidator();
    $return_code = $mail_validator->validateEmailAddress($email_address);

    if($d)
        write_log("Result of validating email address is: $return_code");
    if ( ( 
           $return_code == SMTP_CONNECTION_ATTEMPTS_TIMED_OUT ||
           $return_code == EMAIL_ADDRESS_SYNTAX_CORRECT_BUT_CONNECTION_FAILED
         ) 
        &&
        $leav_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed'] == 'yes'
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
    global $leav_options;

    $leav_options['spam_email_addresses_blocked_count'] = ($leav_options['spam_email_addresses_blocked_count'] + 1);
    update_option('leav_options', $leav_options);
}

// <-- theme functions / statistics -->

function leav_powered_by_label($string_before = "", $string_after = "")
{
    global $d;
    $label = $string_before . _e('Anti spam protected by', 'leav') . ': <a href="https://github.com/smings/leav" title="LEAV - Last Email Address Validator" target="_blank">LEAV - Last Email Address Validator</a> - <strong>%s</strong> ' . _e('Spam email addresses blocked', 'leav') . '!' . $string_after;
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

        if($d)
            write_log("leav_options_update_type = '" . $_POST['leav_options_update_type'] . "'");
        if ($_POST['leav_options_update_type'] === 'update')
        {
            if($d)
                write_log( "We are in the first if in line 338");
            foreach ($_POST as $key => $value)
            {
                if($d)
                    write_log("key=value => $key=$value");
                if ($key !== 'leav_options_update_type' && $key !== 'submit')
                {
                    $leav_options[$key] = $value;
                }
            }
            $update_notice = __('LEAV - Last Email Address Validator options updated', 'leav');
        } 
        elseif ($_POST['leav_options_update_type'] === 'restore_disposable_email_service_domain_blacklist')
        {
            $disposable_email_service_domains = file_get_contents($disposable_email_service_domain_list_url);
            $leav_options['disposable_email_service_domain_list'] = $disposable_email_service_domains;
            if($d)
                write_log($leav_options);
            $update_notice = __('Updated LEAV\'s blacklist of disposable email service domains', 'leav');
        }
        else
        {
            if($d)
                write_log("nothing matched");
        }

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
            <h1><?php _e('Settings for <strong>LEAV - Last Email Address Validator</strong> <i>by smings</i> (light edition)', 'leav') ?></h1>
            <?php _e('LEAV - Last Email Address Validator by smings (light edition) validates email addresses of the supported WordPress functions and plugins in the following ways' , 'leav'); ?>
            <ol>
                <li>
                    <?php _e('Check if the email address is syntactically correct (always)', 'leav'); ?>
                </li>
                <li>
                    <?php _e('Filter against user-defined domain blacklist (if activated)' , 'leav'); ?>
                </li>
                <li>
                    <?php _e('Filter against LEAV\'s built-in extensive blacklist of disposable email service domains (if activated)', 'leav'); ?>
                </li>
                <li>
                    <?php _e('Check if the email address\'s domain has a DNS entry with MX records (always)', 'leav'); ?>
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

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Email domain for simulating sending of emails to entered email addresses', 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="wp_mail_domain" type="text" size="40" value="<?php echo ( $leav_options['wp_mail_domain']); ?>" required="required" minlength="5" pattern="^([A-Za-z0-9]+\.)*[A-Za-z0-9][A-Za-z0-9]+\.[A-Za-z]{2,18}$"/>
                            </label>
                            <p class="description">
                                <?php _e('Email domain used for simulating the sending of an email from ', 'leav'); echo("no-reply@<strong>" . $leav_options['wp_mail_domain'] ); _e('</strong> to the entered email address, that gets validated', 'leav') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Reject email adresses from user-defined blacklist', 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_blacklist" type="radio" value="yes" <?php if ($leav_options['use_user_defined_blacklist'] == 'yes') { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_blacklist" type="radio" value="no" <?php if ($leav_options['use_user_defined_blacklist'] == 'no') { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'leav'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Email addresses from the domains on this blacklist will be rejected (if active). <br/><strong>Use one domain per line</strong>.<br/><strong>Default: Yes</strong>', 'leav'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_blacklist" name="user_defined_blacklist" rows="15" cols="40"><?php echo $leav_options['user_defined_blacklist'] ?></textarea>
                            </label>
                            <p class="description">
                                <span id="user_defined_blacklist_line_count">0</span>
                                <?php _e('User-defined blacklisted email domains', 'leav') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Reject email adresses from LEAV\'s comprehensive and frequently updated list of disposable email services', 'leav') ?>:</th>
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
                                <?php _e('The listed domains are known services that provide disposable email addresses. Users that make use of these services might just want to protect their own privacy. But they might also be spammers. There is no good choice. In doubt we encourage you to value your lifetime and reject email addresses from these domains. We frequently update this list. For retrieving updates, click the update button below. For blocking domains of your own choosing, use the user-defined blacklist option above.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="disposable_email_service_domain_blacklist" name="disposable_email_service_domain_blacklist" rows="15" cols="40" readonly><?php echo $leav_options['disposable_email_service_domain_list'] ?></textarea>
                            </label>
                            <p class="description">
                                <span id="disposable_email_service_domain_blacklist_line_count">0</span>
                                <?php _e('Entries', 'leav') ?>
                            </p>
                            <p class="submit">
                                <input class="button button-primary" type="submit" id="disposable_email_service_domain_blacklist_restore" name="submit" value="<?php _e('Update list', 'leav') ?>" />
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Accept syntactically correct email addresses on connection errors', 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_syntactically_correct_email_addresses_when_connection_to_mx_failed" type="radio" value="yes" <?php if ($leav_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="accept_syntactically_correct_email_addresses_when_connection_to_mx_failed" type="radio" value="no"<?php if ($leav_options['accept_syntactically_correct_email_addresses_when_connection_to_mx_failed'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e("In order to thoroughly validate email addresses, LEAV tries to connect to at <br/>least one of the MX (Mail eXchange) servers in the email domain's DNS record. <br/>Usually there are ≥2 MX servers. When this option is set to 'Yes', <br/>the failure to connect to any of the MX servers will be ignored and <br/>syntactically correct email addresses will be accepted.<br/>We do not recommend this, since it will result in more spam.  <br/><strong>Default: No</strong>", 'leav') ?>.
                            </p>
                        </td>
                    </tr>


                </table>
                <h2><?php _e('Accepting pingbacks / trackbacks without validation', 'leav') ?></h2>
                <?php _e('Pingbacks and trackbacks can\'t be validated because they don\'t come with an email address, that could be run through our validator.</br>Therefore <strong>pingbacks and trackbacks pose a certain spam risk</strong>. They are also free marketing.<br/>By default we therefore accept them. But feel free to reject them.', 'leav') ?>
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
                <?php _e('LEAV - Last Email Address Validator is currently capable of validating the email<br/>addresses for the following WordPress features and plugins (if installed and activated): <br/><ol><li>WordPress user registration (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)</li><li>WordPress comments (<a href="/wp-admin/options-discussion.php" target="_blank">Settings -> Discussion)</li><li><a href="https://wordpress.org/plugins/woocommerce/" target="_blank"> WooCommerce (plugin)</a></li><li><a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7 (plugin)</a></li></ol></br>', 'leav') ?>

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e('1. Validate WordPress user registration', 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="yes" <?php if ($leav_options['validate_wp_standard_user_registration_email_addresses'] == 'yes') { echo 'checked="checked" '; } ?>/><?php _e('Yes', 'leav') ?></label>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="no" <?php if ($leav_options['validate_wp_standard_user_registration_email_addresses'] == 'no') { echo 'checked="checked" '; } ?>/><?php _e('No', 'leav') ?></label>
                            <p class="description">
                                <?php _e('This validates all registrants email address\'s that register through WordPress\'s standard user registration.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('2. Validate WordPress comments', 'leav') ?>:</th>
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
                                <?php _e('This validates all commentor email address\'s that comment through WordPress\'s standard comment functionality.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('3. Validate WooCommerce (plugin)', 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="validate_woocommerce_registration" type="radio" value="yes" <?php if ($leav_options['validate_woocommerce_registration'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_woocommerce_registration" type="radio" value="no" <?php if ($leav_options['validate_wp_comment_user_email_addresses'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e('Validate all WooCommerce email addresses during registration and checkout.<br/><strong>Default: Yes</strong>', 'leav') ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('4. Validate Contact Form 7 (plugin)', 'leav') ?>:</th>
                        <td>
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
                        </td>
                    </tr>


                </table>
                <?php if ($is_windows) { ?>
                    <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                        <tr>
                            <th scope="row"><?php echo_e('Default gateway IP', 'leav') ?>:</th>
                            <td>
                                <input name="default_gateway" type="text" id="default_gateway" value="<?php echo $leav_options['default_gateway'] ?>" maxlength="15" size="40" />
                                <br /><?php _e('Leave blank to use Windows default gateway', 'leav') ?>.
                            </td>
                        </tr>
                    </table>
                <?php } ?>


                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e('Save Changes', 'leav') ?>" />
                </p>
            </form>

            <?php _e('<h1>Feature Requests</h1>If you look for more plugins, we at <a href="https://smings.com" target="_blank">smings.com</a> (website will soon be online) are always happy to make<br/> LEAV - Last Email Address Validator better than it is and help you. Just shoot us an email to <br/><a href="mailto:leav-feature-requests@smings.com">leav-feature-requests@smings.com</a>.<br/><br/><h1>Help us help you!</h1>Lastly - if LEAV - Last Email Address Validator delivers substancial value to you, i.e. saving<br/> lots of your precious non-renewable lifetime, because it filters out tons of <br/>spam attempts, please show us your appreciation and consider a one-time donation', 'leav') ?>

            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                <input type="hidden" name="cmd" value="_s-xclick" />
                <input type="hidden" name="hosted_button_id" value="4Y6G6JJ7DH4FQ" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" title="PayPal - The safer, easier way to donate online!" alt="Donate with PayPal button" />
                <img alt="" border="0" src="https://www.paypal.com/en_DE/i/scr/pixel.gif" width="1" height="1" />
            </form>

            <?php _e('or become a patreon on our patreon page at', 'leav') ?>
                <strong>
                    <a href="https://www.patreon.com/smings" target="_blank">
            <?php _e('patreon.com/smings.', 'leav') ?>
                    </a>
                </strong>
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

// adding hooks and action(s)
register_activation_hook( __FILE__, 'leav_activate_plugin');
register_uninstall_hook( __FILE__, 'leav_uninstall_plugin');
add_action( 'init', 'leav_init' );

?>