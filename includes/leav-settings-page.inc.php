<?php

function leav_options_page()
{
    global $d;
    global $leav_options;
    global $leav_plugin_website;
    global $leav_plugin_name;
    global $leav_plugin_short_name;

    global $disposable_email_service_domain_list_url;
    global $is_windows;
    
    if (isset($_POST["leav_options_update_type"]))
    {
        $update_notice = "";

        foreach ($_POST as $key => $value)
        {
            // todo: sanitize user input
            if($d)
                write_log("key=value => $key=$value");
            if ($key !== "leav_options_update_type" && $key !== "submit")
            {
                $leav_options[$key] = $value;
            }
        }
        $update_notice = __("Options updated", "leav");
        update_option("leav_options", $leav_options);
        $leav_options = get_option("leav_options");
    ?>
        <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
            <p>
                <strong><?php echo $update_notice ?>.</strong>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e("Dismiss this notice", "leav") ?>.</span>
            </button>
        </div>
    <?php
    }
    ?>
        <div class="wrap">
            <h1><?php _e("Settings for <strong>", "leav"); _e( $leav_plugin_name ); ?></strong> by <i>
                <a <?php _e( 'href="'. $leav_plugin_website .  '"' );?> target="_blank">smings</a></i></h1>
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
                        echo ($leav_options["wp_mail_domain"]); 
                        _e("</strong> to the entered email address (always)", "leav" ); ?>
                </li>
            </ol>
<?php 
_e("Below you can control in which way the selected WordPress functions and plugins will validate entered email adresses." , "leav");
?><br/><br/>
            <form name="wp_mail_validator_options" method="post">
                <input type="hidden" name="leav_options_update_type" value="update" />

                <?php if ($is_windows) { ?>
                    <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                        <tr>
                            <th scope="row"><?php _e("Default gateway IP", "leav") ?>:</th>
                            <td>
                                <input name="default_gateway" type="text" id="default_gateway" value="<?php echo $leav_options["default_gateway"] ?>" maxlength="15" size="40" />
                                <br /><?php _e("Leave blank to use Windows default gateway.<br/>If you use a non-default gateway configuration on your windows system, you might have to enter this gateway IP here", "leav") ?>.
                            </td>
                        </tr>
                    </table>
                <?php } ?>

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e("Email domain for simulating sending of emails to entered email addresses", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="wp_mail_domain" type="text" size="40" value="<?php echo ( $leav_options["wp_mail_domain"]); ?>" required="required" minlength="5" pattern="^([A-Za-z0-9]+\.)*[A-Za-z0-9][A-Za-z0-9]+\.[A-Za-z]{2,18}$"/>
                            </label>
                            <p class="description">
                                <?php _e("This Email domain is used for simulating the sending of an email from ", "leav"); echo("no-reply@<strong>" . $leav_options["wp_mail_domain"] ); _e("</strong> to the entered email address, that gets validated.<br/><strong>Please make sure you use the email domain that you use for sending emails from your WordPress instance</strong>") ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Allow email adresses from user-defined domain whitelist", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="yes" <?php if ($leav_options["use_user_defined_domain_whitelist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="no" <?php if ($leav_options["use_user_defined_domain_whitelist"] == "no") { echo ('checked="checked" '); } ?>/>
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
your-whitelisted-domain-2.com"><?php echo ($leav_options["user_defined_domain_whitelist"]); ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Allow email adresses from user-defined email whitelist", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="yes" <?php if ($leav_options["use_user_defined_email_whitelist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="no" <?php if ($leav_options["use_user_defined_email_whitelist"] == "no") { echo ('checked="checked" '); } ?>/>
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
your.whitelisted@email-2.com"><?php echo $leav_options["user_defined_email_whitelist"] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Reject email adresses from user-defined domain blacklist", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="yes" <?php if ($leav_options["use_user_defined_domain_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="no" <?php if ($leav_options["use_user_defined_domain_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
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
your-blacklisted-domain-2.com"><?php echo $leav_options["user_defined_domain_blacklist"] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Reject email adresses from user-defined email blacklist", "leav"); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="yes" <?php if ($leav_options["use_user_defined_email_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="no" <?php if ($leav_options["use_user_defined_email_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
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
your-blacklisted-domain-2.com"><?php echo $leav_options["user_defined_email_blacklist"] ?></textarea>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e("Reject email adresses from domains on LEAV's comprehensive and frequently updated list of disposable email services", "leav") ?>:</th>
                        <td>
                            <label>
                                <input name="block_disposable_email_service_domains" type="radio" value="yes" <?php if ($leav_options["block_disposable_email_service_domains"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="block_disposable_email_service_domains" type="radio" value="no" <?php if ($leav_options["block_disposable_email_service_domains"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e("No", "leav") ?>
                            </label>
                            <p class="description">
                                <?php 
                                    _e("Currently we have ", "leav"); 
                                    echo ("1396 "); 
                                    _e("domains listed as either known disposable email address service providers or spammers. Users that make use of disposable email address services might just want to protect their own privacy. But they might also be spammers. There is no good choice. In doubt we encourage you to value your lifetime and reject email addresses from these domains. We frequently update this list. For retrieving updates, just update the plugin when new versions come out. For blocking domains of your own choosing, use the user-defined blacklist option above.<br/><strong>Default: Yes</strong>", "leav"); ?>
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
                                <input name="accept_pingbacks" type="radio" value="yes" <?php if ($leav_options["accept_pingbacks"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="no" <?php if ($leav_options["accept_pingbacks"] == "no") { echo 'checked="checked" '; } ?>/>
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
                                <input name="accept_trackbacks" type="radio" value="yes" <?php if ($leav_options["accept_trackbacks"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="no" <?php if ($leav_options["accept_trackbacks"] == "no") { echo 'checked="checked" '; } ?>/>
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
                            <?php if( get_option("users_can_register") == 1 && $leav_options["validate_wp_standard_user_registration_email_addresses"] == "yes" ) : ?>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="yes" <?php if ($leav_options["validate_wp_standard_user_registration_email_addresses"] == "yes") { echo 'checked="checked" '; } ?>/><?php _e("Yes", "leav") ?></label>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="no" <?php if ($leav_options["validate_wp_standard_user_registration_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/><?php _e("No", "leav") ?></label>
                            <p class="description">
                                <?php _e("This validates all registrants email address's that register through WordPress's standard user registration.<br/><strong>Default: Yes</strong>", "leav") ?>
                            </p>
                            <?php endif; 
                                  if( get_option("users_can_register") == 0 || $leav_options["validate_wp_standard_user_registration_email_addresses"] == "no" )
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
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="yes" <?php if ($leav_options["validate_wp_comment_user_email_addresses"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="no" <?php if ($leav_options["validate_wp_comment_user_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/>
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
                                <input name="validate_woocommerce_registration" type="radio" value="yes" <?php if ($leav_options["validate_woocommerce_registration"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_woocommerce_registration" type="radio" value="no" <?php if ($leav_options["validate_wp_comment_user_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/><?php _e("No", "leav") ?>
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
                                <input name="validate_cf7_email_fields" type="radio" value="yes" <?php if ($leav_options["validate_cf7_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="no" <?php if ($leav_options["validate_cf7_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
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
                                <input name="validate_wpforms_email_fields" type="radio" value="yes" <?php if ($leav_options["validate_wpforms_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="no" <?php if ($leav_options["validate_wpforms_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
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
                                <input name="validate_ninja_forms_email_fields" type="radio" value="yes" <?php if ($leav_options["validate_ninja_forms_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e("Yes", "leav") ?>
                            </label>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="no" <?php if ($leav_options["validate_ninja_forms_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
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

            <?php _e('<h1>Feature Requests</h1>If you look for more plugins, we at <a href="https://smings.com/last-email-address-validator" target="_blank">smings</a> (website will be online soon) are always happy to make<br/> LEAV - Last Email Address Validator better than it is to help you to protect your non-renewable lifetime. Just shoot us an email to <br/><a href="mailto:leav-feature-requests@smings.com">leav-feature-requests@smings.com</a>.<br/><br/><h1>Help us help you!</h1>Lastly - if LEAV - Last Email Address Validator delivers substancial value to you, i.e. saving<br/> lots of your precious non-renewable lifetime, because it filters out tons of <br/>spam attempts, please show us your appreciation and consider a <strong><a href="https://paypal.me/DirkTornow" target="_blank">one-time donation</a></strong><br/>or become a patreon on our patreon page at <strong><a href="https://www.patreon.com/smings" target="_blank">patreon.com/smings</a></strong><br/>We appreciate your support and send you good karma points.<br/>Thank you and enjoy LEAV', "leav") ?>
        </div>
        <div class="wrap">
            <h1><?php _e("Statistics", "leav") ?></h1>
            <div class="card">
                <p>
                    <?php echo sprintf(_e("Version", "leav") . ": <strong>%s</strong>", leav_version()) ?>&nbsp;|
                    <?php _e("LEAV prevented <strong>", "leav");
                          _e(leav_get_blocked_email_address_count());
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
?>