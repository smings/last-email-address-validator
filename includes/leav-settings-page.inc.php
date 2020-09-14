<?php
defined('ABSPATH') or die("Nice try! Go away!");
require_once("leav-central.inc.php");

class LeavSettingsPage
{
    private $central;
    private $leav;
    private $error_notice = '';
    private $update_notice = '';
    private $warning_notice = '';

    public function __construct( LeavCentral $central, LastEmailAddressValidator $leav )
    {
        $this->central = $central;
        $this->leav = $leav;
    }


    public function add_settings_page_to_menu()
    {
        // ----- for a full list of capabilities, see https://wordpress.org/support/article/roles-and-capabilities/

        // add_options_page( $this->central::$plugin_menu_name, $this->central::$plugin_menu_name, 'activate_plugins', basename(__FILE__, ".php"), array( $this, 'display_settings_page') );

        // ----- for a working menu icon all <path> elements must have the attribute <path fill="black">
        // we can convert them online here https://base64.guru/converter/encode/image/svg
        // additionally one should add width and height attributes <svg width="20" height="20" >

        add_menu_page( $this->central::$plugin_menu_name, $this->central::$plugin_menu_name_short, 'activate_plugins', basename(__FILE__, ".php"), array( $this, 'display_settings_page'), $this->central::$menu_inline_icon, 24);

        if( empty( $this->central::$options['wp_email_domain'] ) )
            add_action('admin_notices', array( $this, 'add_global_warning_wp_email_domain_not_detected' ) );

    }

    public function add_global_warning_wp_email_domain_not_detected() : void
    {
?>
        <div id="setting-error-settings_updated" class="notice notice-warning is-dismissible"> 
            <p>
                 <?php 
                    _e('LEAV - Last Email Address Validator could not automatically detect your email domain .<br/>This usually happens in your local development environment. Please go to the settings and enter an email domain under which your WordPress instance is reachable.<br/>', 'leav');
                    echo '<a href="' . $this->central::$plugin_settings_page . '">';
                    _e('Settings -> LEAV - Last Email Address Validator', 'leav');
                    echo '</a>' ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e("Dismiss this notice", 'leav') ?>.</span>
            </button>
        </div>
<?php
    }




    public function display_settings_page()
    {

        if (isset($_POST["leav_options_update_type"]) || empty( $this->central::$options['wp_email_domain'] ) )
        {
            if( isset( $_POST["leav_options_update_type"] ) )
                $this->sanitize_submitted_settings_form_data();

            if( empty( $this->central::$options['wp_email_domain'] ) )
                $this->warning_notice = __('Could not automatically determine the email domain for simulated sending of emails. Please enter your email domain below.', 'leav');

            if( ! empty( $this->warning_notice ) )
            {
?>
        <div id="setting-error-settings_updated" class="notice notice-warning is-dismissible"> 
            <p>
                <?php echo $this->warning_notice ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e("Dismiss this notice", 'leav') ?>.</span>
            </button>
        </div>
<?php
            }

            if( ! empty( $this->update_notice ) && empty( $this->error_notice) )
            {
?>
        <div id="setting-error-settings_updated" class="notice notice-success is-dismissible"> 
            <p>
                <?php echo $this->update_notice ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e("Dismiss this notice", 'leav') ?>.</span>
            </button>
        </div>
<?php
            }
            elseif( ! empty( $this->error_notice ) )
            {
                $this->error_notice = $this->error_notice . __('Your changes have not been saved! Correct your input and click on "Save Changes" again.', 'leav');
?>
        <div id="setting-error-settings_updated" class="notice notice-error is-dismissible"> 
            <p>
                <?php echo $this->error_notice ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e("Dismiss this notice", 'leav') ?>.</span>
            </button>
        </div>

<?php
            }                
        }
?>
        <div class="wrap">
            <h1 style="display: flex;  align-items: center; color:#89A441"><?php 
                _e('<img src="' . plugin_dir_url(__FILE__) . '../' . $this->central::$settings_page_logo_url . '" /> &nbsp;&nbsp;&nbsp;<strong>');
                _e( $this->central::$plugin_display_name_long ); ?></strong></h1>
                 <h1><?php _e("Settings", 'leav'); ?></h1>
            <?php _e('LEAV - Last Email Address Validator <i>by ','leav'); ?>
                  <a <?php _e( 'href="'. $this->central::$plugin_website .  '"' );?> target="_blank">smings</a></i> <?php _e('validates email addresses of the supported WordPress functions and plugins in the following multi-step process', 'leav'); ?>


            <ol>
                <li>
                    <?php _e("Check if the email address is syntactically correct. This acts as a backup check for the plugins. Some plugins only have a frontend based email syntax check. This is a proper server-side check (always)", 'leav'); ?>
                </li>
                <li>
                    <?php _e("Filter against user-defined email domain whitelist. (if activated)<br/>If you need to override false positives, you can use this option. We kindly ask you to <a href=\"mailto:leav@smings.com\">inform us</a> about wrongfully blacklisted domains, so that we can correct any errors asap." , 'leav'); ?>
                </li>
                <li>
                    <?php _e("Filter against user-defined email whitelist (if activated)<br/>If you need to override specific email addresses that would otherwise get filtered out." , 'leav'); ?>
                </li>
                <li>
                    <?php _e("Filter against user-defined email domain blacklist (if activated)" , 'leav'); ?>
                </li>
                <li>
                    <?php _e("Filter against user-defined email blacklist (if activated)" , 'leav'); ?>
                </li>
                <li>
                    <?php _e("Check if the email address's domain has a DNS entry with MX records (always)", 'leav'); ?>
                </li>
                <li>
                    <?php _e("Filter against LEAV's built-in extensive blacklist of disposable email services (if activated)", 'leav'); ?>
                </li>
                <li>
                    <?php 
                        _e("Connect to one of the MX servers and simulate the sending of an email from <strong>no-reply@", 'leav'); 
                        echo ( $this->central::$options["wp_email_domain"] ); 
                        _e("</strong> to the entered email address (always)", 'leav' ); ?>
                </li>
            </ol>
<?php 
_e("Below you can control in which way the selected WordPress functions and plugins will validate entered email adresses." , 'leav');
?><br/><br/>
            <form name="wp_mail_validator_options" method="post">
                <input type="hidden" name="leav_options_update_type" value="update" />

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e("Email domain for simulating sending of emails to entered email addresses", 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="wp_email_domain" type="text" size="40" value="<?php echo ( $this->central::$options["wp_email_domain"]); ?>" required="required" minlength="5" pattern="^([A-Za-z0-9]+\.)*[A-Za-z0-9][A-Za-z0-9]+\.[A-Za-z]{2,18}$"/>
                            </label>
                            <p class="description">
                                <?php _e("This Email domain is used for simulating the sending of an email from ", 'leav'); echo("no-reply@<strong>" . $this->central::$options["wp_email_domain"] ); _e("</strong> to the entered email address, that gets validated.<br/><strong>Please make sure you use the email domain that you use for sending emails from your WordPress instance</strong>") ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Allow email adresses from user-defined domain whitelist", 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="yes" <?php if ($this->central::$options["use_user_defined_domain_whitelist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="no" <?php if ($this->central::$options["use_user_defined_domain_whitelist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e("No", 'leav'); ?>
                            </label>
                            <p class="description">
                                <?php _e("Email addresses from the listed domains will be accepted without further checks (if active). <br/><strong>Use one domain per line</strong>.<br/><strong>Default: Yes</strong>", 'leav'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_whitelist" name="user_defined_domain_whitelist" rows="7" cols="40" placeholder="your-whitelisted-domain-1.com
your-whitelisted-domain-2.com"><?php echo ($this->central::$options["user_defined_domain_whitelist"]); ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Allow email adresses from user-defined email whitelist", 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="yes" <?php if ($this->central::$options["use_user_defined_email_whitelist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="no" <?php if ($this->central::$options["use_user_defined_email_whitelist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e("No", 'leav'); ?>
                            </label>
                            <p class="description">
                                <?php _e("Email addresses on this list will be accepted without further checks (if active). <br/><strong>Use one email address per line</strong>.<br/><strong>Default: Yes</strong>", 'leav'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_whitelist" name="user_defined_email_whitelist" rows="7" cols="40" placeholder="your.whitelisted@email-1.com
your.whitelisted@email-2.com"><?php echo $this->central::$options["user_defined_email_whitelist"] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Reject email adresses from user-defined domain blacklist", 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="yes" <?php if ($this->central::$options["use_user_defined_domain_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="no" <?php if ($this->central::$options["use_user_defined_domain_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e("No", 'leav'); ?>
                            </label>
                            <p class="description">
                                <?php _e("Email addresses from these domains will be rejected (if active). <br/><strong>Use one domain per line</strong>.<br/><strong>Default: Yes</strong>", 'leav'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_blacklist" name="user_defined_domain_blacklist" rows="7" cols="40" placeholder="your-blacklisted-domain-1.com
your-blacklisted-domain-2.com"><?php echo $this->central::$options["user_defined_domain_blacklist"] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Reject email adresses from user-defined email blacklist", 'leav'); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="yes" <?php if ($this->central::$options["use_user_defined_email_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="no" <?php if ($this->central::$options["use_user_defined_email_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e("No", 'leav'); ?>
                            </label>
                            <p class="description">
                                <?php _e("Email addresses from this list will be rejected (if active). <br/><strong>Use one email address per line</strong>.<br/><strong>Default: Yes</strong>", 'leav'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_blacklist" name="user_defined_email_blacklist" rows="7" cols="40" placeholder="your-blacklisted-domain-1.com
your-blacklisted-domain-2.com"><?php echo $this->central::$options["user_defined_email_blacklist"] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Reject email adresses from disposable email address services (DEA)", 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="block_disposable_email_address_services" type="radio" value="yes" <?php if ($this->central::$options["block_disposable_email_address_services"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="block_disposable_email_address_services" type="radio" value="no" <?php if ($this->central::$options["block_disposable_email_address_services"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", 'leav') ?>
                            </label>
                            <p class="description">
                                <?php 
                                    _e("If activated email adresses from disposable email address services (DEA) i.e. mailinator.com will be rejected. LEAV manages a comprehensive list of DEA services that is frequently updated. We block the underlying MX server IPs and not just the domains. This bulletproofs the validation against domain aliases and makes it extremely reliable, since it attacks DEAs at their core. If you found a DEA service that doesn't get blocked yet, please contact us at <a href=\"mailto:leav@smings.com\">leav@smings.com</a>.<br/><strong>Default: Yes</strong>", 'leav'); ?>
                            </p>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e("Save Changes", 'leav') ?>" />
                </p>

                <h2><?php _e("Accepting pingbacks / trackbacks", 'leav') ?></h2>
                <?php _e("Pingbacks and trackbacks can\'t be validated because they don\'t come with an email address, that could be run through our validation process.</br>Therefore <strong>pingbacks and trackbacks pose a certain spam risk</strong>. They could also be free marketing.<br/>By default we therefore accept them. But feel free to reject them.", 'leav') ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e("Accept pingbacks", 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="yes" <?php if ($this->central::$options["accept_pingbacks"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="no" <?php if ($this->central::$options["accept_pingbacks"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", 'leav') ?>
                            </label>
                            <p class="description">
                                <strong><?php _e("Default:", 'leav') ?> <?php _e("Yes", 'leav') ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e("Accept trackbacks", 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="yes" <?php if ($this->central::$options["accept_trackbacks"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="no" <?php if ($this->central::$options["accept_trackbacks"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", 'leav') ?>
                            </label>
                            <p class="description">
                                <strong><?php _e("Default:", 'leav') ?> <?php _e("Yes", 'leav') ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>
                <h2><?php _e("Control which of WordPress's functions and plugins should be email validated by LEAV", 'leav') ?></h2>
                <?php _e('LEAV - Last Email Address Validator is currently capable of validating the email<br/>addresses for the following WordPress features and plugins (if installed and activated): <br/><ol><li>WordPress user registration (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)</li><li>WordPress comments (<a href="/wp-admin/options-discussion.php" target="_blank">Settings -> Discussion)</li><li><a href="https://wordpress.org/plugins/woocommerce/" target="_blank"> WooCommerce (plugin)</a></li><li><a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7 (plugin)</a></li><li><a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">WPForms (lite and pro) (plugin)</a></li><li><a href="https://wordpress.org/plugins/ninja-forms/" target="_blank">Ninja Forms (plugin)</a></li><li><a href="https://wordpress.org/plugins/mailchimp-for-wp/"" target="_blank">Mailchimp for WordPress (MC4WP) (plugin)</a></li></ol></br>', 'leav') ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e("Control which functions and plugins to validate with LEAV", 'leav') ?>:</th>
                        <td/>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e("WordPress user registration", 'leav') ?>:</th>
                        <td>
                            <?php if( get_option("users_can_register") == 1 && $this->central::$options["validate_wp_standard_user_registration_email_addresses"] == "yes" ) : ?>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="yes" <?php if ($this->central::$options["validate_wp_standard_user_registration_email_addresses"] == "yes") { echo 'checked="checked" '; } ?>/><?php _e("Yes", 'leav') ?></label>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="no" <?php if ($this->central::$options["validate_wp_standard_user_registration_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/><?php _e("No", 'leav') ?></label>
                            <p class="description">
                                <?php _e("This validates all registrants email address's that register through WordPress's standard user registration.<br/><strong>Default: Yes</strong>", 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( get_option("users_can_register") == 0 || $this->central::$options["validate_wp_standard_user_registration_email_addresses"] == "no" )
                                  {
                                      _e('WordPress\'s built-in user registration is currently deactivated (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)', 'leav');
                                  }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e("WordPress comments", 'leav') ?>:</th>
                        <td>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="yes" <?php if ($this->central::$options["validate_wp_comment_user_email_addresses"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="no" <?php if ($this->central::$options["validate_wp_comment_user_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e("This validates all (not logged in) commentator's email address's that comment through WordPress's standard comment functionality.<br/><strong>Default: Yes</strong>", 'leav') ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("WooCommerce", 'leav') ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "woocommerce/woocommerce.php" ) ) : ?>
                            <label>
                                <input name="validate_woocommerce_email_fields" type="radio" value="yes" <?php if ($this->central::$options["validate_woocommerce_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_woocommerce_email_fields" type="radio" value="no" <?php if ($this->central::$options["validate_woocommerce_email_fields"] == "no") { echo 'checked="checked" '; } ?>/><?php _e("No", 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e("Validate all WooCommerce email addresses during registration and checkout.<br/><strong>Default: Yes</strong>", 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( "woocommerce/woocommerce.php" ) )
                                  {
                                      echo "WooCommerce "; 
                                      _e("not found in list of active plugins", 'leav');
                                  }
                            ?>

                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Contact Form 7", 'leav') ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "contact-form-7/wp-contact-form-7.php" )  ) : ?>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="yes" <?php if ($this->central::$options["validate_cf7_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="no" <?php if ($this->central::$options["validate_cf7_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e("Validate all Contact Form 7 email address fields.<br/><strong>Default: Yes</strong>", 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( "contact-form-7/wp-contact-form-7.php" ) )
                                  {
                                      echo "Contact Form 7 "; 
                                      _e("not found in list of active plugins", 'leav');
                                  }
                            ?>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row"><?php _e("WPForms (lite and pro)", 'leav') ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "wpforms-lite/wpforms.php" ) || is_plugin_active( "wpforms/wpforms.php" )  ) : ?>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="yes" <?php if ($this->central::$options["validate_wpforms_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="no" <?php if ($this->central::$options["validate_wpforms_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e("Validate all WPForms email address fields.<br/><strong>Default: Yes</strong>", 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( "wpforms-lite/wpforms.php" ) && ! is_plugin_active( "wpforms/wpforms.php" ) )
                                  {
                                      echo "WPForms "; 
                                      _e("not found in list of active plugins", 'leav');
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Ninja Forms", 'leav') ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "ninja-forms/ninja-forms.php" )  ) : ?>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="yes" <?php if ($this->central::$options["validate_ninja_forms_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="no" <?php if ($this->central::$options["validate_ninja_forms_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e("Validate all Ninja Forms email address fields.<br/><strong>Default: Yes</strong>", 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( "ninja-forms/ninja-forms.php" ) )
                                  {
                                      echo "Ninja Forms "; 
                                      _e("not found in list of active plugins", 'leav');
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Mailchimp for WordPress (MC4WP)", 'leav') ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "mailchimp-for-wp/mailchimp-for-wp.php" )  ) : ?>
                            <label>
                                <input name="validate_mc4wp_email_fields" type="radio" value="yes" <?php if ($this->central::$options["validate_mc4wp_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", 'leav') ?>
                            </label>
                            <label>
                                <input name="validate_mc4wp_email_fields" type="radio" value="no" <?php if ($this->central::$options["validate_mc4wp_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", 'leav') ?>
                            </label>
                            <p class="description">
                                <?php _e("Validate all MC4WP email address fields.<br/><strong>Default: Yes</strong>", 'leav') ?>
                            </p>
                            <?php endif; 
                                  if( ! is_plugin_active( "mailchimp-for-wp/mailchimp-for-wp.php" ) )
                                  {
                                      echo "Mailchimp for WordPress (MC4WP) "; 
                                      _e("not found in list of active plugins", 'leav');
                                  }
                            ?>
                        </td>
                    </tr>


                </table>

                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e("Save Changes", 'leav') ?>" />
                </p>
            </form>

            <?php _e('<h1>Feature Requests</h1>If you look for more plugins, we at <a href="https://smings.com/last-email-address-validator" target="_blank">smings</a> (website will be online soon) are always happy to make<br/> LEAV - Last Email Address Validator better than it is to help you to protect your non-renewable lifetime. <br/>Just shoot us an email to <a href="mailto:leav@smings.com">leav@smings.com</a>.<br/><br/><h1>Help us help you!</h1>Lastly - if LEAV - Last Email Address Validator delivers substancial value to you, i.e. saving<br/> lots of your precious non-renewable lifetime, because it filters out tons of <br/>spam attempts, please show us your appreciation and consider a <strong><a href="https://paypal.me/DirkTornow" target="_blank">one-time donation</a></strong><br/>or become a patreon on our patreon page at <strong><a href="https://www.patreon.com/smings" target="_blank">patreon.com/smings</a></strong><br/>We appreciate your support and send you good karma points.<br/>Thank you and enjoy LEAV', 'leav') ?>
        </div>
        <div class="wrap">
            <h1><?php _e("Statistics", 'leav') ?></h1>
            <div class="card">
                <p>
                    <?php echo sprintf(_e("Version", 'leav') . ": <strong>%s</strong>", $this->central::$plugin_version ) ?>&nbsp;|
                    <?php _e("LEAV prevented <strong>", 'leav');
                          _e( $this->central::$options["spam_email_addresses_blocked_count"] );
                           _e("</strong> SPAM email address attempts so far.", 'leav');
                          ?>
                </p>
                <p>
                    <a href="https://smings.com/leav"><?php _e("Documentation", 'leav') ?></a>&nbsp;|
                    <a href="https://smings.com/leav"><?php _e("Bugs", 'leav') ?></a>
                </p>
            </div>
        </div>
<?php
    }


    private function sanitize_submitted_settings_form_data()
    {
        $this->update_notice = '';
        $this->error_notice = '';

        foreach ($_POST as $key => $value)
        {
            // we only look at defined keys who's values have changed
            if(   ! array_key_exists( $key, $this->central::$options )
                || $this->central::$options[$key] == $value 
            )
                continue;

            // First we validate all radio button fields
            if(    in_array( $key, $this->central::$radio_button_fields ) 
                && $this->validate_radio_button_form_fields($key, $value) 
            )
                continue;

            // Now we check the single domain entry fields
            elseif( in_array( $key, $this->central::$domain_fields ) )
            {
                if( $this->leav->sanitize_and_validate_domain( $value ) )
                {
                    $this->central::$options[$key] = $value;
                    $this->add_update_notification_for_form_field($key);
                }
                else
                    $this->add_error_notification_for_form_field($key);
                continue;
            }

            elseif(    in_array( $key, $this->central::$domain_list_fields ) 
                    || in_array( $key, $this->central::$email_list_fields  )
            )
            {
                $lines = preg_split("/[\r\n]+/", $value, -1, PREG_SPLIT_NO_EMPTY);
                $value = '';
                $sanitized_internal_values = array();
                $has_errors = false;

                if( $this->central::$debug )
                {
                    write_log("Splitted lines look like this");
                    write_log( $lines );
                }

                foreach( $lines as $id => $line )
                {
                    if(    preg_match( $this->central::$COMMENT_LINE_REGEX, $line )
                        || preg_match( $this->central::$EMPTY_LINE_REGEX, $line )
                    )
                    {
                        $value = $value . $line . "\r\n";
                        continue;
                    }

                    $original_line = $line;
                    if(    in_array( $key, $this->central::$domain_list_fields )
                        && $this->leav->sanitize_and_validate_domain( $line ) 
                    )
                    {
                        $value = $value . $line . "\r\n";
                        array_push( $sanitized_internal_values, $line );
                        continue;
                    }
                    
                    elseif( in_array( $key, $this->central::$email_list_fields )
                        && $this->leav->sanitize_and_validate_email_address( $line ) 
                    )
                    {
                        $value = $value . $line . "\r\n";
                        array_push( $sanitized_internal_values, $line );
                    }

                    else
                    {
                        if( ! $has_errors )
                        {    
                            $this->add_error_notification_for_form_field($key);
                            $has_errors = true;
                        }
                        $line = __('# Next line\'s value is invalid', 'leav') . "\r\n". "# " . $original_line;
                        $value = $value . $line . "\r\n";
                    }
                    
                }

                // cutting of a trainling \r\n
                $value = substr($value, 0, -2);
                $this->central::$options[$key] = $value;
                $this->add_update_notification_for_form_field($key);
                $internal_key = 'internal_' . $key;
                $this->central::$options[$internal_key] = $sanitized_internal_values;
            }
        }

        # if there are no errors, we update the options
        if( empty( $this->error_notice ) )
        {
            update_option($this->central::$options_name, $this->central::$options);
            $this->central::$options = get_option( $this->central::$options_name );
        }
    }


    private function validate_radio_button_form_fields( string &$key, string &$value ) : bool
    {
        if( in_array( $value, $this->central::$radio_button_values ) )
        {
            $this->central::$options[$key] = $value;
            $this->add_update_notification_for_form_field($key);
            return true;
        }
        else
            $this->add_error_notification_for_form_field($key);
        return false;
    }


    // private function sanitize_and_validate_domain( string &$domain ) : bool
    // {
    //     $domain = strtolower( $domain );
    //     $domain = preg_replace( $this->central::$SANITIZE_DOMAIN_REGEX, '', $domain );
    //     return $this->validate_domain_syntax( $domain );
    // }


    // private function sanitize_and_validate_email_address( string &$email_address ) : bool
    // {
    //     $email_address = strtolower( sanitize_email( $email_address ) );
    //     return $this->validate_email_address_syntax( $email_address );
    // }


    // private function validate_domain_syntax( string &$domain ) : bool
    // {
    //     return preg_match( $this->central::$DOMAIN_REGEX, $domain );
    // }


    // private function validate_email_address_syntax( string &$email_address ) : bool
    // {
    //     return preg_match( $this->central::$EMAIL_ADDRESS_REGEX, $email_address );
    // }


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

        elseif( $field_name == 'user_defined_domain_whitelist')
            $this->update_notice = $this->update_notice . __( 'Updated the user-defined domain whitelist.<br/>', 'leav');
        elseif( $field_name == 'user_defined_email_whitelist')
            $this->update_notice = $this->update_notice . __( 'Updated the user-defined email address whitelist.<br/>', 'leav');
        elseif( $field_name == 'user_defined_domain_blacklist')
            $this->update_notice = $this->update_notice . __( 'Updated the user-defined domain blacklist.<br/>', 'leav');
        elseif( $field_name == 'user_defined_email_blacklist')
            $this->update_notice = $this->update_notice . __( 'Updated the user-defined email address blacklist.<br/>', 'leav');

        else
            $this->update_notice = $this->update_notice . __( 'Updated the settings for field <strong>', 'leav') . $field_name . '</strong><br/>';

        return true;
    }


    private function add_error_notification_for_form_field( string &$field_name )
    {
           if( $field_name == 'wp_email_domain')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the email domain for simulating the sending of emails.<br/>', 'leav');
        elseif( $field_name == 'accept_pingbacks')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for accepting pingbacks.<br/>', 'leav');
        elseif( $field_name == 'accept_trackbacks')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for accepting trackbacks.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_domain_whitelist')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for using the user-defined domain whitelist.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_email_whitelist')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for using the user-defined email address whitelist.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_domain_blacklist')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for using the user-defined domain blacklist.<br/>', 'leav');
        elseif( $field_name == 'use_user_defined_email_blacklist')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for using the user-defined email address blacklist.<br/>', 'leav');
        elseif( $field_name == 'block_disposable_email_address_services')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for blocking email addresses from disposable email address services.<br/>', 'leav');
        elseif( $field_name == 'validate_wp_standard_user_registration_email_addresses')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the setting for validating WordPress\'s user registration email addresses.<br/>', 'leav');
        elseif( $field_name == 'validate_wp_comment_user_email_addresses')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the setting for validating WordPress\'s commentator email addresses.<br/>', 'leav');
        elseif( $field_name == 'validate_woocommerce_email_fields')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for validating WooCommerce email fields.<br/>', 'leav');
        elseif( $field_name == 'validate_cf7_email_fields')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for validating Contact Form 7 email fields.<br/>', 'leav');
        elseif( $field_name == 'validate_wpforms_email_fields')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for validating WPforms email fields.<br/>', 'leav');
        elseif( $field_name == 'validate_ninja_forms_email_fields')
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for validating Ninja Forms email fields.<br/>', 'leav');

        elseif( $field_name == 'user_defined_domain_whitelist')
            $this->error_notice = $this->error_notice . __( 'Error! One or more entered domains in the user-defined domain whitelist is invalid. Look at the comments in the field and correct your input.<br/>', 'leav');
        elseif( $field_name == 'user_defined_email_whitelist')
            $this->error_notice = $this->error_notice . __( 'Error! One or more entered email addresses in the user-defined email address whitelist is invalid. Look at the comments in the field and correct your input.<br/>', 'leav');
        elseif( $field_name == 'user_defined_domain_blacklist')
            $this->error_notice = $this->error_notice . __( 'Error! One or more entered domains in the user-defined domain blacklist is invalid. Look at the comments in the field and correct your input.<br/>', 'leav');
        elseif( $field_name == 'user_defined_email_blacklist')
            $this->error_notice = $this->error_notice . __( 'Error! One or more entered email addresses in the user-defined email address blacklist is invalid. Look at the comments in the field and correct your input.<br/>', 'leav');

        else
            $this->error_notice = $this->error_notice . __( 'Error while trying to update the settings for field <strong>', 'leav') . $field_name . '</strong><br/>';
    }

}
?>