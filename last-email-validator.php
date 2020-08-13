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
$text_domain = 'lev';
load_plugin_textdomain($text_domain);

// <-- trash mail service blacklist -->
$disposable_email_service_domain_list_file = plugin_dir_path(__FILE__) . 'data/disposable_email_service_provider_domain_list.txt';

// <-- plugin options -->
$last_email_validator_options = array();

if (get_option('last_email_validator_options')) {
    $last_email_validator_options = get_option('last_email_validator_options');
}

if (empty($last_email_validator_options['eaten_spam'])) {
    $last_email_validator_options['eaten_spam'] = '0';
}

if (empty($last_email_validator_options['ignore_failed_connection'])) {
    $last_email_validator_options['ignore_failed_connection'] = 'no';
}

if (empty($last_email_validator_options['ignore_request_rejected'])) {
    $last_email_validator_options['ignore_request_rejected'] = 'no';
}

if (empty($last_email_validator_options['accept_correct_syntax_on_server_timeout'])) {
    $last_email_validator_options['accept_correct_syntax_on_server_timeout'] = 'no';
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
    $last_email_validator_options['user_defined_blacklist'] = 'your_blacklisted_domains.here';
}

if (empty($last_email_validator_options['block_disposable_email_service_domains'])) {
    $last_email_validator_options['block_disposable_email_service_domains'] = 'yes';
}

if (empty($last_email_validator_options['disposable_email_service_domain_list'])) {
    $disposable_email_service_domains = file_exists($disposable_email_service_domain_list_file) ? file_get_contents($disposable_email_service_domain_list_file) : '';
    $last_email_validator_options['disposable_email_service_domain_list'] = $disposable_email_service_domains;
}

if (empty($last_email_validator_options['check_registrations'])) {
    $last_email_validator_options['check_registrations'] = 'yes';
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
    global $text_domain;
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
    global $text_domain;
    global $last_email_validator_options;

    if ($last_email_validator_options['check_registrations'] == 'yes') {
        $approved = last_email_validator_validate_email_address('', $user_email);

        if ($approved === 'spam') {
            $errors->add('wp_mail-validator-registration-error', __( 'Your mail-address is evaluated as spam.', $text_domain));
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
            $result->invalidate( $tags, __( 'Die eingegebene Emailaddresse ist ungültig.', 'contact-form-7-email-validation' ));
        }
    }
    return $result;
}


function last_email_validator_validate_email_address($approved, $email_address)
{
    global $last_email_validator_options;
    global $text_domain;

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
            if ($last_email_validator_options['ignore_failed_connection'] == 'no') {
                last_email_validator_block_email_address();
                return 'spam';
            }
            return $approved;
        case REQUEST_REJECTED:
            // host could be identified - but he rejected any request
            if ($last_email_validator_options['ignore_request_rejected'] == 'no') {
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
            if ($last_email_validator_options['accept_correct_syntax_on_server_timeout'] != 'yes') {
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

    $last_email_validator_options['eaten_spam'] = ($last_email_validator_options['eaten_spam'] + 1);
    update_option('last_email_validator_options', $last_email_validator_options);
}

// <-- theme functions / statistics -->

function last_email_validator_powered_by_label($string_before = "", $string_after = "")
{
    global $text_domain;

    $label = $string_before . __('Protected by', $text_domain) . ': <a href="https://github.com/smings/last-email-validator" title="Last Email Validator (LEV)" target="_blank">Last Email Validator (LEV)r</a> - <strong>%s</strong> ' . __('spam attacks fended', $text_domain) . '!' . $string_after;
    return sprintf($label, last_email_validator_get_blocked_email_address_count());
}

function last_email_validator_get_blocked_email_address_count()
{
    global $last_email_validator_options;
    return $last_email_validator_options['eaten_spam'];
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
    global $text_domain;
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
            $update_notice = __('WP-Mail-Validator options updated', $text_domain);
        } 
        elseif ($_POST['last_email_validator_options_update_type'] === 'restore_trashmail_blacklist') {
            // write_log( "We are in the elsif part in line 349");
            $wp_mail_validator_updated_options['disposable_email_service_domain_list'] = file_get_contents($disposable_email_service_domain_list_file);
            $update_notice = __('WP-Mail-Validator trashmail service blacklist restored', $text_domain);
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
                <span class="screen-reader-text"><?php echo __('Dismiss this notice', $text_domain) ?>.</span>
            </button>
        </div>
    <?php
    }
    ?>
        <div class="wrap">
            <h1><?php echo __('Settings', $text_domain) ?></h1>
            <form name="wp_mail_validator_options" method="post">
                <input type="hidden" name="last_email_validator_options_update_type" value="update" />
                <h2><?php echo __('Comment validation', $text_domain) ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
    <?php
    if ($is_windows) {
    ?>
                    <tr>
                        <th scope="row"><?php echo__('Default-gateway IP', $text_domain) ?>:</th>
                        <td>
                            <input name="default_gateway" type="text" id="default_gateway" value="<?php echo $last_email_validator_options['default_gateway'] ?>" maxlength="15" size="40" />
                            <br /><?php echo __('Leave blank to use the default gateway', $text_domain) ?>.
                        </td>
                    </tr>
    <?php
    }
    ?>
                    <tr>
                        <th scope="row"><?php echo __('Accept on connection failures', $text_domain) ?>:</th>
                        <td>
                            <label>
                                <input name="ignore_failed_connection" type="radio" value="yes" <?php if ($last_email_validator_options['ignore_failed_connection'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', $text_domain) ?>
                            </label>
                            <label>
                                <input name="ignore_failed_connection" type="radio" value="no"<?php if ($last_email_validator_options['ignore_failed_connection'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', $text_domain) ?>
                            </label>
                            <p class="description">
                                <?php echo __('Choose to ignore connection failures with mail servers while validating mail-addresses', $text_domain) ?>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Accept on rejected requests', $text_domain) ?>:</th>
                        <td>
                            <label>
                                <input name="ignore_request_rejected" type="radio" value="yes" <?php if ($last_email_validator_options['ignore_request_rejected'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', $text_domain) ?>
                            </label>
                            <label>
                                <input name="ignore_request_rejected" type="radio" value="no" <?php if ($last_email_validator_options['ignore_request_rejected'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', $text_domain) ?>
                            </label>
                            <p class="description">
                                <?php echo __('Choose to ignore rejected request from mail servers while validating mail-addresses', $text_domain) ?>.
                            </p>
                       </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Accept syntactically correct mail-addresses', $text_domain) ?>:</th>
                        <td>
                            <label>
                                <input name="accept_correct_syntax_on_server_timeout" type="radio" value="yes" <?php if ($last_email_validator_options['accept_correct_syntax_on_server_timeout'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', $text_domain) ?>
                            </label>
                            <label>
                                <input name="accept_correct_syntax_on_server_timeout" type="radio" value="no" <?php if ($last_email_validator_options['accept_correct_syntax_on_server_timeout'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', $text_domain) ?>
                            </label>
                            <p class="description">
                                <?php echo __('Choose if syntactically correct mail-addresses can pass when the mail server did not respond in time', $text_domain) ?>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Reject mail-adresses from trashmail services', $text_domain) ?>:</th>
                        <td>
                            <label>
                                <input name="use_trashmail_service_blacklist" type="radio" value="yes" <?php if ($last_email_validator_options['block_disposable_email_service_domains'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', $text_domain) ?>
                            </label>
                            <label>
                                <input name="use_trashmail_service_blacklist" type="radio" value="no" <?php if ($last_email_validator_options['block_disposable_email_service_domains'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', $text_domain) ?>
                            </label>
                            <p class="description">
                                <?php echo __('Choose to reject mail-addresses from trashmail services <strong>(single entry per line)</strong>', $text_domain) ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="trashmail_service_blacklist" name="trashmail_service_blacklist" rows="15" cols="40"><?php echo $last_email_validator_options['disposable_email_service_domain_list'] ?></textarea>
                            </label>
                            <p class="description">
                                <span id="trashmail_service_blacklist_line_count">0</span>
                                <?php echo __('Entries', $text_domain) ?>
                            </p>
                            <p class="submit">
                                <input class="button button-primary" type="submit" id="trashmail_service_blacklist_restore" name="submit" value="<?php echo __('Restore default blacklist', $text_domain) ?>" />
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Reject mail-adresses from user-defined blacklist', $text_domain) ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_blacklist" type="radio" value="yes" <?php if ($last_email_validator_options['use_user_defined_blacklist'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', $text_domain) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_blacklist" type="radio" value="no" <?php if ($last_email_validator_options['use_user_defined_blacklist'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', $text_domain) ?>
                            </label>
                            <p class="description">
                                <?php echo __('Choose to reject mail-addresses from a user-defined blacklist <strong>(single entry per line)</strong>', $text_domain) ?>
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
                                <?php echo __('Entries', $text_domain) ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <h2><?php echo __('Ping validation', $text_domain) ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php echo __('Validate pingbacks', $text_domain) ?>:</th>
                        <td>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="yes" <?php if ($last_email_validator_options['accept_pingbacks'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', $text_domain) ?>
                            </label>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="no" <?php if ($last_email_validator_options['accept_pingbacks'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', $text_domain) ?>
                            </label>
                            <p class="description">
                                <?php echo __('Choose to accept Pingbacks <strong>(Pingbacks might be a security risk, because they\'re not carrying a mail-address to validate)</strong>', $text_domain) ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Validate trackbacks', $text_domain) ?>:</th>
                        <td>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="yes" <?php if ($last_email_validator_options['accept_trackbacks'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', $text_domain) ?>
                            </label>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="no" <?php if ($last_email_validator_options['accept_trackbacks'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', $text_domain) ?>
                            </label>
                            <p class="description">
                                <?php echo __('Choose to accept Trackbacks <strong>(Trackbacks might be a security risk, because they\'re not carrying a mail-address to validate)</strong>', $text_domain) ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <h2><?php echo __('Registrants validation', $text_domain) ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php echo __('Validate user-registrations', $text_domain) ?>:</th>
                        <td>
                            <label>
                                <input name="check_registrations" type="radio" value="yes" <?php if ($last_email_validator_options['check_registrations'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('Yes', $text_domain) ?>
                            </label>
                            <label>
                                <input name="check_registrations" type="radio" value="no" <?php if ($last_email_validator_options['check_registrations'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php echo __('No', $text_domain) ?>
                            </label>
                            <p class="description">
                                <?php echo __('Choose to validate registrants mail-address', $text_domain) ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php echo __('Save Changes', $text_domain) ?>" />
                </p>
            </form>
        </div>
        <div class="wrap">
            <h1><?php echo __('Statistics', $text_domain) ?></h1>
            <div class="card">
                <p>
                    <?php echo sprintf(__('Version', $text_domain) . ': <strong>%s</strong>', last_email_validator_version()) ?>&nbsp;|
                    <?php echo sprintf(__('Spam attacks fended', $text_domain) . ': <strong>%s</strong>', last_email_validator_get_blocked_email_address_count()) ?>
                </p>
                <p>
                    <a href="https://github.com/smings/last-email-validator/wiki"><?php echo __('Documentation', $text_domain) ?></a>&nbsp;|
                    <a href="https://github.com/smings/last-email-validator/issues"><?php echo __('Bugs', $text_domain) ?></a>
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

        $last_email_validator_options['eaten_spam'] = $count;
        update_option('lasr_email_validator_options', $last_email_validator_options);

        $sql = "DROP TABLE IF EXISTS " . $table_name . ";";
        $wpdb->query($sql);
    }
}

// <-- hooks -->
register_activation_hook( __FILE__, 'last_email_validator_install');
add_action( 'init', 'last_email_validator_init' );
?>