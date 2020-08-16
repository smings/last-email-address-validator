<?php
/*
Plugin Name: Last Email Validator (LEV)
Plugin URI: https://github.com/smings/last-email-validator/
Description: LEV provides email address validation for WP's user registration,  WooCommerce's registrations and 'contact form 7' (cf7) forms.
Version: 1.0.0
Author: Dirk Tornow
Author URI: https://smings.de
Text Domain: lev
*/

// Example of how to get the current translation strings from the 
// WordPress plugin code. 
// `xgettext --default-domain=last-email-validator --language=PHP --keyword=__ --keyword=_e --sort-by-file --copyright-holder="Dirk Tornow" --package-name=last-email-validator --package-version=1.0.3 --from-code=UTF-8 --msgid-bugs-address="translastions@smings.com" -i *.php`

// Example of how to merge a newer version with an existing po file
// `msgmerge -i -o new_merged.po OLD_reference.po new_empty.po`

// Example of how to create an mo file
// `msgfmt -o output.mo input.po

// for debugging only
// if ( ! function_exists('write_log')) {
//    function write_log ( $log )  {
//       if ( is_array( $log ) || is_object( $log ) ) {
//          error_log( print_r( $log, true ) );
//       } else {
//          error_log( $log );
//       }
//    }
// }

require_once('includes/last-email-validator.inc.php');

// <-- i18n textdomain -->
load_plugin_textdomain('last-email-validator');

// <-- trash mail service blacklist -->
$disposable_email_service_domain_list_file = plugin_dir_path(__FILE__) . 'data/disposable_email_service_provider_domain_list.txt';

// <-- plugin options -->
$last_email_validator_options = array();

if (get_option('last_email_validator_options')) {
    $last_email_validator_options = get_option('last_email_validator_options');
}

if (empty($last_email_validator_options['spam_email_addresses_blocked_count'])) {
    $last_email_validator_options['spam_email_addresses_blocked_count'] = '0';
}

if (empty($last_email_validator_options['accept_email_address_when_connection_to_mx_failed'])) {
    $last_email_validator_options['accept_email_address_when_connection_to_mx_failed'] = 'no';
}

if (empty($last_email_validator_options['accept_email_address_when_simulated_sending_failed'])) {
    $last_email_validator_options['accept_email_address_when_simulated_sending_failed'] = 'no';
}

if (empty($last_email_validator_options['accept_correct_email_address_syntax_on_server_timeout'])) {
    $last_email_validator_options['accept_correct_email_address_syntax_on_server_timeout'] = 'no';
}

if (empty($last_email_validator_options['default_gateway'])) {
    $last_email_validator_options['default_gateway'] = '';
}

if (empty($last_email_validator_options['accept_pingbacks'])) {
    $last_email_validator_options['accept_pingbacks'] = 'yes';
}

if (empty($last_email_validator_options['accept_trackbacks'])) {
    $last_email_validator_options['accept_trackbacks'] = 'yes';
}

if (empty($last_email_validator_options['use_user_defined_blacklist'])) {
    $last_email_validator_options['use_user_defined_blacklist'] = 'yes';
}

if (empty($last_email_validator_options['user_defined_blacklist'])) {
    $last_email_validator_options['user_defined_blacklist'] = "your_blacklisted_domain1.here\nyour_blacklisted_domain2.here";
}

if (empty($last_email_validator_options['block_disposable_email_service_domains'])) {
    $last_email_validator_options['block_disposable_email_service_domains'] = 'yes';
}

if (empty($last_email_validator_options['disposable_email_service_domain_list'])) {
    $disposable_email_service_domains = file_exists($disposable_email_service_domain_list_file) ? file_get_contents($disposable_email_service_domain_list_file) : '';
    $last_email_validator_options['disposable_email_service_domain_list'] = $disposable_email_service_domains;
}

if (empty($last_email_validator_options['check_wp_standard_user_registrations'])) {
    $last_email_validator_options['check_wp_standard_user_registrations'] = 'yes';
}

// <-- os detection -->
$is_windows = strncasecmp(PHP_OS, 'WIN', 3) == 0 ? true : false;

// get windows compatibility
if (! function_exists('getmxrr')) {
    function getmxrr($hostName, &$mxHosts, &$mxPreference)
    {
        global $last_email_validator_options;
        
        $gateway = $last_email_validator_options['default_gateway'];
    
        $nsLookup = shell_exec("nslookup -q=mx {$hostName} {$gateway} 2>nul");
        preg_match_all("'^.*MX preference = (\d{1,10}), mail exchanger = (.*)$'simU", $nsLookup, $mxMatches);

        if (count($mxMatches[2]) > 0) {
            array_multisort($mxMatches[1], $mxMatches[2]);

            for ($i = 0; $i < count($mxMatches[2]); $i++) {
                $mxHosts[$i] = $mxMatches[2][$i];
                $mxPreference[$i] = $mxMatches[1][$i];
            }

            return true;
        } else {
            return false;
        }
    }
}

// <-- plugin functionality -->

function last_email_validator_init() {
    add_filter('pre_comment_approved', 'last_email_validator_validate_comment_email_addresses', 99, 2);
    add_filter('registration_errors', 'last_email_validator_validate_registration_email_addresses', 99, 3);

    # Filtering for WooCommerce, if it is installed and active
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_filter('woocommerce_registration_errors', 'last_email_validator_validate_registration_email_addresses', 10, 3);
    }

    # Filtering for contact form 7 - this doesn't work well yet tho. So I deactivate it
    if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
       add_filter('wpcf7_validate_email', 'last_email_validator_validate_cf7_email_addresses', 20, 2); // Email field
       add_filter('wpcf7_validate_email*', 'last_email_validator_validate_cf7_email_addresses', 20, 2); // Req. Email field
    }

    if (is_admin()) {
        add_action('admin_menu', 'last_email_validator_add_options_page');
        add_action('admin_enqueue_scripts', 'last_email_validator_enque_scripts');
    }
}

function last_email_validator_validate_comment_email_addresses($approved, $comment_data)
{
    // if comment is already marked as spam or trash
    // no further investigation is done
    if ($approved === 'spam' || $approved === 'trash') {
        return $approved;
    }

    global $user_ID;
    global $last_email_validator_options;
    
    // currently it is not possible to check trackbacks / pingbacks while there
    // is no 'comment_author_email' given in the trackback values
    
    // check if trackbacks should be left or dropped out
    if ((isset($comment_data['comment_type'])) && ($comment_data['comment_type'] == 'trackback')) {
        if ($last_email_validator_options['accept_trackbacks'] == 'yes') {
            return $approved;
        } else {
            return 'trash';
        }
    }
    
    // check if pingbacks should be left or dropped out
    if ((isset($comment_data['comment_type'])) && ($comment_data['comment_type'] == 'pingback')) {
        if ($last_email_validator_options['accept_pingbacks'] == 'yes') {
            return $approved;
        } else {
            return 'trash';
        }
    }
    
    // if it's a comment and not a logged in user - check mail
    if ((get_option('require_name_email')) && (!$user_ID)) {
        $email_address = $comment_data['comment_author_email'];
        return last_email_validator_validate_email_address($approved, $email_address);
    }

    return $approved;
}

function last_email_validator_validate_registration_email_addresses($errors, $sanitized_user_login, $user_email)
{
    global $last_email_validator_options;

    if ($last_email_validator_options['check_wp_standard_user_registrations'] == 'yes') {
        $approved = last_email_validator_validate_email_address('', $user_email);

        if ($approved === 'spam') {
            $errors->add('wp_mail-validator-registration-error', __( 'This email domain is not accepted/valid. Try another one.', 'last-email-validator'));
        }
    }

    return $errors;
}

function last_email_validator_validate_cf7_email_addresses($result, $tags) {
            
    $tags = new WPCF7_FormTag( $tags );

    $type = $tags->type;
    $name = $tags->name;

    if ('email' == $type || 'email*' == $type) { // Only apply to fields with the form field name of "company-email"
      
        $user_email = sanitize_email($_POST[$name]);
        $approved = last_email_validator_validate_email_address('', $user_email);
      
        if ($approved === 'spam') {
            $result->invalidate( $tags, __( 'The e-mail address entered is invalid.', 'contact-form-7' ));
        }
    }
    return $result;
}


function last_email_validator_validate_email_address($approved, $email_address)
{
    global $last_email_validator_options;

    // check mail-address against user defined blacklist (if enabled)
    if ($last_email_validator_options['use_user_defined_blacklist'] == 'yes') {
        $regexps = preg_split('/[\r\n]+/', $last_email_validator_options['user_defined_blacklist'], -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($regexps as $regexp) {
            if (preg_match('/' . $regexp . '/', $email_address)) {
                last_email_validator_block_email_address();
                return 'spam';
            }
        }
    }

    // check mail-address against trashmail services (if enabled)
    if ($last_email_validator_options['block_disposable_email_service_domains'] == 'yes') {
        $regexps = preg_split('/[\r\n]+/', $last_email_validator_options['disposable_email_service_domain_list'], -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($regexps as $regexp) {
            if (preg_match('/' . $regexp . '/', $email_address)) {
                last_email_validator_block_email_address();
                return 'spam';
            }
        }
    }

    $mail_validator = new LEVemailValidator();
    $return_code = $mail_validator->validateEmailAddress($email_address);

    switch ($return_code) {
        case UNKNOWN_SERVER:
        case INVALID_MAIL:
        case SYNTAX_INCORRECT:
            last_email_validator_block_email_address();
            return 'spam';
        case CONNECTION_FAILED:
            // timeout while connecting to mail-server
            if ($last_email_validator_options['accept_email_address_when_connection_to_mx_failed'] == 'no') {
                last_email_validator_block_email_address();
                return 'spam';
            }
            return $approved;
        case REQUEST_REJECTED:
            // host could be identified - but he rejected any request
            if ($last_email_validator_options['accept_email_address_when_simulated_sending_failed'] == 'no') {
                last_email_validator_block_email_address();
                return 'spam';
            }
            return $approved;
        case VALID_MAIL:
            // host could be identified and he accepted and he approved
            // the mail address
            return $approved;
        case SYNTAX_CORRECT:
            // mail address syntax correct - but the host server
            // did not repsonse in time
            if ($last_email_validator_options['accept_correct_email_address_syntax_on_server_timeout'] != 'yes') {
                last_email_validator_block_email_address();
                return 'spam';
            }
            return $approved;
        default:
            return $approved;
    }
}

// <-- database update function -->

function last_email_validator_block_email_address()
{
    global $last_email_validator_options;

    $last_email_validator_options['spam_email_addresses_blocked_count'] = ($last_email_validator_options['spam_email_addresses_blocked_count'] + 1);
    update_option('last_email_validator_options', $last_email_validator_options);
}

// <-- theme functions / statistics -->

function last_email_validator_powered_by_label($string_before = "", $string_after = "")
{
    $label = $string_before . __('Anti spam protected by', 'last-email-validator') . ': <a href="https://github.com/smings/last-email-validator" title="Last Email Validator (LEV)" target="_blank">Last Email Validator (LEV)</a> - <strong>%s</strong> ' . __('spam email addresses stopped', 'last-email-validator') . '!' . $string_after;
    return sprintf($label, last_email_validator_get_blocked_email_address_count());
}

function last_email_validator_get_blocked_email_address_count()
{
    global $last_email_validator_options;
    return $last_email_validator_options['spam_email_addresses_blocked_count'];
}

function last_email_validator_version()
{
    $plugin = get_plugin_data( __FILE__ );
    return $plugin['Version'];
}

// <-- admin menu option page -->

function last_email_validator_add_options_page()
{
    add_options_page('LEV - Last Email Validator', 'LEV - Last Email Validator', 'edit_pages', basename(__FILE__, ".php"), 'last_email_validator_options_page');
}

function last_email_validator_options_page()
{
    global $last_email_validator_options;
    global $is_windows;
    global $disposable_email_service_domain_list_file;

    if (isset($_POST['last_email_validator_options_update_type'])) {
        $wp_mail_validator_updated_options = $last_email_validator_options;
        $update_notice = '';

        // write_log("last_email_validator_options_update_type = '" . $_POST['last_email_validator_options_update_type'] . "'");
        if ($_POST['last_email_validator_options_update_type'] === 'update') {
            // write_log( "We are in the first if in line 338");
            foreach ($_POST as $key => $value) {
                // write_log("key=value => $key=$value");
                if ($key !== 'last_email_validator_options_update_type' && $key !== 'submit') {
                    $wp_mail_validator_updated_options[$key] = $value;
                }
            }
            $update_notice = __('Last Email Validator (LEV) options updated', 'last-email-validator');
        } 
        elseif ($_POST['last_email_validator_options_update_type'] === 'restore_disposable_email_service_domain_blacklist') {
            // write_log( "We are in the elsif part in line 349");
            $wp_mail_validator_updated_options['disposable_email_service_domain_list'] = file_get_contents($disposable_email_service_domain_list_file);
            $update_notice = __('Last Email Validator (LEV) disposable email services domain blacklist restored', 'last-email-validator');
        }
        else {
            // write_log("nothing matched");
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
            <h1><?php echo __('Last Email Validator (LEV) Settings', 'last-email-validator') ?></h1>
            <form name="wp_mail_validator_options" method="post">
                <input type="hidden" name="last_email_validator_options_update_type" value="update" />
                <h2><?php echo __('Control the level of spam protection. Defaults are set to the highest level.', 'last-email-validator') ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
    <?php
    if ($is_windows) {
    ?>
                    <tr>
                        <th scope="row"><?php echo__('Default gateway IP', 'last-email-validator') ?>:</th>
                        <td>
                            <input name="default_gateway" type="text" id="default_gateway" value="<?php echo $last_email_validator_options['default_gateway'] ?>" maxlength="15" size="40" />
                            <br /><?php echo __('Leave blank to use Windows default gateway', 'last-email-validator') ?>.
                        </td>
                    </tr>
    <?php
    }
    ?>
                    <tr>
                        <th scope="row"><?php echo __('Accept email addresses despite failure to connnect to ALL of their MX servers', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_email_address_when_connection_to_mx_failed" type="radio" value="yes" <?php if ($last_email_validator_options['accept_email_address_when_connection_to_mx_failed'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="accept_email_address_when_connection_to_mx_failed" type="radio" value="no"<?php if ($last_email_validator_options['accept_email_address_when_connection_to_mx_failed'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('Do you want to ignore connection failures to the email address\'s mail exchange servers?  Servers can always be down for maintenance. But since every serious mail service has fallback servers, and we test all servers, we strongly recommend to not ignore this. For strongest protection, don\'t ignore connection failures. <br/><strong>Default: No</strong>', 'last-email-validator') ?>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Accept syntactically correct email address when connecting to ALL its mail servers failed (due to timeouts)', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_correct_email_address_syntax_on_server_timeout" type="radio" value="yes" <?php if ($last_email_validator_options['accept_correct_email_address_syntax_on_server_timeout'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="accept_correct_email_address_syntax_on_server_timeout" type="radio" value="no" <?php if ($last_email_validator_options['accept_correct_email_address_syntax_on_server_timeout'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('If set to \'Yes\' syntactically correct mail-addresses will be accepted despite ALL mail server did not respond (in time). This will result in more spam. We recommend to set it to \'No\' for strongest spam protection.<br/><strong>Default: No</strong>', 'last-email-validator') ?>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Accept email address despite failed simulated sending of email', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_email_address_when_simulated_sending_failed" type="radio" value="yes" <?php if ($last_email_validator_options['accept_email_address_when_simulated_sending_failed'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="accept_email_address_when_simulated_sending_failed" type="radio" value="no" <?php if ($last_email_validator_options['accept_email_address_when_simulated_sending_failed'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('We simulate the sending of an email from this wordpress instances\'s domain to the entered email address. If the receiving mail server rejects the email address, this is the strongest indicator, that the email address doesn\'t exist (is spam). When ignoring this rejection you will receive a lot more spam. We strongly recommmend to not accept email addresses when the simulated sending failed.<br/> <strong>Default: No</strong>', 'last-email-validator') ?>.
                            </p>
                       </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo __('Reject email adresses from our comprehensive and frequently updated list of disposable email services', 'last-email-validator') ?>:</th>
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
                                <?php echo __('The listed domains are services that provide single use email addresses (disposable email addresses). Users that make use of these services might just want to protect their own privacy. Users might also be spammers. There is no good choice. In doubt we encourage you to value your own time and reject email addresses from these domains. You can click the button below to update the list from our server. This will overwrite all existing values. Therefore we discourage you to add/edit this list. For blocking domains of your choosing, use the blacklist option below.<br/>  <strong>Default: Yes</strong>', 'last-email-validator') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="disposable_email_service_domain_blacklist" name="disposable_email_service_domain_blacklist" rows="15" cols="40"><?php echo $last_email_validator_options['disposable_email_service_domain_list'] ?></textarea>
                            </label>
                            <p class="description">
                                <span id="disposable_email_service_domain_blacklist_line_count">0</span>
                                <?php echo __('Entries', 'last-email-validator') ?>
                            </p>
                            <p class="submit">
                                <input class="button button-primary" type="submit" id="disposable_email_service_domain_blacklist_restore" name="submit" value="<?php echo __('Update / Restore list of dispoable email service domains', 'last-email-validator') ?>" />
                            </p>
                        </td>
                    </tr>
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
                                <?php echo __('Email addresses from the domains on this list will be rejected. Place any domain that you started to receive spam mails from on this list. <strong>Use one domain per line</strong>.  <br/><strong>Default: Yes</strong>', 'last-email-validator') ?>
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
                </table>
                <h2><?php echo __('Accepting pingbacks / trackbacks without validation', 'last-email-validator') ?></h2>
                <?php echo __('Pingbacks and trackbacks can\'t be validated because they don\'t come with an email address, that could be run through our validator. Therefore <strong>pingbacks and trackbacks pose a certain spam risk</strong>. They are also free marketing. By default we therefore accept them. You can always choose to reject them.', 'last-email-validator') ?>
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
                <h2><?php echo __('Validate WordPress standard user registration', 'last-email-validator') ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php echo __('Validate user-registrations', 'last-email-validator') ?>:</th>
                        <td>
                            <label>
                                <input name="check_registrations" type="radio" value="yes" <?php if ($last_email_validator_options['check_wp_standard_user_registrations'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', 'last-email-validator') ?>
                            </label>
                            <label>
                                <input name="check_registrations" type="radio" value="no" <?php if ($last_email_validator_options['check_wp_standard_user_registrations'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', 'last-email-validator') ?>
                            </label>
                            <p class="description">
                                <?php echo __('This validates all registrants email address\'s that register through WordPress\'s standard user registration.<br/><strong>Default: Yes</strong>', 'last-email-validator') ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php echo __('Save Changes', 'last-email-validator') ?>" />
                </p>
            </form>
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
    // write_log("Hook = '" . $hook . "'");
    if ('settings_page_last-email-validator' != $hook) {
        return;
    }

    wp_enqueue_script('jquery.mask', plugin_dir_url(__FILE__) . 'scripts/jquery.mask.min.js', array(), '1.14.15');
    wp_enqueue_script('lev', plugin_dir_url(__FILE__) . 'scripts/lev.min.js', array(), '1.0.0');
}

// <-- plugin installation on activation -->

function last_email_validator_install()
{
    global $wpdb;
    global $last_email_validator_options;

    // migration of existing data in older versions
    $table_name = $wpdb->prefix . "last_email_validator";

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $sql = "SELECT eaten FROM " . $table_name . " LIMIT 1;";
        $count = $wpdb->get_var($sql);

        $last_email_validator_options['spam_email_addresses_blocked_count'] = $count;
        update_option('lasr_email_validator_options', $last_email_validator_options);

        $sql = "DROP TABLE IF EXISTS " . $table_name . ";";
        $wpdb->query($sql);
    }
}

// <-- hooks -->
register_activation_hook( __FILE__, 'last_email_validator_install');
add_action( 'init', 'last_email_validator_init' );
?>