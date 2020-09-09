<?php

defined('ABSPATH') or die("Nice try! Go away!");


class LeavSettingsPage
{

    private static $plugin_display_name_long = "LEAV - Last Email Address Validator";
    private static $plugin_display_name_short = "LEAV";
    private static $plugin_website = "https://smings.com/last-email-address-validator/";

    private static $EMAIL_ADDRESS_REGEX = "/^[0-9a-z_]([-_\.]*[0-9a-z])*\+?[0-9a-z]*([-_\.]*[0-9a-z])*@[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\\.[a-z]{2,18}$/i";
    
    private static $DOMAIN_REGEX = "/^[0-9a-z]([-\._]*[0-9a-z])*[0-9a-z]\\.[a-z]{2,18}$/i";
    private static $SANITIZE_DOMAIN_REGEX = "/[^0-9a-z-\._]/";
    private static $COMMENT_LINE_REGEX = "/^\s*(#|\/\/)/";
    private static $EMPTY_LINE_REGEX = "/^\s*[\r\n]+$/";
   

    private $debug = true;
    private $options = array();
    private $options_name = '';
    private $plugin_version;
    private $update_notice = '';
    private $error_notice = '';

    public function __construct(string $options_name, array &$options)
    {
        $this->options_name = $options_name;
        $this->options = $options;
        // $plugin = get_plugin_data( __FILE__ );
        $this->plugin_version = get_plugin_data( __FILE__ )["Version"];
        // $this->plugin_version = $plugin["Version"];
    }


    public function set_debug(boolean $state)
    {
        $this->debug = $state;
    }


    public function add_settings_page_to_menu()
    {
        add_options_page( "LEAV - Last Email Address Validator", "LEAV - Last Email Address Validator", "edit_pages", basename(__FILE__, ".php"), array( $this, 'display_settings_page') );
    }


    public function display_settings_page()
    {

        if (isset($_POST["leav_options_update_type"]))
        {
            $this->sanitize_submitted_settings_form_data();
            if( ! empty( $this->update_notice ) && empty( $this->error_notice ) )
            {
?>
        <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
            <p>
                <strong><?php echo $this->update_notice ?>.</strong>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e("Dismiss this notice", "leav") ?>.</span>
            </button>
        </div>
<?php
            }
            elseif( ! empty( $this->error_notice ) )
            {
?>
        <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
            <p>
                <strong><?php echo $this->error_notice ?>.</strong>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e("Dismiss this notice", "leav") ?>.</span>
            </button>
        </div>

<?php
            }                
        }
?>
        <div class="wrap">
            <h1><?php _e("Settings for <strong>", "leav"); _e( self::$plugin_display_name_long ); ?></strong> by <i>
                <a <?php _e( 'href="'. self::$plugin_website .  '"' );?> target="_blank">smings</a></i></h1>
            <?php _e("LEAV - Last Email Address Validator <i>by smings</i> validates email addresses of the supported WordPress functions and plugins in the following multi-step process" , "leav"); ?>


            <ol>
                <li>
                    <?php _e("Check if the email address is syntactically correct. This acts as a backup check for the plugins. Some plugins only have a frontend based email syntax check. This is a proper server-side check (always)", "leav"); ?>
                </li>
                <li>
                    <?php _e("Filter against user-defined email domain whitelist. (if activated)<br/>If you need to override false positives, you can use this option. We would kindly ask you to <a href=\"mailto:leav-bugs@smings.com\">inform us</a> about wrongfully blacklisted domains though, so that we can correct this." , "leav"); ?>
                </li>
                <li>
                    <?php _e("Filter against user-defined email whitelist (if activated)<br/>If you need to override one or multiple specific email addresses that would otherwise get filtered out." , "leav"); ?>
                </li>
                <li>
                    <?php _e("Filter against user-defined email domain blacklist (if activated)" , "leav"); ?>
                </li>
                <li>
                    <?php _e("Filter against user-defined email blacklist (if activated)" , "leav"); ?>
                </li>
                <li>
                    <?php _e("Filter against LEAV's built-in extensive blacklist of disposable email service domains and known spammers (always)", "leav"); ?>
                </li>
                <li>
                    <?php _e("Check if the email address's domain has a DNS entry with MX records (always)", "leav"); ?>
                </li>

                <li>
                    <?php _e("Filter against LEAV's built-in extensive blacklist of MX (MX = Mail eXchange) server domains and IP addresses for disposable email services (if activated)", "leav"); ?>
                </li>
                <li>
                    <?php 
                        _e("Connect to one of the MX servers and simulate the sending of an email from <strong>no-reply@", "leav"); 
                        echo ( $this->options["wp_email_domain"] ); 
                        _e("</strong> to the entered email address (always)", "leav" ); ?>
                </li>
            </ol>
<?php 
_e("Below you can control in which way the selected WordPress functions and plugins will validate entered email adresses." , "leav");
?><br/><br/>
            <form name="wp_mail_validator_options" method="post">
                <input type="hidden" name="leav_options_update_type" value="update" />

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e("Email domain for simulating sending of emails to entered email addresses", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="wp_email_domain" type="text" size="40" value="<?php echo ( $this->options["wp_email_domain"]); ?>" required="required" minlength="5" pattern="^([A-Za-z0-9]+\.)*[A-Za-z0-9][A-Za-z0-9]+\.[A-Za-z]{2,18}$"/>
                            </label>
                            <p class="description">
                                <?php _e("This Email domain is used for simulating the sending of an email from ", "leav"); echo("no-reply@<strong>" . $this->options["wp_email_domain"] ); _e("</strong> to the entered email address, that gets validated.<br/><strong>Please make sure you use the email domain that you use for sending emails from your WordPress instance</strong>") ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Allow email adresses from user-defined domain whitelist", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="yes" <?php if ($this->options["use_user_defined_domain_whitelist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="no" <?php if ($this->options["use_user_defined_domain_whitelist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e("No", "leav"); ?>
                            </label>
                            <p class="description">
                                <?php _e("Email addresses from the listed domains will be accepted without further checks (if active). <br/><strong>Use one domain per line</strong>.<br/><strong>Default: Yes</strong>", "leav"); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_whitelist" name="user_defined_domain_whitelist" rows="7" cols="40" placeholder="your-whitelisted-domain-1.com
your-whitelisted-domain-2.com"><?php echo ($this->options["user_defined_domain_whitelist"]); ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Allow email adresses from user-defined email whitelist", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="yes" <?php if ($this->options["use_user_defined_email_whitelist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="no" <?php if ($this->options["use_user_defined_email_whitelist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e("No", "leav"); ?>
                            </label>
                            <p class="description">
                                <?php _e("Email addresses on this list will be accepted without further checks (if active). <br/><strong>Use one email address per line</strong>.<br/><strong>Default: Yes</strong>", "leav"); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_whitelist" name="user_defined_email_whitelist" rows="7" cols="40" placeholder="your.whitelisted@email-1.com
    your.whitelisted@email-2.com"><?php echo $this->options["user_defined_email_whitelist"] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Reject email adresses from user-defined domain blacklist", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="yes" <?php if ($this->options["use_user_defined_domain_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="no" <?php if ($this->options["use_user_defined_domain_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e("No", "leav"); ?>
                            </label>
                            <p class="description">
                                <?php _e("Email addresses from these domains will be rejected (if active). <br/><strong>Use one domain per line</strong>.<br/><strong>Default: Yes</strong>", "leav"); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_blacklist" name="user_defined_domain_blacklist" rows="7" cols="40" placeholder="your-blacklisted-domain-1.com
your-blacklisted-domain-2.com"><?php echo $this->options["user_defined_domain_blacklist"] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Reject email adresses from user-defined email blacklist", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="yes" <?php if ($this->options["use_user_defined_email_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="no" <?php if ($this->options["use_user_defined_email_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e("No", "leav"); ?>
                            </label>
                            <p class="description">
                                <?php _e("Email addresses from this list will be rejected (if active). <br/><strong>Use one email address per line</strong>.<br/><strong>Default: Yes</strong>", "leav"); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_blacklist" name="user_defined_email_blacklist" rows="7" cols="40" placeholder="your-blacklisted-domain-1.com
your-blacklisted-domain-2.com"><?php echo $this->options["user_defined_email_blacklist"] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Reject email adresses from disposable email address services (DEA)", "leav") ?>:</th>
                        <td>
                            <label>
                                <input name="block_disposable_email_address_services" type="radio" value="yes" <?php if ($this->options["block_disposable_email_address_services"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="block_disposable_email_address_services" type="radio" value="no" <?php if ($this->options["block_disposable_email_address_services"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", "leav") ?>
                            </label>
                            <p class="description">
                                <?php 
                                    _e("If activated email adresses from disposable email address services (DEA) i.e. mailinator.com will be rejected. LEAV manages a comprehensive list of DEA services that is frequently updated. We block the underlying MX server IPs and not just the domains. This bulletproofs the validation against domain aliases and makes it extremely reliable, since it attacks DEAs at their core. If you found a DEA service that doesn't get blocked yet, please contact us at <a href=\"mailto:leav@smings.com\">leav@smings.com</a>.<br/><strong>Default: Yes</strong>", "leav"); ?>
                            </p>
                        </td>
                    </tr>

                </table>
                <h2><?php _e("Accepting pingbacks / trackbacks", "leav") ?></h2>
                <?php _e("Pingbacks and trackbacks can\'t be validated because they don\'t come with an email address, that could be run through our validation process.</br>Therefore <strong>pingbacks and trackbacks pose a certain spam risk</strong>. They could also be free marketing.<br/>By default we therefore accept them. But feel free to reject them.", "leav") ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e("Accept pingbacks", "leav") ?>:</th>
                        <td>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="yes" <?php if ($this->options["accept_pingbacks"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="no" <?php if ($this->options["accept_pingbacks"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", "leav") ?>
                            </label>
                            <p class="description">
                                <strong><?php _e("Default:", "leav") ?> <?php _e("Yes", "leav") ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e("Accept trackbacks", "leav") ?>:</th>
                        <td>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="yes" <?php if ($this->options["accept_trackbacks"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="no" <?php if ($this->options["accept_trackbacks"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", "leav") ?>
                            </label>
                            <p class="description">
                                <strong><?php _e("Default:", "leav") ?> <?php _e("Yes", "leav") ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>
                <h2><?php _e("Control which of WordPress's functions and plugins should be email validated by LEAV", "leav") ?></h2>
                <?php _e('LEAV - Last Email Address Validator is currently capable of validating the email<br/>addresses for the following WordPress features and plugins (if installed and activated): <br/><ol><li>WordPress user registration (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)</li><li>WordPress comments (<a href="/wp-admin/options-discussion.php" target="_blank">Settings -> Discussion)</li><li><a href="https://wordpress.org/plugins/woocommerce/" target="_blank"> WooCommerce (plugin)</a></li><li><a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7 (plugin)</a></li><li><a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">WPForms (lite and pro) (plugin)</a></li><li><a href="https://wordpress.org/plugins/ninja-forms/" target="_blank">Ninja Forms (plugin)</a></li></ol></br>', "leav") ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e("Control which functions and plugins to validate with LEAV", "leav") ?>:</th>
                        <td/>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e("WordPress user registration", "leav") ?>:</th>
                        <td>
                            <?php if( get_option("users_can_register") == 1 && $this->options["validate_wp_standard_user_registration_email_addresses"] == "yes" ) : ?>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="yes" <?php if ($this->options["validate_wp_standard_user_registration_email_addresses"] == "yes") { echo 'checked="checked" '; } ?>/><?php _e("Yes", "leav") ?></label>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="no" <?php if ($this->options["validate_wp_standard_user_registration_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/><?php _e("No", "leav") ?></label>
                            <p class="description">
                                <?php _e("This validates all registrants email address's that register through WordPress's standard user registration.<br/><strong>Default: Yes</strong>", "leav") ?>
                            </p>
                            <?php endif; 
                                  if( get_option("users_can_register") == 0 || $this->options["validate_wp_standard_user_registration_email_addresses"] == "no" )
                                  {
                                      _e('WordPress\'s built-in user registration is currently deactivated (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)', "leav");
                                  }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e("WordPress comments", "leav") ?>:</th>
                        <td>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="yes" <?php if ($this->options["validate_wp_comment_user_email_addresses"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="no" <?php if ($this->options["validate_wp_comment_user_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", "leav") ?>
                            </label>
                            <p class="description">
                                <?php _e("This validates all (not logged in) commentator's email address's that comment through WordPress's standard comment functionality.<br/><strong>Default: Yes</strong>", "leav") ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("WooCommerce", "leav") ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "woocommerce/woocommerce.php" ) ) : ?>
                            <label>
                                <input name="validate_woocommerce_email_fields" type="radio" value="yes" <?php if ($this->options["validate_woocommerce_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_woocommerce_email_fields" type="radio" value="no" <?php if ($this->options["validate_wp_comment_user_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/><?php _e("No", "leav") ?>
                            </label>
                            <p class="description">
                                <?php _e("Validate all WooCommerce email addresses during registration and checkout.<br/><strong>Default: Yes</strong>", "leav") ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( "woocommerce/woocommerce.php" ) )
                                  {
                                      echo "WooCommerce "; 
                                      _e("not found in list of active plugins", "leav");
                                  }
                            ?>

                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Contact Form 7", "leav") ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "contact-form-7/wp-contact-form-7.php" )  ) : ?>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="yes" <?php if ($this->options["validate_cf7_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="no" <?php if ($this->options["validate_cf7_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", "leav") ?>
                            </label>
                            <p class="description">
                                <?php _e("Validate all Contact Form 7 email address fields.<br/><strong>Default: Yes</strong>", "leav") ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( "contact-form-7/wp-contact-form-7.php" ) )
                                  {
                                      echo "Contact Form 7 "; 
                                      _e("not found in list of active plugins", "leav");
                                  }
                            ?>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row"><?php _e("WPForms (lite and pro)", "leav") ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "wpforms-lite/wpforms.php" ) || is_plugin_active( "wpforms/wpforms.php" )  ) : ?>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="yes" <?php if ($this->options["validate_wpforms_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="no" <?php if ($this->options["validate_wpforms_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", "leav") ?>
                            </label>
                            <p class="description">
                                <?php _e("Validate all WPForms email address fields.<br/><strong>Default: Yes</strong>", "leav") ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( "wpforms-lite/wpforms.php" ) && ! is_plugin_active( "wpforms/wpforms.php" ) )
                                  {
                                      echo "WPForms "; 
                                      _e("not found in list of active plugins", "leav");
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Ninja Forms", "leav") ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "ninja-forms/ninja-forms.php" )  ) : ?>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="yes" <?php if ($this->options["validate_ninja_forms_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="no" <?php if ($this->options["validate_ninja_forms_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", "leav") ?>
                            </label>
                            <p class="description">
                                <?php _e("Validate all Ninja Forms email address fields.<br/><strong>Default: Yes</strong>", "leav") ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( "ninja-forms/ninja-forms.php" ) )
                                  {
                                      echo "Ninja Forms "; 
                                      _e("not found in list of active plugins", "leav");
                                  }
                            ?>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e("Save Changes", "leav") ?>" />
                </p>
            </form>

            <?php _e('<h1>Feature Requests</h1>If you look for more plugins, we at <a href="https://smings.com/last-email-address-validator" target="_blank">smings</a> (website will be online soon) are always happy to make<br/> LEAV - Last Email Address Validator better than it is to help you to protect your non-renewable lifetime. <br/>Just shoot us an email to <a href="mailto:leav@smings.com">leav@smings.com</a>.<br/><br/><h1>Help us help you!</h1>Lastly - if LEAV - Last Email Address Validator delivers substancial value to you, i.e. saving<br/> lots of your precious non-renewable lifetime, because it filters out tons of <br/>spam attempts, please show us your appreciation and consider a <strong><a href="https://paypal.me/DirkTornow" target="_blank">one-time donation</a></strong><br/>or become a patreon on our patreon page at <strong><a href="https://www.patreon.com/smings" target="_blank">patreon.com/smings</a></strong><br/>We appreciate your support and send you good karma points.<br/>Thank you and enjoy LEAV', "leav") ?>
        </div>
        <div class="wrap">
            <h1><?php _e("Statistics", "leav") ?></h1>
            <div class="card">
                <p>
                    <?php echo sprintf(_e("Version", "leav") . ": <strong>%s</strong>", $this->plugin_version ) ?>&nbsp;|
                    <?php _e("LEAV prevented <strong>", "leav");
                          _e( $this->options["spam_email_addresses_blocked_count"] );
                           _e("</strong> SPAM email address attempts so far.", "leav");
                          ?>
                </p>
                <p>
                    <a href="https://smings.com/leav"><?php _e("Documentation", "leav") ?></a>&nbsp;|
                    <a href="https://smings.com/leav"><?php _e("Bugs", "leav") ?></a>
                </p>
            </div>
        </div>
<?php
    }


    private function sanitize_submitted_settings_form_data()
    {
        $this->update_notice = '';
        $this->error_notice = '';
        $radio_button_fields = array('accept_pingbacks', 'accept_trackbacks', 'use_user_defined_domain_whitelist', 'use_user_defined_email_whitelist', 'use_user_defined_domain_blacklist', 'use_user_defined_email_blacklist', 'block_disposable_email_address_services', 'validate_wp_standard_user_registration_email_addresses', 'validate_wp_comment_user_email_addresses', 'validate_woocommerce_email_fields', 'validate_cf7_email_fields', 'validate_wpforms_email_fields', 'validate_ninja_forms_email_fields' );
        $radio_button_values = array( 'yes', 'no' );

        $domain_fields = array( 'wp_email_domain' );
        $domain_list_fields = array( 'user_defined_domain_whitelist', 'use_user_defined_domain_blacklist');
        $email_list_fields = array( 'user_defined_email_whitelist', 'user_defined_email_blacklist');

        foreach ($_POST as $key => $value)
        {
            // we only look at defined keys who's values have changed
            if(   ! array_key_exists( $key, $this->options )
                || $this->options[$key] == $value 
            )
                continue;

            // First we validate all radio button fields
            if( in_array( $key, $radio_button_fields ) )
            {
                if( $this->debug )
                    write_log("Validating '$key'");
                if( in_array( $value, $radio_button_values ) )
                {
                    $this->options[$key] = $value;
                    $this->add_update_notification_for_form_field($key);
                }
                else
                    $this->add_error_notification_for_form_field($key);
            }

            // Now we check the single domain entry fields
            elseif( in_array( $key, $domain_fields ) )
            {
                if( $this->debug )
                    write_log("Sanitizing '$key' => '$value'");
                if( $this->sanitize_and_validate_domain( $value ) )
                {
                    $this->options[$key] = $value;
                    $this->add_update_notification_for_form_field($key);
                }
                else
                    $this->add_error_notification_for_form_field($key);
            }

            elseif( in_array( $key, $domain_list_fields ) )
            {
                $domains = preg_split("/[\r\n]+/", $value, -1,PREG_SPLIT_NO_EMPTY);
                $value = '';
                $sanitized_internal_domains = array();
                $has_errors = false;
                foreach( $domains as $domain )
                {
                    if( $this->debug )
                        write_log("Sanitizing domain '$value' in list '$key'");


                    $original_domain = $domain;
                    if(    preg_match( self::$COMMENT_LINE_REGEX, $domain )
                        || preg_match( self::$EMPTY_LINE_REGEX, $domain )
                    )
                    {}
                    elseif( $this->sanitize_and_validate_domain( $domain ) )
                        array_push( $sanitized_internal_domains, $domain );
                    else
                    {
                        $domain = _e('# Couldn\'t sanitize this domain: ', 'leav') . $original_domain;
                        if( ! $has_errors )
                        {    
                            $this->add_error_notification_for_form_field($key);
                            $has_errors = true;
                        }
                    }
                    $value = $value . $domain . "\r\n";
                }

                $this->options[$key] = $value;
                $this->add_update_notification_for_form_field($key);
                $internal_key = 'internal_' . $key;
                $this->options[$internal_key] = $sanitized_internal_domains;
            }

            // TODO: Remove this = this is just for testing
            else
                $this->options[$key] = $value;
        }

        # if there are no errors, we update the options
        if( empty( $this->error_notice ) )
        {
            update_option($this->options_name, $this->options);
            $this->options = get_option( $this->options_name );
        }
    }


    private function sanitize_and_validate_domain( string &$domain ) : bool
    {
        $domain = strtolower( $domain );
        $domain = preg_replace( self::$SANITIZE_DOMAIN_REGEX, '', $domain );
        return $this->validate_domain( $domain );
    }


    private function sanitize_and_validate_email_address( string &$email_address ) : bool
    {
        $email_address = strtolower( sanitize_email( $email_address ) );
        return $this->validate_email_address_syntax( $email_address );
    }


    private function validate_domain( string &$domain ) : bool
    {
        return preg_match( self::$DOMAIN_REGEX, $domain );
    }


    private function validate_email_address_syntax( string &$email_address ) : bool
    {
        return preg_match( self::$EMAIL_ADDRESS_REGEX, $email_address );
    }


    private function add_update_notification_for_form_field( string &$field_name ) : bool
    {
           if( $field_name == 'wp_email_domain')
            $this->update_notice = $this->update_notice . __( 'Updated the email domain for simulating the sending of emails.<br/>', 'leav');
        elseif( $field_name == 'accept_pingbacks')
            $this->update_notice = $this->update_notice .  __( 'Updated the settings for accepting pingbacks.<br/>', 'leav');
        elseif( $field_name == 'accept_trackbacks')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for accepting trackbacks.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_domain_whitelist')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for using the user-defined domain whitelist.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_email_whitelist')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for using the user-defined email address whitelist.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_domain_blacklist')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for using the user-defined domain blacklist.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_email_blacklist')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for using the user-defined email address blacklist.<br/>', 'leav');
        elseif( $field_name == 'block_disposable_email_address_services')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for blocking email addresses from disposable email address services.<br/>', 'leav');
        elseif( $field_name == 'validate_wp_standard_user_registration_email_addresses')
            $this->update_notice = $this->update_notice . __( 'Updated the setting for validating WordPress\'s user registration email addresses.<br/>', 'leav');
        elseif( $field_name == 'validate_wp_comment_user_email_addresses')
            $this->update_notice = $this->update_notice . __( 'Updated the setting for validating WordPress\'s commentator email addresses.<br/>', 'leav');
        elseif( $field_name == 'validate_woocommerce_email_fields')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for validating WooCommerce email fields.<br/>', 'leav');
        elseif( $field_name == 'validate_cf7_email_fields')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for validating Contact Form 7 email fields.<br/>', 'leav');
        elseif( $field_name == 'validate_wpforms_email_fields')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for validating WPforms email fields.<br/>', 'leav');
        elseif( $field_name == 'validate_ninja_forms_email_fields')
            $this->update_notice = $this->update_notice . __( 'Updated the settings for validating Ninja Forms email fields.<br/>', 'leav');
        else
            $this->update_notice = $this->update_notice . __( 'Updated the settings for field <strong>', 'leav') . $field_name . '</strong><br/>';

        return true;
    }


    private function add_error_notification_for_form_field( string &$field_name )
    {
           if( $field_name == 'wp_email_domain')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the email domain for simulating the sending of emails.<br/>', 'leav');
        elseif( $field_name == 'accept_pingbacks')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for accepting pingbacks.<br/>', 'leav');
        elseif( $field_name == 'accept_trackbacks')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for accepting trackbacks.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_domain_whitelist')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for using the user-defined domain whitelist.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_email_whitelist')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for using the user-defined email address whitelist.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_domain_blacklist')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for using the user-defined domain blacklist.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_email_blacklist')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for using the user-defined email address blacklist.<br/>', 'leav');
        elseif( $field_name == 'block_disposable_email_address_services')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for blocking email addresses from disposable email address services.<br/>', 'leav');
        elseif( $field_name == 'validate_wp_standard_user_registration_email_addresses')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the setting for validating WordPress\'s user registration email addresses.<br/>', 'leav');
        elseif( $field_name == 'validate_wp_comment_user_email_addresses')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the setting for validating WordPress\'s commentator email addresses.<br/>', 'leav');
        elseif( $field_name == 'validate_woocommerce_email_fields')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for validating WooCommerce email fields.<br/>', 'leav');
        elseif( $field_name == 'validate_cf7_email_fields')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for validating Contact Form 7 email fields.<br/>', 'leav');
        elseif( $field_name == 'validate_wpforms_email_fields')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for validating WPforms email fields.<br/>', 'leav');
        elseif( $field_name == 'validate_ninja_forms_email_fields')
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for validating Ninja Forms email fields.<br/>', 'leav');
        else
            $this->update_notice = $this->update_notice . __( 'Error while trying to update the settings for field <strong>', 'leav') . $field_name . '</strong><br/>';
    }

}
?>