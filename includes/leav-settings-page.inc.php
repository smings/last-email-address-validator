<?php
defined('ABSPATH') or die("Nice try! Go away!");
require_once("leav-central.inc.php");

class LeavSettingsPage
{
    private $leav_plugin;
    private $central;
    private $leav;
    private $error_notice = '';
    private $update_notice = '';
    private $warning_notice = '';

    public function __construct( LeavPlugin &$leav_plugin, LeavCentral &$central, LastEmailAddressValidator $leav  )
    {
        $this->leav_plugin = $leav_plugin;
        $this->central = $central;
        $this->leav = $leav;
    }


    public function add_settings_page_to_menu()
    {
        // ----- for a full list of capabilities, see https://wordpress.org/support/article/roles-and-capabilities/
        // add_options_page( $this->central::$PLUGIN_MENU_NAME, $this->central::$PLUGIN_MENU_NAME, 'activate_plugins', basename(__FILE__, ".php"), array( $this, 'display_settings_page') );

        // ----- for a working menu icon all <path> elements must have the attribute <path fill="black">
        // we can convert them online here https://base64.guru/converter/encode/image/svg
        // additionally one should add width and height attributes <svg width="20" height="20" >
        if( $this->central::$OPTIONS['use_main_menu'] == 'yes' )
        {
            add_menu_page( $this->central::$PLUGIN_MENU_NAME, $this->central::$PLUGIN_MENU_NAME_SHORT, 'activate_plugins', basename(__FILE__, ".php"), array( $this, 'display_settings_page'), $this->central::$MENU_INLINE_ICON, $this->central::$OPTIONS['main_menu_position'] );

        }
        else
            add_options_page($this->central::$PLUGIN_MENU_NAME, $this->central::$PLUGIN_MENU_NAME, 'activate_plugins', basename(__FILE__, ".php"), array( $this, 'display_settings_page'), intval( $this->central::$OPTIONS['settings_menu_position'] ) );

    }

    public function add_global_warning_wp_email_domain_not_detected() : void
    {

?>
        <div id="setting-error-settings_updated" class="notice notice-warning is-dismissible">
            <p>
                 <?php
                    _e('LEAV - Last Email Address Validator could not automatically detect your email domain .<br/>This usually happens in your local development environment. Please go to the settings and enter an email domain under which your WordPress instance is reachable.<br/>', 'last-email-address-validator' );
                    echo '<a href="' . $this->central::$PLUGIN_SETTING_PAGE . '">';
                    _e('Settings', 'last-email-address-validator' );
                    echo '</a>' ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'last-email-address-validator' ) ?>.</span>
            </button>
        </div>
<?php
    }




    public function display_settings_page()
    {

        // even if we just load the settings page without any changes by the user, we look at
        // the wp_email_domain and the simulation settings
        if (   isset($_POST["leav_options_update_type"])
            || (    empty( $this->central::$OPTIONS['wp_email_domain'] )
                 && $this->central::$OPTIONS['simulate_email_sending'] == 'yes'
               )
        )
        {
            if( isset( $_POST["leav_options_update_type"] ) )
                $this->sanitize_submitted_settings_form_data();

            if(
                   empty( $this->central::$OPTIONS['wp_email_domain'] )
                && $this->central::$OPTIONS['simulate_email_sending'] == 'yes'
            )
                $this->warning_notice = __('Could not automatically determine the email domain for simulated sending of emails. Please enter your <a href="#email_domain">email domain below</a> or <a href="#ses">deactivate the simulated email sending</a> to permanently dismiss this warning message.', 'last-email-address-validator' );

            if( ! empty( $this->warning_notice ) )
            {
?>
        <div id="setting-error-settings_updated" class="notice notice-warning is-dismissible">
            <p>
                <?php echo $this->warning_notice ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'last-email-address-validator' ) ?>.</span>
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
                <span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'last-email-address-validator' ) ?>.</span>
            </button>
        </div>
<?php
            }
            elseif( ! empty( $this->error_notice ) )
            {
                $this->error_notice .= __('Your changes have not been saved! Correct your input and click on "Save Changes" again.', 'last-email-address-validator' );
?>
        <div id="setting-error-settings_updated" class="notice notice-error is-dismissible">
            <p>
                <?php echo $this->error_notice ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'last-email-address-validator' ) ?>.</span>
            </button>
        </div>

<?php
            }

        }
?>
<script>
window.onload = function (event) {
    window.location.hash = '';
};
</script>
        <div class="wrap">
            <a name="top"></a>
            <h1 style="display: flex;  align-items: center; color:#89A441; font-size: 30px;"><?php
                _e('<img width="75px" src="' . plugin_dir_url(__FILE__) . '../' . $this->central::$SETTINGS_PAGE_LOGO_URL . '" /> &nbsp;&nbsp;&nbsp;<strong>');
                _e( $this->central::$PLUGIN_DISPLAY_NAME_LONG ); ?></strong></h1>
                 <h1><?php _e( 'Settings', 'last-email-address-validator' ); ?></h1>
                 <br/>
                <div>
                    <span>
                        <strong>
                            <?php _e( 'Quick Navigation', 'last-email-address-validator' ); ?>
                        </strong>
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#test_email_address">
                            <?php _e('Test Email Address Vaildation', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#email_domain">
                            <?php _e('Email Domain', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#allow_recipient_name_catch_all">
                            <?php _e('Recipient Name Catch All', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#whitelists">
                            <?php _e('Whitelists', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#blacklists">
                            <?php _e('Blacklists', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#dea">
                            <?php _e( 'Disposable Email Address Blocking', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#ses">
                            <?php _e('Simulate Email Sending', 'last-email-address-validator' ) ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#cad">
                            <?php _e('Catch-all domains', 'last-email-address-validator' ) ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#functions_plugins">
                            <?php _e('LEAV-validated Functions / Plugins', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#ping_track_backs">
                            <?php _e('Pingbacks / Trackbacks', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#custom_messages">
                            <?php _e('Custom Error Messages', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#menu_location">
                            <?php _e('LEAV Menu Item Location', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#faq">
                            <?php _e( 'FAQ', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#feature_requests">
                            <?php _e( 'Feature Requests', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#help">
                            <?php _e( 'Help Us, Help You', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#stats">
                            <?php _e( 'Statistics / Version', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <a name="test_email_address"></a>
                <br/><br/>
            <form name="leav_options" method="post">
                <input type="hidden" name="leav_options_update_type" value="update" />

                <h2><?php _e('Test Current Email Address Validation Settings', 'last-email-address-validator' ) ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Email address to test', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="test_email_address" type="email" placeholder="emailaddress@2test.com" value="<?php
                                    if( isset( $_POST[ 'test_email_address' ] ) )
                                            echo( $_POST[ 'test_email_address' ] )
                                ?>" size="40" />
                            </label>
                            <?php

                                if( ! empty( $_POST[ 'test_email_address' ] ) )
                                {
                                    if( ! $this->leav_plugin->validate_email_address( $_POST[ 'test_email_address' ], false ) )
                                    {
                                        echo('<p><span style="color:#a00"><strong>');
                                        _e( 'Validation result for email address ', 'last-email-address-validator' );
                                        echo( '</strong></span><span>"' . $_POST[ 'test_email_address' ] . '" </span><span style="color:#a00"><strong>');
                                        _e( 'is negative!', 'last-email-address-validator' );
                                        echo('</strong></span></p><p><span style="color:#a00"><strong>');
                                        _e( 'ERROR TYPE:', 'last-email-address-validator' );
                                        echo('</strong></span><span> "' . $this->leav_plugin->get_email_validation_error_type() );
                                        echo('" </span></p><p><span style="color:#a00"><strong>');
                                        _e( 'ERROR MESSAGE:', 'last-email-address-validator' );
                                        echo('</strong></span><span> "' . $this->leav_plugin->get_email_validation_error_message() );
                                        echo('"</span></p><br/>');
                                    }
                                    else
                                    {
                                        echo('<p><span style="color:#89A441"><strong>');
                                        _e( 'Validation result for email address', 'last-email-address-validator' );
                                        echo( ' </strong></span><span>"' . $_POST[ 'test_email_address' ] . '" </span><span  style="color:#89A441"><strong>');
                                        _e( 'is positive!', 'last-email-address-validator' );
                                        echo('</strong></span></p><p><span style="color:#89A441">');
                                        _e( 'The email address got successfully validated. It is good to go!', 'last-email-address-validator' );
                                        echo('</span></p><br/>');
                                    }
                                }
                                ?>
                            <p class="description">
                                <?php _e('Test any email address against LEAV\'s current settings.<br/>No emails will be sent out or saved anywhere.<br/>Feel free to adjust the settings to your individual needs. We encourage you to do thorough testing.', 'last-email-address-validator' ); ?>
                            </p>

                        </td>
                    </tr>
                </table>


                <a name="email_domain"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Test Email Address', 'last-email-address-validator' ) ?>" />
                </p>

                <h2><?php _e('Email Domain', 'last-email-address-validator' ) ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e("Email domain for simulating sending of emails to entered email addresses", 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="wp_email_domain" type="text" size="40" value="<?php echo ( $this->central::$OPTIONS["wp_email_domain"]); ?>" placeholder="<?php echo( $this->central::$PLACEHOLDER_EMAIL_DOMAIN ); ?>" />
                            </label>
                            <p class="description">
                                <?php _e('The Email domain is used for simulating the sending of an email from no-reply@<strong>', 'last-email-address-validator' );
                                if( ! empty( $this->central::$OPTIONS["wp_email_domain"] ) )
                                    echo( $this->central::$OPTIONS["wp_email_domain"] );
                                else
                                    echo( $this->central::$PLACEHOLDER_EMAIL_DOMAIN ) ;
                                _e('</strong> to the entered email address, that gets validated.<br/><strong>Please make sure you enter the email domain that you use for sending emails from your WordPress instance. If the email domain doesn\'t point to your WordPress instance\'s IP address, simulating the sending of emails might fail. This is usually only the case in development or test environments. In these cases you might have to disable the <a href="#ses">simulation of sending an email</a>.<br/>Default: Automatically detected WordPress Domain.</strong>', 'last-email-address-validator' ) ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <a name="allow_recipient_name_catch_all"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>


                <h1>
                    <?php
                        _e( 'Filter Function Settings', 'last-email-address-validator' );
                    ?>
                </h1>
                <?php
                    _e( 'From here onwards you can configure the filter steps. You can find an overview and description of the filter steps in <a href="#faq">our FAQ</a>.', 'last-email-address-validator' );
                ?>
                <h2><?php _e('Recipient Name Catch-All Syntax', 'last-email-address-validator' ) ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Allow recipient name catch-all syntax', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="allow_recipient_name_catch_all_email_addresses" type="radio" value="yes" <?php if ($this->central::$OPTIONS["allow_recipient_name_catch_all_email_addresses"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="allow_recipient_name_catch_all_email_addresses" type="radio" value="no" <?php if ($this->central::$OPTIONS["allow_recipient_name_catch_all_email_addresses"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                _e('Allow recipient name (the part of an email address before the "@") catch-all syntax. google and other email address providers allow you to extend the recipient name part of an email address with a "+" followed by whatever text. The only limitation is a maximum length of 64 characters for the recipient name.<br/><strong>"my.name+anything@gmail.com"</strong> is the same as <strong>"my.name@gmail.com"</strong> for google. This allows users to "cloak" their "main" email address, which is usually used to differentiate where and what the user signed up for.<br/>You can choose to allow this or block such email addresses.', 'last-email-address-validator' );
                                _e('<br/><strong>Default: Yes</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>


                <a name="whitelists">
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

                <h2></a><?php _e('Whitelists', 'last-email-address-validator' ) ?></h2>
                <?php _e('Any email address that gets whitelisted will skip the corresponding blacklist filter. It doesn\'t mean that it doesn\'t get filtered out by other filters. I.e. if a domain is whitelisted, but it is a catch-all domain and you disallow catch-all domains, all email addresses from this domain will still get rejected with these validation settings. Look at our <a href="#faq">FAQ</a> for detailed information on the validation process.', 'last-email-address-validator' ) ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <a name="dwl"/>
                        <th scope="row"><?php _e( 'Use Domain Whitelist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="yes" <?php if ($this->central::$OPTIONS["use_user_defined_domain_whitelist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="no" <?php if ($this->central::$OPTIONS["use_user_defined_domain_whitelist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e( 'Email addresses from the listed domains will be accepted without further domain blacklist  checks (if active).', 'last-email-address-validator' );
                                    _e( '<br/>For information on how to use wildcards, see our <a href="#faq-wildcards">FAQ entry</a>.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Enter one domain per line</strong>.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: No</strong>', 'last-email-address-validator' );
                                 ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_whitelist_string" name="user_defined_domain_whitelist_string" rows="7" cols="40" placeholder="your-whitelisted-domain-1.com
your-whitelisted-domain-2.com"><?php echo ($this->central::$OPTIONS['user_defined_domain_whitelist_string']); ?></textarea><br/>
                                <?php
                                    _e( 'Number of entries: ', 'last-email-address-validator' );

                                    $size = 0;
                                    if( is_array( $this->central::$OPTIONS['user_defined_domain_whitelist'] ) )
                                    {
                                        if( array_key_exists( 'domains', $this->central::$OPTIONS['user_defined_domain_whitelist'] ) )
                                            $size += sizeof( 
                                                    $this->central::$OPTIONS['user_defined_domain_whitelist']['domains'] 
                                            );
                                        if( array_key_exists( 'regexps', $this->central::$OPTIONS['user_defined_domain_whitelist'] ) )
                                            $size += sizeof( 
                                                $this->central::$OPTIONS['user_defined_domain_whitelist']['regexps'] 
                                            );
                                    }
                                    echo( 
                                        strval( $size )
                                    );
                                ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <a name="ewl"/>
                        <th scope="row"><?php _e( 'Use email adress whitelist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="yes" <?php if ($this->central::$OPTIONS["use_user_defined_email_whitelist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="no" <?php if ($this->central::$OPTIONS["use_user_defined_email_whitelist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('Email addresses on this list will be accepted without further email address blacklist checks (if active).', 'last-email-address-validator' );
                                    _e( '<br/>Unlike with domains and recipient names, you can\'t use wildcards for email addresses.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Enter one email address per line</strong>.', 'last-email-address-validator' );
                                    _e('<br/><strong>Default: No</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_whitelist_string" name="user_defined_email_whitelist_string" rows="7" cols="40" placeholder="your.whitelisted@email-1.com
your.whitelisted@email-2.com"><?php echo $this->central::$OPTIONS["user_defined_email_whitelist_string"] ?></textarea><br/>
                                <?php
                                    _e( 'Number of entries: ', 'last-email-address-validator' );
                                    echo( 
                                        strval( 
                                            sizeof( 
                                                $this->central::$OPTIONS['user_defined_email_whitelist']
                                            )
                                        ) 
                                    );
                                ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <a name="rnwl"/>
                        <th scope="row"><?php _e( 'Use recipient name whitelist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_recipient_name_whitelist" type="radio" value="yes" <?php if ($this->central::$OPTIONS["use_user_defined_recipient_name_whitelist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_recipient_name_whitelist" type="radio" value="no" <?php if ($this->central::$OPTIONS["use_user_defined_recipient_name_whitelist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('Recipient names on this list will be accepted without further recipient name blacklist checks, either user-defined and/or role-based (if active).', 'last-email-address-validator' );
                                    _ex('<br/>Entered recipient names will automatically be stripped of any non-letter (a-z) characters except for wildcards.', 'last-email-address-validator' );
                                    _e( '<br/>For information on how to use wildcards, see our <a href="#faq-wildcards">FAQ entry</a>.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Enter one recipient name per line</strong>.', 'last-email-address-validator' );
                                    _e('<br/><strong>Default: No</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_recipient_name_whitelist_string" name="user_defined_recipient_name_whitelist_string" rows="7" cols="40" placeholder="your-recipient-name-1
your-recipient-name-2"><?php echo $this->central::$OPTIONS["user_defined_recipient_name_whitelist_string"] ?></textarea><br/>
                                <?php
                                    _e( 'Number of entries: ', 'last-email-address-validator' );
                                    $size = 0;
                                    if( is_array( $this->central::$OPTIONS['user_defined_recipient_name_whitelist'] ) )
                                    {
                                        if( array_key_exists( 'recipient_names', $this->central::$OPTIONS['user_defined_recipient_name_whitelist'] ) )
                                            $size += sizeof( 
                                                    $this->central::$OPTIONS['user_defined_recipient_name_whitelist']['recipient_names'] 
                                            );
                                        if( array_key_exists( 'regexps', $this->central::$OPTIONS['user_defined_recipient_name_whitelist'] ) )
                                            $size += sizeof( 
                                                $this->central::$OPTIONS['user_defined_recipient_name_whitelist']['regexps'] 
                                            );
                                    }
                                    echo( 
                                        strval( $size )
                                    );
                                ?>
                            </label>
                        </td>
                    </tr>


                </table>


                <a name="blacklists">
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

                <h2></a><?php _e('Blacklists', 'last-email-address-validator' ) ?></h2>
                <?php _e('Any email address that gets matched by a blacklist rule gets rejected, unless it has previously been whitelisted for the blacklist rule. If an email address gets matched by a blacklist rule, all subsequent validations get skipped.', 'last-email-address-validator' ) ?><br/>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">

                    <tr>
                        <a name="dbl"/>
                        <th scope="row"><?php _e( 'Use domain blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="yes" <?php if ($this->central::$OPTIONS["use_user_defined_domain_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="no" <?php if ($this->central::$OPTIONS["use_user_defined_domain_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e( 'Email addresses from these domains will be rejected (if active).', 'last-email-address-validator' );
                                    _e( '<br/>For information on how to use wildcards, see our <a href="#faq-wildcards">FAQ entry</a>.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Enter one domain per line</strong>.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: No</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_blacklist_string" name="user_defined_domain_blacklist_string" rows="7" cols="40" placeholder="your-blacklisted-domain-1.com
your-blacklisted-domain-2.com"><?php echo $this->central::$OPTIONS["user_defined_domain_blacklist_string"] ?></textarea><br/>
                                <?php
                                    _e( 'Number of entries: ', 'last-email-address-validator' );
                                    if( is_array( $this->central::$OPTIONS['user_defined_domain_blacklist'] ) )
                                    {
                                        if( array_key_exists( 'domains', $this->central::$OPTIONS['user_defined_domain_blacklist'] ) )
                                            $size += sizeof( 
                                                    $this->central::$OPTIONS['user_defined_domain_blacklist']['domains'] 
                                            );
                                        if( array_key_exists( 'regexps', $this->central::$OPTIONS['user_defined_domain_blacklist'] ) )
                                            $size += sizeof( 
                                                $this->central::$OPTIONS['user_defined_domain_blacklist']['regexps'] 
                                            );
                                    }
                                    echo( 
                                        strval( $size )
                                    );
                                ?>
                            </label>
                        </td>
                    </tr>


                    <tr>
                        <a name="feapdbl"/>
                        <th scope="row"><?php _e( 'Use free email address provider domain blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_free_email_address_provider_domain_blacklist" type="radio" value="yes" <?php if ($this->central::$OPTIONS["use_free_email_address_provider_domain_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_free_email_address_provider_domain_blacklist" type="radio" value="no" <?php if ($this->central::$OPTIONS["use_free_email_address_provider_domain_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('The list comprises the most common free email address services. If for example you want to enforce business email addresses, you can activate this blacklist feature and reject email addresses from domains on this list.<br/>If you feel that we missed important domains, you can add them on the user-defined domain blacklist above. But please also ', 'last-email-address-validator' );
                                    echo( '<a href="mailto:' . $this->central::$PLUGIN_CONTACT_EMAIL . '">' );
                                    _e( 'inform us</a> about it. This list is not editable.', 'last-email-address-validator' );
                                    _e( '<br/>If you should wonder why we block the entire top-level-domains .cf, .ga, .gq, .mk and .tk, here is why: these top-level-domains are free of charge and therefore wildy popular with private individuals, that don\'t want to spend anything on a domain. Because of this we treat them like free email address providers. These top-level-domains are almost exclusively registered by individuals and not (relevant) companies.', 'last-email-address-validator' );
                                    _e('<br/><strong>Default: No</strong>', 'last-email-address-validator' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="free_email_address_provider_domain_blacklist_string_display_only" name="free_email_address_provider_domain_blacklist_string_display_only" rows="7" cols="40" readonly><?php echo $this->central::$OPTIONS["free_email_address_provider_domain_blacklist_string"] ?></textarea><br/>
                                <?php
                                    _e( 'Number of entries: ', 'last-email-address-validator' );
                                    echo( 
                                        strval( 
                                            sizeof( 
                                                $this->central::$OPTIONS['free_email_address_provider_domain_blacklist']['domains'] 
                                            ) 
                                            +  
                                            sizeof( 
                                                $this->central::$OPTIONS['free_email_address_provider_domain_blacklist']['regexps'] 
                                            )
                                        ) 
                                    );
                                ?>
                            </label>
                        </td>
                    </tr>


                    <tr>
                        <a name="ebl"/>
                        <th scope="row"><?php _e( 'Use email address blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="yes" <?php if ($this->central::$OPTIONS["use_user_defined_email_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="no" <?php if ($this->central::$OPTIONS["use_user_defined_email_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e( 'Email addresses from this list will be rejected (if active).', 'last-email-address-validator' );
                                    _e( '<br/>Unlike with domains and recipient names, you can\'t use wildcards for email addresses.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Enter one email address per line</strong>.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: No</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_blacklist_string" name="user_defined_email_blacklist_string" rows="7" cols="40" placeholder="your-blacklisted-email-1@domain.com
your-blacklisted-email-2@domain.com"><?php echo $this->central::$OPTIONS["user_defined_email_blacklist_string"] ?></textarea><br/>
                                <?php
                                    _e( 'Number of entries: ', 'last-email-address-validator' );
                                    $size = 0;
                                    if( is_array( $this->central::$OPTIONS['user_defined_email_blacklist'] ) )
                                        $size += sizeof( $this->central::$OPTIONS['user_defined_email_blacklist'] );
                                    echo( 
                                        strval( $size )
                                    );
                                ?>


                            </label>
                        </td>
                    </tr>


                    <tr>
                        <a name="rnbl"/>
                        <th scope="row"><?php _e( 'Use recipient name blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_recipient_name_blacklist" type="radio" value="yes" <?php if ($this->central::$OPTIONS["use_user_defined_recipient_name_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_recipient_name_blacklist" type="radio" value="no" <?php if ($this->central::$OPTIONS['use_user_defined_recipient_name_blacklist'] == 'no') { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('If activated, email addresses with recipient names (the part before the "@" sign) from the list below, will be rejected. The recipient names will get automatically "collapsed" to only their letters. This means that non-letter characters get stripped from the original recipient name. "<strong>d.e.m.o.123@domain.com</strong>" gets collapsed into "<strong>demo@domain.com</strong>".<br/>This way, we automatically block role-based recipient names, that are altered with punctuation and non-letter characters.<br/>This list is meant for user-defined additional entries that are not (yet) covered by our built-in role-based recipient name blacklist below.', 'last-email-address-validator' );
                                    _e('<br/>Entered recipient names will automatically be stripped of any non-letter (a-z) characters except for wildcards.', 'last-email-address-validator' );
                                    _e( '<br/>For information on how to use wildcards, see our <a href="#faq-wildcards">FAQ entry</a>.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Enter one recipient name per line</strong>.', 'last-email-address-validator' );
                                    _e('<br/><strong>Default: No</strong>', 'last-email-address-validator' );
                                ?>

                            </p>
                            <label>
                                <textarea id="user_defined_recipient_name_blacklist_string" name="user_defined_recipient_name_blacklist_string" rows="7" cols="40" placeholder="blacklisted recipient name 1
blacklisted recipient name 1"><?php echo $this->central::$OPTIONS['user_defined_recipient_name_blacklist_string'] ?></textarea><br/>
                                <?php
                                    _e( 'Number of entries: ', 'last-email-address-validator' );
                                    $size = 0;
                                    if( is_array( $this->central::$OPTIONS['user_defined_recipient_name_blacklist'] ) )
                                    {
                                        if( array_key_exists( 'recipient_names', $this->central::$OPTIONS['user_defined_recipient_name_blacklist'] ) )
                                            $size += sizeof( 
                                                    $this->central::$OPTIONS['user_defined_recipient_name_blacklist']['recipient_names'] 
                                            );
                                        if( array_key_exists( 'regexps', $this->central::$OPTIONS['user_defined_recipient_name_blacklist'] ) )
                                            $size += sizeof( 
                                                $this->central::$OPTIONS['user_defined_recipient_name_blacklist']['regexps'] 
                                            );
                                    }
                                    echo( 
                                        strval( $size )
                                    );
                                ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <a name="rbrnbl"/>
                        <th scope="row"><?php _e( 'Use role-based recipient name blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_role_based_recipient_name_blacklist" type="radio" value="yes" <?php if ($this->central::$OPTIONS["use_role_based_recipient_name_blacklist"] == "yes") { echo ('checked="checked" '); } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_role_based_recipient_name_blacklist" type="radio" value="no" <?php if ($this->central::$OPTIONS["use_role_based_recipient_name_blacklist"] == "no") { echo ('checked="checked" '); } ?>/>
                                <?php _e('No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                _e('If activated, email addresses with generic, role-based recipient names (the part before the "@" sign) from the list below, will be rejected. The recipient names are validated in their "collapsed" form. This means that all punctuation is stripped from the original recipient name. "<strong>i.n.f.o@domain.com</strong>" gets collapsed into "<strong>info@domain.com</strong>" (which is on the list). "<strong>123-all-456-employees@domain.com</strong>" gets collapsed into "<strong>allemployees@domain.com</strong>" and so on. Essentially, we strip away all non-letter characters. This way, we can block role-based recipient names, that are altered with punctuation.<br/>If the collapsed recipient name is empty, it will also be detected as role-based recipient name. In this case it contains only digits and non-letter characters, which we consider a role-based recipient name.<br/>This list is not editable. If you want to block other recipient names than on this list, please use the recipient name blacklist above.<br/>If we block too much for you, you can add recipient names to the whitelist above.<br/>If you think we missed important common role-based recipient names, <a href="mailto:leav@sming.com">please let us know</a>.', 'last-email-address-validator' );
                                _e('<br/><strong>Default: No</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                            <label>
                                <textarea id="display_only" name="display_only" rows="7" cols="40" readonly><?php echo $this->central::$OPTIONS['role_based_recipient_name_blacklist_string'] ?></textarea><br/>
                                <?php
                                    _e( 'Number of entries: ', 'last-email-address-validator' );
                                    echo( 
                                        strval( 
                                            sizeof( 
                                                $this->central::$OPTIONS['role_based_recipient_name_blacklist']['recipient_names'] 
                                            ) 
                                            +  
                                            sizeof( 
                                                $this->central::$OPTIONS['role_based_recipient_name_blacklist']['regexps'] 
                                            )
                                        ) 
                                    );
                                ?>
                            </label>
                        </td>
                    </tr>

                </table>

                <a name="dea"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

                <h2><a name="dea"></a><?php _e( 'Disposable Email Address Blocking', 'last-email-address-validator' ) ?></h2>


                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Use disposable email address service (DEA) blacklist', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="block_disposable_email_address_services" type="radio" value="yes" <?php if ($this->central::$OPTIONS["block_disposable_email_address_services"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="block_disposable_email_address_services" type="radio" value="no" <?php if ($this->central::$OPTIONS["block_disposable_email_address_services"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e( 'If activated email adresses from disposable email address services (DEA) i.e. mailinator.com, maildrop.cc, guerrillamail.com and many more will be rejected. LEAV manages a comprehensive list of DEA services that is frequently updated. We block the underlying MX server domains and IP addresses - not just the website domains. This bulletproofs the validation against domain aliases and makes it extremely reliable, since it attacks DEAs at their core. If you found a DEA service that doesn\'t get blocked yet, please ', 'last-email-address-validator' );
                                    echo( '<a href="mailto:' . $this->central::$PLUGIN_CONTACT_EMAIL . '">' );
                                    _e( 'contact us</a>.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                        </td>
                    </tr>

                </table>

                <a name="ses"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

                <h2><?php _e( 'Simulate Email Sending', 'last-email-address-validator' ) ?></h2>


                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Simulate Email Sending', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="simulate_email_sending" type="radio" value="yes" <?php if ($this->central::$OPTIONS['simulate_email_sending'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="simulate_email_sending" type="radio" value="no" <?php if ($this->central::$OPTIONS['simulate_email_sending'] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('If activated LEAV tries to simulate the sending of an email. For this we connect to one of the MX servers and test if it would accept an email from your email domain (see above) to the email address that gets validated. If the used email domain doesn\'t point to your WordPress instance\'s IP address, this might fail. This is usually only the case in development or test environments. Test this with a working email address. If it gets rejected, you might have to deactivate this option.<br/><strong>This option should always be active in production environments<br/>Default: Yes</strong>', 'last-email-address-validator' ); ?>
                            </p>
                        </td>
                    </tr>

                </table>


                <a name="cad"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

                <h2><?php _e( 'Allow catch-all domains', 'last-email-address-validator' ) ?></h2>


                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Accept email addresses from catch-all domains', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="allow_catch_all_domains" type="radio" value="yes" <?php if ($this->central::$OPTIONS['allow_catch_all_domains'] == 'yes') { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="allow_catch_all_domains" type="radio" value="no" <?php if ($this->central::$OPTIONS['allow_catch_all_domains'] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('Here you can control whether to accept email addresses from domains, that allow arbritary recipient names. These are domains that allow arbritary recipient names like <strong>dtras657td8giuy23gtf7e3628@catch-all-domain.com</strong>.<br/>For whom might this be important? I.e. if you have a website with a free trial, you might want to make it a bit harder for leechers to get an unlimited amount of free accounts. Of course users with their own domains can create an unlimited amount of email accounts, but by not allowing catch-all domains, it makes it harder for them. I use catch-all domains myself and there is generally nothing wrong about it. You\'ll have to decide for yourself, whether this is important for you or not. Just so you know: even gmail.com allows any recipient name. If you set this option to "No", you should also reject email addresses from free email address providers above.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' ); ?>
                            </p>
                        </td>
                    </tr>

                </table>



                <a name="functions_plugins"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

                <h1><?php _e('LEAV-validated Functions / Plugins', 'last-email-address-validator' ) ?></h1>
                <?php _e('Control which functions and plugins will get validated by LEAV\'s algorithm.', 'last-email-address-validator' ) ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'WordPress user registration', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <?php if( get_option("users_can_register") == 1 && $this->central::$OPTIONS["validate_wp_standard_user_registration_email_addresses"] == "yes" ) : ?>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="yes" <?php if ($this->central::$OPTIONS["validate_wp_standard_user_registration_email_addresses"] == "yes") { echo 'checked="checked" '; } ?>/><?php _e('Yes', 'last-email-address-validator' ) ?></label>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="no" <?php if ($this->central::$OPTIONS["validate_wp_standard_user_registration_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/><?php _e('No', 'last-email-address-validator' ) ?></label>
                            <p class="description">
                                <?php
                                    _e('This validates all registrants email address\'s that register through WordPress\'s standard user registration. (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' )
                                ?>
                            </p>
                            <?php endif;
                                  if( get_option("users_can_register") == 0 || $this->central::$OPTIONS["validate_wp_standard_user_registration_email_addresses"] == "no" )
                                  {
                                      _e('WordPress\'s built-in user registration is currently deactivated (<a href="/wp-admin/options-general.php" target="_blank" target="_blank">Settings -> General</a>)', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'WordPress comments', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="yes" <?php if ($this->central::$OPTIONS["validate_wp_comment_user_email_addresses"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="no" <?php if ($this->central::$OPTIONS["validate_wp_comment_user_email_addresses"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('This validates all (not logged in) commentator\'s email address\'s that comment through WordPress\'s standard comment functionality. (<a href="/wp-admin/options-discussion.php" target="_blank">Settings -> Discussion)</a>', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' )
                                ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'WooCommerce', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "woocommerce/woocommerce.php" ) ) : ?>
                            <label>
                                <input name="validate_woocommerce_email_fields" type="radio" value="yes" <?php if ($this->central::$OPTIONS["validate_woocommerce_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_woocommerce_email_fields" type="radio" value="no" <?php if ($this->central::$OPTIONS["validate_woocommerce_email_fields"] == "no") { echo 'checked="checked" '; } ?>/><?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e( 'Validate all WooCommerce email addresses during registration and checkout.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "woocommerce/woocommerce.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> ';
                                      _e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>

                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Contact Form 7', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "contact-form-7/wp-contact-form-7.php" )  ) : ?>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="yes" <?php if ($this->central::$OPTIONS["validate_cf7_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="no" <?php if ($this->central::$OPTIONS["validate_cf7_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e( 'Validate all Contact Form 7 email address fields.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' ) ?>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "contact-form-7/wp-contact-form-7.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7</a> ';
                                      _e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row"><?php _e( 'WPForms (lite and pro)', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "wpforms-lite/wpforms.php" ) || is_plugin_active( "wpforms/wpforms.php" )  ) : ?>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="yes" <?php if ($this->central::$OPTIONS["validate_wpforms_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="no" <?php if ($this->central::$OPTIONS["validate_wpforms_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e( 'Validate all WPForms email address fields.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "wpforms-lite/wpforms.php" ) && ! is_plugin_active( "wpforms/wpforms.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">WPForms </a>';
                                      _e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Ninja Forms', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "ninja-forms/ninja-forms.php" )  ) : ?>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="yes" <?php if ($this->central::$OPTIONS["validate_ninja_forms_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="no" <?php if ($this->central::$OPTIONS["validate_ninja_forms_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e( 'Validate all Ninja Forms email address fields.<br/>', 'last-email-address-validator' );
                                    _e( 'The names of the fields that will get validated by LEAV must contain "email", "e-mail", "e.mail", "E-Mail"... (case insensitive)', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "ninja-forms/ninja-forms.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/ninja-forms/" target="_blank">Ninja Forms </a>';
                                      _e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Mailchimp for WordPress (MC4WP)', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "mailchimp-for-wp/mailchimp-for-wp.php" )  ) : ?>
                            <label>
                                <input name="validate_mc4wp_email_fields" type="radio" value="yes" <?php if ($this->central::$OPTIONS["validate_mc4wp_email_fields"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_mc4wp_email_fields" type="radio" value="no" <?php if ($this->central::$OPTIONS["validate_mc4wp_email_fields"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('Validate all MC4WP email address fields.<br/>', 'last-email-address-validator' );
                                    _e( 'The names of the fields that will get validated by LEAV must contain "email", "e-mail", "e.mail", "E-Mail"... (case insensitive)', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "mailchimp-for-wp/mailchimp-for-wp.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/mailchimp-for-wp/" target="_blank">Mailchimp for WordPress (MC4WP) </a>';
                                      _e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Formidable Forms', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "formidable/formidable.php" )  ) : ?>
                            <label>
                                <input name="validate_formidable_forms_email_fields" type="radio" value="yes" <?php if ($this->central::$OPTIONS['validate_formidable_forms_email_fields'] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_formidable_forms_email_fields" type="radio" value="no" <?php if ($this->central::$OPTIONS['validate_formidable_forms_email_fields'] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('Validate all Formidable Forms email address fields.', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' )
                                ?>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "formidable/formidable.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/formidable/" target="_blank">Formidable Forms</a> ';
                                      _e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Kali Forms', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <?php if( is_plugin_active( "kali-forms/kali-forms.php" )  ) : ?>
                            <label>
                                <input name="validate_kali_forms_email_fields" type="radio" value="yes" <?php if ( $this->central::$OPTIONS['validate_kali_forms_email_fields'] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_kali_forms_email_fields" type="radio" value="no" <?php if ( $this->central::$OPTIONS['validate_kali_forms_email_fields'] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    _e('Validate all Kali Forms email address fields.<br/>', 'last-email-address-validator' );
                                    _e( 'The names of the fields that will get validated by LEAV must contain "email", "e-mail", "e.mail", "E-Mail"... (case insensitive)', 'last-email-address-validator' );
                                    _e( '<br/><strong>Default: Yes</strong>', 'last-email-address-validator' );
                                ?>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "kali-forms/kali-forms.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/kali-forms/" target="_blank">Kali Forms</a> ';
                                      _e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                </table>


                <a name="ping_track_backs"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

                <h2><a name="pingbacks"></a><?php _e('Pingbacks / Trackbacks', 'last-email-address-validator' ) ?></h2>
                <?php _e('Pingbacks and trackbacks can\'t be validated because they don\'t come with an email address, that could be run through our validation process.</br>Therefore <strong>pingbacks and trackbacks pose a certain spam risk</strong>. They could also be free marketing.<br/>By default we therefore accept them.', 'last-email-address-validator' ) ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Accept pingbacks', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="yes" <?php if ($this->central::$OPTIONS["accept_pingbacks"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="no" <?php if ($this->central::$OPTIONS["accept_pingbacks"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <strong><?php _e( 'Default:', 'last-email-address-validator' ) ?> <?php _e('Yes', 'last-email-address-validator' ) ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Accept trackbacks', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="yes" <?php if ($this->central::$OPTIONS["accept_trackbacks"] == "yes") { echo 'checked="checked" '; } ?>/>
                                <?php _e('Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="no" <?php if ($this->central::$OPTIONS["accept_trackbacks"] == "no") { echo 'checked="checked" '; } ?>/>
                                <?php _e('No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <strong><?php _e( 'Default:', 'last-email-address-validator' ) ?> <?php _e('Yes', 'last-email-address-validator' ) ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

                <a name="custom_messages"></a>
                <h1><?php _e('Custom Error Messages', 'last-email-address-validator' ) ?></h1>
                <?php _e('If you want to override the default validation error messages or if you want to translate them without having to go through .po files, you can replace the default validation error messages below. The placeholder texts are the default error messages. Overwrite them to use your custom validation error messages. Delete the field\'s contents for using the defaults again.<br/>In multi-language sites, you will have to do the translations within the .po files that come with the plugin. Of course you can do this with the help of plugins like WPML and others as well.', 'last-email-address-validator' ) ?>

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">

                    <tr>
                        <th scope="row"><?php _e('Email address syntax error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_address_syntax_error" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_email_address_syntax_error"]); ?>"  placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['email_address_syntax_error'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Email address recipient name catch-all syntax error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_recipient_name_catch_all_email_address_error" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_recipient_name_catch_all_email_address_error"]); ?>"  placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['recipient_name_catch_all_email_address_error'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Email domain blacklisted error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_domain_is_blacklisted" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_email_domain_is_blacklisted"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['email_domain_is_blacklisted'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Email domain is on list of free email address provider domains error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_domain_is_on_free_email_address_provider_domain_list" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_email_domain_is_on_free_email_address_provider_domain_list"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['email_domain_is_on_free_email_address_provider_domain_list'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Email address is blacklisted error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_address_is_blacklisted" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_email_address_is_blacklisted"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['email_address_is_blacklisted'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Recipient name is blacklisted error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_recipient_name_is_blacklisted" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_recipient_name_is_blacklisted"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['recipient_name_is_blacklisted'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Recipient name is role-based error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_recipient_name_is_role_based" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_recipient_name_is_role_based"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['recipient_name_is_role_based'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('No MX (Mail eXchange) server found error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_domain_has_no_mx_record" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_email_domain_has_no_mx_record"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['email_domain_has_no_mx_record'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Email address from disposable email address service error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_domain_is_on_dea_blacklist" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_email_domain_is_on_dea_blacklist"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['email_domain_is_on_dea_blacklist'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Simulating sending an email failed error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_simulated_sending_of_email_failed" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_simulated_sending_of_email_failed"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['simulated_sending_of_email_failed'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Catch-all domains not allowed error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_from_catch_all_domain" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_email_from_catch_all_domain"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['email_from_catch_all_domain'] ); ?>"/>
                            </label>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row"><?php _e('General email validation error', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_general_email_validation_error" type="text" size="80" value="<?php echo ( $this->central::$OPTIONS["cem_general_email_validation_error"]); ?>" placeholder="<?php echo( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS['general_email_validation_error'] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                 </table>

                <a name="menu_location"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

                <h1><?php _e('LEAV Menu Item Location', 'last-email-address-validator' ) ?></h1>
                <?php _e('We believe that LEAV will provide great value for you for as long as you use it. But after setting it up, you don\'t have to worry about it anymore. We understand that after having set up LEAV you might want to move the LEAV menu item to a different location in the main menu or move it away from the main menu into the settings menu. Here you can control where to place it.<br/>The lower the number for a location, the higher up in the menu the LEAV menu item will be displayed. We allow locations in between 0-999.<br/>After changing the values, you\'ll have to reload the page.', 'last-email-address-validator' ) ?>

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Show LEAV menu item in main menu / settings menu', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <?php _e('Show in ', 'last-email-address-validator' ); ?> &nbsp;&nbsp;
                                <input name="use_main_menu" type="radio" value="yes" <?php if ($this->central::$OPTIONS['use_main_menu'] == 'yes') { echo 'checked="checked" '; } ?> />
                                <?php _e('main menu &nbsp;&nbsp;or', 'last-email-address-validator' ) ?> &nbsp;&nbsp;
                            </label>
                            <label>
                                <input name="use_main_menu" type="radio" value="no" <?php if ($this->central::$OPTIONS['use_main_menu'] == 'no') { echo 'checked="checked" '; } ?>/>
                                <?php _e('settings menu', 'last-email-address-validator' ) ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('LEAV menu item location (main menu)', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="main_menu_position" type="number" size="3" value="<?php echo ( $this->central::$OPTIONS['main_menu_position']); ?>" min="0" max="999" required />
                            </label>
                            <p class="description">
                                <?php _e('Values in between 0-999 are allowed.<br/>0 = top menu position<br/><br/><strong>Default: 24</strong>', 'last-email-address-validator' ) ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('LEAV menu item location (settings menu)', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="settings_menu_position" type="number" size="3" value="<?php echo ( $this->central::$OPTIONS['settings_menu_position']); ?>" min="0" max="999" required />
                            </label>
                            <p class="description">
                                <?php _e('Values in between 0-999 are allowed.<br/>0 = top menu position<br/><strong>Default: 3</strong>', 'last-email-address-validator' ) ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <a name="faq"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php _e( 'Save Changes', 'last-email-address-validator' ) ?>" />
                </p>

            </form>
            <h1><?php _e( 'FAQ - Frequently Asked Questions', 'last-email-address-validator' ); ?></h1>
            <h2><?php _e( 'How exactly does LEAV validate email addresses?', 'last-email-address-validator' ); ?></h2>
            <?php
                echo( $this->central::$PLUGIN_DISPLAY_NAME_FULL . ' <i>by <a href="'.  $this->central::$PLUGIN_WEBSITE . '" target="_blank">smings</a></i> ' );
                _e('validates email addresses of the supported WordPress functions and plugins in the following multi-step process', 'last-email-address-validator' );
            ?>


            <ol>
                <li>
                    <strong>
                        <?php _e( 'Email Address Syntax Validation (always active)', 'last-email-address-validator' ); ?>
                    </strong>
                    <br/>
                    <?php _e( 'Checks if the email address is syntactically correct. This acts as a backup check for the plugin\'s checks. Some plugins only have a frontend based email syntax check. This is a regular expression-based server-side check. We wouldn\'t even need it, but use it for performance reasons to filter out wrong emails without further checking', 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php
                            _e( 'Recipient Name Catch-All Syntax (optional)', 'last-email-address-validator' );
                        ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['allow_recipient_name_catch_all_email_addresses'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#allow_recipient_name_catch_all">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php
                        _e( 'Control if you want to filter out email addresses with a recipient name catch-all syntax. For more information what a recipient name catch-all syntax is, please check our FAQ entry below.', 'last-email-address-validator' );
                    ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Domain Whitelist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['use_user_defined_domain_whitelist'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#dwl">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php
                        _e( 'Filters against the user-defined email domain whitelist (if activated).<br/>Use this whitelist to override potential false positives from extensive (wildcard) domain blacklist rules. Whenever an email address gets matches by this whitelist, the domain blacklist check gets skipped.<br/>We kindly ask you to ', 'last-email-address-validator' );
                        echo( '<a href="mailto:' . $this->central::$PLUGIN_CONTACT_EMAIL . '">' );
                        _e('inform us</a> about wrongfully blacklisted domains, so that we can correct any errors asap.' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Email Address Whitelist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['use_user_defined_email_whitelist'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#ewl">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php _e( 'Filters against the user-defined email whitelist (if activated)<br/>If you need to override specific email addresses that would otherwise get filtered out by the blacklist filters.' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Recipient Name Whitelist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['use_user_defined_recipient_name_whitelist'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#rnwl">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php _e( 'Filters against the user-defined recipient name whitelist (if activated)<br/>If you need to override specific recipient names that would otherwise get filtered out by either the user-defined recipient name blacklist or the role-based recipient name blacklist. If a recipient name gets matched by this whitelist, both recipient name blacklist checks get skipped.' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Domain Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['use_user_defined_domain_blacklist'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#dbl">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php _e( 'Filters against the user-defined email domain blacklist (if activated).' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Free Email Address Provider Domain Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['use_free_email_address_provider_domain_blacklist'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#feapdbl">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php _e( 'Filters against the built-in free email address provider domain blacklist (if activated). This list gets updated with new plugin releases.' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Email Address Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['use_user_defined_email_blacklist'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#ebl">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php _e( 'Filters against the user-defined email address blacklist (if activated).' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Recipient Name Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['use_user_defined_recipient_name_blacklist'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#rnbl">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php _e( 'Filters against the user-defined recipient name blacklist (if activated).' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Role-Based Recipient Name Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['use_role_based_recipient_name_blacklist'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#rbrnbl">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php _e( 'Filters against the built-in role-based recipient name blacklist (if activated).' , 'last-email-address-validator' ); ?>
                </li>

                <li>
                    <strong>
                        <?php _e( 'DNS MX Server Lookup (always active)', 'last-email-address-validator' ); ?>
                    </strong>
                    <br/>
                    <?php _e( 'Check if the email address\'s domain has a DNS entry with MX records (always)', 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Disposable Email Address (DEA) Service Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['block_disposable_email_address_services'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#dea">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php _e('Filters against the built-in extensive blacklist of disposable email services (if activated). This list gets updated with new plugin releases.', 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Simulate Email Sending (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['simulate_email_sending'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#dea">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php
                        _e('Connects to one of the MX servers and simulates the sending of an email from <strong>no-reply@', 'last-email-address-validator' );
                        if( ! empty( $this->central::$OPTIONS["wp_email_domain"] ) )
                            echo( $this->central::$OPTIONS["wp_email_domain"] );
                        else
                            echo( $this->central::$PLACEHOLDER_EMAIL_DOMAIN ) ;
                        _e('</strong> to the entered email address. No actual email will be sent out. This is just LEAV asking the receiving server, if it would accept the email address. Then the dialog with the MX server gets terminated without any email being sent. It\'s essentially like looking at a house\'s mailboxes and checking if there is a mailbox with a specific name on it and if we can open it and see if the letter would fit in without dropping it into the mailbox.', 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php _e( 'Allow Email Addresses from Catch-All Domains (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            _e( ' - Current setting is "<strong>', 'last-email-address-validator' );
                            if( $this->central::$OPTIONS['allow_catch_all_domains'] == 'no' )
                                _e( 'No', 'last-email-address-validator' );
                            else
                                _e( 'Yes', 'last-email-address-validator' );
                            echo( '</strong>"' );
                            echo( ' - <a href="#cad">' );
                            _e( 'Change settings</a>', 'last-email-address-validator' );
                        ?>
                    <br/>
                    <?php _e('If set to "No", this filters out all email addresses that originate from domains that accept emails for ANY recipient name.', 'last-email-address-validator' ); ?>
                </li>

            </ol>
            <a name="faq-wildcards"></a>
            <br/>
            <h2><?php _e( 'Can I use wildcards for the whitelists/blacklists?', 'last-email-address-validator' ); ?></h2>
            <?php _e( 'The short answer is yes and here is how it works', 'last-email-address-validator' ); ?>
            <h3><?php
                    _e( '<strong>Wildcard syntax for domains:</strong>', 'last-email-address-validator' ); ?></h3>
                <?php
                    _e( 'You can use asterisks "<strong>*</strong>" as wildcards in domain names. It stands for zero up to any amount of characters. I.e. "<strong>mail4*.com</strong>" matches all emails from domains starting with <br/>"<strong>mail4</strong>" followed by "<strong>.com</strong>". In this example "<strong>mail4.com</strong>", "<strong>mail4i.com</strong>", "<strong>mail4me.com</strong>", "<strong>mail4myparents.com</strong>" would all be matched.<br/>You can use "*" for entire subdomains and top-level domains (TLDs) (Explanation: subdomain2.subdomain1.domain.tld).<br/>"<strong>*.mail.*</strong>" matches "<strong>a.mail.tk</strong>" or "<strong>this-is-a-subdomain.mail.com</strong>".<br/>If you want to block entire top-level-domains, you\'ll have to use "<strong>**</strong>". I.e. "<strong>**.tk</strong>" will match all domains ending with "<strong>.tk</strong>".<br/>You can see further examples on our list of free email address provider domains in the blacklists section.', 'last-email-address-validator' );
                    _e( '<br/>Be careful to not over do any kind of matching with wildcards.<br/>We urge you to extensively test whether email addresses would get matched or not with the test option <a href="#test_email_address">at the very top</a> of the settings page.', 'last-email-address-validator' );
                ?>
            <h3><?php
                    _e( '<strong>Wildcard syntax for recipient names:</strong>', 'last-email-address-validator' ); ?></h3>
                <?php
                    _e( 'You can use asterisks "<strong>*</strong>" as wildcards in recipient names as well. It stands for zero up to any amount of characters. I.e. "<strong>*spammer*</strong>" matches all recipient names containing the word "<strong>spammer</strong>". It matches "<strong>all-spammers-go</strong>" or just "<strong>spammer</strong>". <strong>mailfrom*</strong>" matches all recipient names starting with "<strong>mailfrom</strong>". I.e. "<strong>mailfrom</strong>", "<strong>mailfroma</strong>", "<strong>mailfromme</strong>", etc. You can place the asterisk anywhere. I.e. "<strong>*spam*from*</strong>" matches "<strong>spamfrom</strong>" as well as "<strong>all-spam-from-me</strong>".<br/>You can see plenty examples on our list of role-based recipient names in the blacklists section. These are mostly trailing "*", so that we don\'t match too many recipient names.', 'last-email-address-validator' );
                    _e( '<br/>Be careful to not over do any kind of matching with wildcards.<br/>We urge you to extensively test whether email addresses would get matched or not with the test option <a href="#test_email_address">at the very top</a> of the settings page.', 'last-email-address-validator' );
                ?>
            <h3><?php
                    _e( '<strong>Wildcard syntax for email address:</strong>', 'last-email-address-validator' ); ?></h3>
                <?php
                    _e( 'Wildcards are NOT available for email addresses as of now. If there is a real usecase for this, feel free to send us a <a href="#feature_requests">feature request</a>.', 'last-email-address-validator' );
                ?>

            <h3><?php
                    _e( '<strong>What are the different parts of an email address:</strong>', 'last-email-address-validator' ); ?></h3>
                <?php
                    _e( 'Of course you know what an email generally looks like.<br/>&nbsp;&nbsp;&nbsp;<strong>recipient-name</strong>@<strong>domain</strong>.<strong>tld</strong><br/>But do you really understand its different parts?<br/>An email address consists of 3 parts with delimiters in between them.', 'last-email-address-validator' );
                ?>
                <ol>
                    <li>
                        <?php _e( 'Recipient name', 'last-email-address-validator' ); ?>
                    </li>
                    <li>
                        <?php _e( 'Domain', 'last-email-address-validator' ); ?>
                    </li>
                    <li>
                        <?php _e( 'Top-level domain (tld)', 'last-email-address-validator' ); ?>
                    </li>
                </ol>
                <?php
                    _e( 'Let\'s use a physical world analogy for these elements of an email address. For this, we have to start at the 3rd part of an email address.<br/><br/>The <strong>top-level domain</strong> part usually represents a country or an organizational type. And in the beginning of the internet there were (aside from some top-level domains like .com, .net, .org, .mil, .edu ...) indeed mostly country domains. Today there are more than 1,500 top-level domains, which gets more and more confusing. But essentially top-level domains are still more or less describing geography, organizational types and more and more lifestyle. There are new top-level domains that are up to 18 charactes long and if you include non-aasci TLDs, they are up to 24 characters long. The current valid list of top-level domains is available at <a href="https://data.iana.org/TLD/tlds-alpha-by-domain.txt" target="_blank">iana.org</a>.<br/>A somewhat current list of how many domains are registered with each top-level domain is available at <a href="https://research.domaintools.com/statistics/tld-counts/" target="_blank">domaintools.com</a>.<br/>For the sake of our analogy, let\'s pretend top-level domains are describing a type of building i.e. simple houses, company buildings, private mansions, public buildings, condo buildings, appartment buildings etc.<br/><br/>The <strong>domain</strong> part is the equivalent of a specific building or house of the general type defined by the top-level domain. The house or building has one or multiple mailboxes. Each mailbox represents a real life person, an entire household, a company, a department and so on.<br/><br/>A <strong>recipient name</strong> is a name on one of the mailboxes of the house. And there can be multiple names on one mailbox.<br/>In this analogy a mailbox is an email account. An email account can have multiple recipient names. Just like a real life mailbox labelled "XYZ family" will receive all mail addressed to any of the XYZ family members, email accounts can have so called aliases. There is usually one "main" or "real" recipient name but additionally there can be "alias" recipient names. For instance companies tend to have a generic main recipient name syntax like this: first.last@company.com<br/>beyond this they tend to also have aliases like f.last@company.com, firstlast@company.com, fl@company.com, first@company, last@company.com etc. You get the picture.', 'last-email-address-validator' );
                ?>

            <a name="faq-recipient-name"></a>
            <h3><?php
                    _e( '<strong>What is a "recipient name":</strong>', 'last-email-address-validator' ); ?></h3>
                <?php
                    _e( 'A Recipient name is the part of an email that is in front of the "@" sign. It is also called "local part". This part defines the concrete mailbox an email gets received by. The mailbox might also be reachable under aliases for the "main" recipient name.', 'last-email-address-validator' );
                ?>

            <a name="faq-recipient-name-catch-all-syntax"></a>
            <h3><?php
                    _e( '<strong>What does "recipient name catch-all syntax"</strong> mean?', 'last-email-address-validator' ); ?></h3>
                <?php
                    _e( 'Email address service providers like <strong>gmail.com</strong> and others allow users to place a "+" sign after their actual recipient name and append whatever string they want as long as the recipient name\'s total length doesn\'t exceed 64 characters.  If your email address is "<strong>tester.testing@gmail.com</strong>" you are allowed to use the following email addresses as well and they will all be delivered into your mailbox: "<strong>tester.testing+domain1@gmail.com</strong>", "<strong>tester.testing+newsletter.xyz@gmail.com</strong>", "<strong>tester.testing+website.signup.for.lottery@gmail.com</strong>" etc.<br/>This is a very easy way for users to differentiate between where and what they signed up for or subscribed to. This allows users to "cloak" their "main" email address. Well - at least a tiny bit. This gives users an infinite amount of email addresses, which sometimes makes it easy for leechers to sign up to free or freemium offers multiple times. You might wan\'t to disallow this, if it interferes with your business model', 'last-email-address-validator' );
                ?>


            <a name="feature_requests"></a>
            <br/><br/><br/>
            <h1><?php _e('Feature Requests', 'last-email-address-validator' ); ?></h1>
            <?php
                _e( 'If you look for more supported plugins or an extension of the base functionality of how we validate and filter email addresses, we at <a href="', 'last-email-address-validator' );
                echo ( $this->central::$PLUGIN_WEBSITE );
                _e( '" target="_blank">smings</a> (website will be online soon) are always happy to optimize <br/> LEAV - Last Email Address Validator to help you to protect your non-renewable lifetime even better. <br/>Just shoot us an email to ', 'last-email-address-validator' );
                echo( '<a href="mailto:' . $this->central::$PLUGIN_CONTACT_EMAIL . '">' . $this->central::$PLUGIN_CONTACT_EMAIL . '</a>.' );
            ?>
            <a name="help"></a>
            <br/><br/>
            <h1><?php _e( 'Help us help you!', 'last-email-address-validator' ); ?></h1>
            <?php
                _e( 'Lastly - if LEAV - Last Email Address Validator delivers substancial value to you, i.e. saving<br/> lots of your precious non-renewable lifetime by filtering out tons of <br/>spam attempts, please show us your appreciation and consider a <strong>', '' );
                echo( '<a href="' . $this->central::$PLUGIN_ONETIME_DONATION_LINK .'" target="_blank">' );
                _e( 'one-time donation</a></strong><br/>or become a patreon on our patreon page at <strong>', 'last-email-address-validator' );
                echo( '<a href="' . $this->central::$PLUGIN_PATREON_LINK . '" target="_blank">' );
                _e( 'patreon.com/smings</a></strong><br/>We appreciate your support and send you virtual hugs and good karma points.<br/>Thank you and enjoy LEAV', 'last-email-address-validator' ) 
            ?>
        </div>
        <div class="wrap">
            <a name="stats"></a>
            <h1><?php _e( 'Statistics', 'last-email-address-validator' ) ?></h1>
            <div class="card">
                <p>
                    <?php echo sprintf(_e( 'Version', 'last-email-address-validator' ) . ": <strong>%s</strong>", $this->central::$PLUGIN_VERSION ) ?>&nbsp;|
                    <?php _e( 'LEAV prevented <strong>', 'last-email-address-validator' );
                          _e( $this->central::$OPTIONS["spam_email_addresses_blocked_count"] );
                           _e( '</strong> SPAM email address attempts so far.', 'last-email-address-validator' );
                          ?>
                </p>
                <p>
                    <a href="<?php echo( $this->central::$PLUGIN_DOCUMENTATION_WEBSITE ) ?>"><?php _e( 'Documentation', 'last-email-address-validator' ) ?></a>&nbsp;|
                    <a href="<?php echo( $this->central::$PLUGIN_BUGREPORTS_WEBSITE ) ?>"><?php _e( 'Bugs', 'last-email-address-validator' ) ?></a>
                    Both will be available soon.
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

            $value = stripslashes( $value );
            $value = rtrim($value);
            $value = preg_replace( "/\r/", '', $value);
            if( $key == 'test_email_address' )
            {
                $this->leav->reuse( $value );
                $this->central::$OPTIONS['test_email_address'] = $value;
                continue;
            }

            // we only look at defined keys who's values have changed
            if(   ! array_key_exists( $key, $this->central::$OPTIONS )
                || $this->central::$OPTIONS[$key] == $value
            )
                continue;

            // First we validate all radio button fields
            if(    in_array( $key, $this->central::$RADIO_BUTTON_FIELDS )
                && $this->validate_radio_button_form_fields($key, $value)
            )
                continue;

            elseif( in_array( $key, $this->central::$TEXT_FIELDS ) )
            {
                if( $this->leav->sanitize_and_validate_text( $value ) )
                {
                    $this->central::$OPTIONS[$key] = $value;
                    $this->add_update_notification_for_form_field($key);
                }
                else
                    $this->add_error_notification_for_form_field($key);
                continue;
            }

            elseif( $key == 'wp_email_domain' )
            {
                if(    empty( $value )
                    && $this->central::$OPTIONS['simulate_email_sending'] == 'no'
                )
                {
                    $this->central::$OPTIONS[$key] = $value;
                    $this->add_update_notification_for_form_field($key);
                }
                elseif(    empty( $value )
                        && $this->central::$OPTIONS['simulate_email_sending'] == 'yes'
                )
                    $this->add_error_notification_for_form_field($key);
                elseif( $this->leav->sanitize_and_validate_domain( $value ) )
                {
                    $this->central::$OPTIONS[$key] = $value;
                    $this->add_update_notification_for_form_field($key);
                }
                else
                    $this->add_error_notification_for_form_field($key);
                continue;

            }

            elseif( in_array( $key, $this->central::$INTEGER_GEZ_FIELDS ) )
            {
                if( preg_match( $this->central::$INTEGER_GEZ_REGEX, $value ) )
                {
                    $this->central::$OPTIONS[$key] = $value;
                    $this->add_update_notification_for_form_field($key);
                }
                else
                    $this->add_error_notification_for_form_field($key);
            }

            elseif(    in_array( $key, $this->central::$DOMAIN_LIST_FIELDS )
                    || in_array( $key, $this->central::$EMAIL_LIST_FIELDS  )
                    || in_array( $key, $this->central::$RECIPIENT_NAME_FIELDS )
            )
            {
                $sanitized_internal_values = array();
                $lines = preg_split("/[\r\n]+/", $value, -1, PREG_SPLIT_NO_EMPTY);
                $value = '';
                $has_errors = false;
                if( in_array( $key, $this->central::$DOMAIN_LIST_FIELDS ) )
                {
                    $sanitized_internal_values['domains'] = array();
                    $sanitized_internal_values['regexps'] = array();
                }
                elseif( in_array( $key, $this->central::$RECIPIENT_NAME_FIELDS ) )
                {
                    $sanitized_internal_values['recipient_names'] = array();
                    $sanitized_internal_values['regexps'] = array();
                }
                foreach( $lines as $id => $line )
                {
                    if( $this->leav->is_comment_line( $line ) )
                    {
                        $value .= $line . "\n";
                        continue;
                    }

                    $original_line = $line;
                    if(    in_array( $key, $this->central::$DOMAIN_LIST_FIELDS )
                        && $this->leav->sanitize_and_validate_domain_internally( $line )
                    )
                    {
                        $value .= $line . "\n";
                        if( preg_match ( $this->central::$DOMAIN_REGEX, $line ) )
                            array_push( $sanitized_internal_values['domains'], $line );
                        else
                        {
                            $pattern = '/' . preg_replace( "/\*/", '[a-z0-9-]*', $line ) . '/';
                            array_push( $sanitized_internal_values['regexps'], $pattern );
                        }
                    }

                    elseif( in_array( $key, $this->central::$EMAIL_LIST_FIELDS )
                        && $this->leav->sanitize_and_validate_email_address( $line )
                    )
                    {
                        $value .= $line . "\n";
                        array_push( $sanitized_internal_values, $line );
                    }

                    // ----- now we have to check the recipient names
                    //
                    elseif(    in_array( $key, $this->central::$RECIPIENT_NAME_FIELDS )
                            && $this->leav->sanitize_and_validate_recipient_name_internally( $line )
                    )
                    {
                        $value .= $line . "\n";
                        if( $this->leav->line_contains_wildcard( $line ) )
                            array_push( $sanitized_internal_values['regexps'], $line );
                        else
                            array_push( $sanitized_internal_values['recipient_names'], $line );
                    }

                    else
                    {
                        if( ! $has_errors )
                        {
                            $this->add_error_notification_for_form_field($key);
                            $has_errors = true;
                        }

                        // ----- when we end up here, we have to autocorrect the line
                        if( in_array( $key, $this->central::$RECIPIENT_NAME_FIELDS ) )
                        {
                            $corrected_line = $original_line;
                            if( preg_match( "/\+/", $corrected_line ) )
                                $corrected_line = array_shift( explode( '+', $corrected_line ) );
                            $corrected_line = preg_replace( "/[^a-z]/", '',  $corrected_line );
                            $line = __('# Next line\'s value was automatically corrected/normalized', 'last-email-address-validator' ) . "\n" . "# " . $original_line . "\n" . $corrected_line;
                            $value .= $line . "\n";
                            array_push( $sanitized_internal_values, $corrected_line );
                        }
                        // ----- here we just comment out the errors for domains and email addresses
                        else
                        {
                            $line = __('# Next line\'s value is invalid', 'last-email-address-validator' ) . "\n". "# " . $original_line;
                            $value .= $line . "\n";
                        }
                    }

                }

                // cutting of a trainling whitespaces
                $value = rtrim($value);
                $this->central::$OPTIONS[$key] = $value;
                $this->add_update_notification_for_form_field($key);

                # now we cut of the trailing "_string"
                $list_key = substr( $key, 0, -7);
                $this->central::$OPTIONS[$list_key] = $sanitized_internal_values;
            }
        }

        # if there are no errors, we update the options
        if( empty( $this->error_notice ) )
        {
            update_option($this->central::$OPTIONS_NAME, $this->central::$OPTIONS);
            $this->central::$OPTIONS = get_option( $this->central::$OPTIONS_NAME );
        }
    }


    private function validate_radio_button_form_fields( string &$key, string &$value ) : bool
    {
        if( in_array( $value, $this->central::$RADIO_BUTTON_VALUES ) )
        {
            $this->central::$OPTIONS[$key] = $value;
            $this->add_update_notification_for_form_field($key);
            return true;
        }
        else
            $this->add_error_notification_for_form_field($key);
        return false;
    }

    private function add_update_notification_for_form_field( string &$field_name ) : bool
    {

        // ----- Allow recipient name catch-all syntax --------------------------------------------
        //
            if( $field_name == 'allow_recipient_name_catch_all_email_addresses')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'allowing recipient name catch-all syntax.<br/>', 'last-email-address-validator' );

        // ----- Email Domain --------------------------------------------------
        //
        elseif( $field_name == 'wp_email_domain')
            $this->update_notice .= __( 'Updated the email domain for simulating the sending of emails.<br/>', 'last-email-address-validator' );

        // ----- Whitelists ----------------------------------------------------
        //
        elseif( $field_name == 'use_user_defined_domain_whitelist')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'using the user-defined domain whitelist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_domain_whitelist_string')
            $this->update_notice .= __( 'Updated the user-defined domain whitelist.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_user_defined_email_whitelist')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'using the user-defined email address whitelist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_email_whitelist_string')
            $this->update_notice .= __( 'Updated the user-defined email address whitelist.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_user_defined_recipient_name_whitelist')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'using the user-defined recipient name whitelist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_recipient_name_whitelist_string')
            $this->update_notice .= __( 'Updated the user-defined recipient name whitelist.<br/>', 'last-email-address-validator' );
        // ----- Blacklists ----------------------------------------------------
        //
        elseif( $field_name == 'use_user_defined_domain_blacklist')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'using the user-defined domain blacklist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_domain_blacklist_string')
            $this->update_notice .= __( 'Updated the user-defined domain blacklist.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_user_defined_email_blacklist')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'using the user-defined email address blacklist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_email_blacklist_string')
            $this->update_notice .= __( 'Updated the user-defined email address blacklist.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_user_defined_recipient_name_blacklist')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'using the user-defined recipient name blacklist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_recipient_name_blacklist_string')
            $this->update_notice .= __( 'Updated the entries of the user-defined recipient name blacklist.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_role_based_recipient_name_blacklist')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'using the role-based recipient name blacklist.<br/>', 'last-email-address-validator' );

        // ----- Disposable Email Address Blocking -----------------------------
        //
        elseif( $field_name == 'block_disposable_email_address_services')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'blocking email addresses from disposable email address services.<br/>', 'last-email-address-validator' );

        // ----- Simulate Email Sending ----------------------------------------
        //
        elseif( $field_name == 'simulate_email_sending')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'simulating email sending.<br/>', 'last-email-address-validator' );

        // ----- Catch-all domain ----------------------------------------
        //
        elseif( $field_name == 'allow_catch_all_domains')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'allowing catch-all domains.<br/>', 'last-email-address-validator' );

        // ----- Pingbacks / Trackbacks ----------------------------------------
        //
        elseif( $field_name == 'accept_pingbacks')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'accepting pingbacks.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'accept_trackbacks')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'accepting trackbacks.<br/>', 'last-email-address-validator' );

        // ------ Validation of functions / plugins switches ---
        //
        elseif( $field_name == 'validate_wp_standard_user_registration_email_addresses')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'validating WordPress\'s user registration email addresses.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_wp_comment_user_email_addresses')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'validating WordPress\'s commentator email addresses.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_woocommerce_email_fields')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'validating WooCommerce email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_cf7_email_fields')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'validating Contact Form 7 email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_wpforms_email_fields')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'validating WPforms email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_ninja_forms_email_fields')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'validating Ninja Forms email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_mc4wp_email_fields')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'validating Mailchimp for WordPress (MC4WP) email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_formidable_forms_email_fields')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'validating Formidable Forms email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_kali_forms_email_fields')
            $this->update_notice .= __( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  __( 'validating Kali Forms email fields.<br/>', 'last-email-address-validator' );


        // ------ Custom error message override fields -------------------------
        //
        elseif( $field_name == 'cem_email_address_syntax_error')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'email address syntax errors.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_recipient_name_catch_all_email_address_error')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'recipient name catch-all errors.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_email_domain_is_blacklisted')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'blacklisted email domains.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_email_domain_is_on_free_email_address_provider_domain_list')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'email domains on the free email address provider domain list.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_email_address_is_blacklisted')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'blacklisted email addresses.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_recipient_name_is_blacklisted')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'recipient name is on blacklist error message.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_recipient_name_is_role_based')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'role-based recipient names.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_email_domain_has_no_mx_record')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'email domains without MX records.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_email_domain_is_on_dea_blacklist')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'disposable email addresses (DEA).<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_simulated_sending_of_email_failed')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'errors during simulating sending an email.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_email_from_catch_all_domain')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'email addresses from catch-all domains.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'cem_general_email_validation_error')
            $this->update_notice .= __( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . __( 'general email validation errors.<br/>', 'last-email-address-validator' );

        // ------ Main Menu Use & Positions -------------------
        //
        elseif( in_array( $field_name, array( 'use_main_menu', 'main_menu_position', 'settings_menu_position' ) ) )
            $this->update_notice .= __( 'Changed the display location of the LEAV menu item. You have to hard-reload  this page before the change takes effect.<br/>', 'last-email-address-validator' );
        else
            $this->update_notice .= __( 'Updated the settings for field <strong>', 'last-email-address-validator' ) . $field_name . '</strong><br/>';

        return true;
    }


    private function add_error_notification_for_form_field( string &$field_name )
    {

        // ----- Allow recipient name catch-all syntax --------------------------------------------
        //
           if( $field_name == 'allow_recipient_name_catch_all_email_addresses' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'allowing recipient name catch-all syntax.<br/>', 'last-email-address-validator' );

        // ----- Email Domain --------------------------------------------------
        //
        elseif( $field_name == 'wp_email_domain' )
            $this->error_notice .= __( 'Error while trying to update the email domain for simulating the sending of emails. The email domain can\'t be empty while simulated email sending is activate.<br/>', 'last-email-address-validator' );

        // ----- Whitelists ----------------------------------------------------
        //
        elseif( $field_name == 'use_user_defined_domain_whitelist' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'using the user-defined domain whitelist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_domain_whitelist_string' )
            $this->error_notice .= __( 'Error! One or more entered domains in the user-defined domain whitelist are invalid. Look at the comments in the field and correct your input.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_user_defined_email_whitelist' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'using the user-defined email address whitelist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_email_whitelist_string' )
            $this->error_notice .= __( 'Error! One or more entered email addresses in the user-defined email address whitelist are invalid. Look at the comments in the field and correct your input.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_user_defined_recipient_name_whitelist' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'using the user-defined recipient name whitelist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_recipient_name_whitelist_string' )
            $this->error_notice .= __( 'Error! One or more entered recipient names in the user-defined recipient name whitelist are invalid. Look at the comments in the field and correct your input.<br/>', 'last-email-address-validator' );

        // ----- Blacklists ----------------------------------------------------
        //
        elseif( $field_name == 'use_user_defined_domain_blacklist' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'using the user-defined domain blacklist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_domain_blacklist_string' )
            $this->error_notice .= __( 'Error! One or more entered domains in the user-defined domain blacklist are invalid. Look at the comments in the field and correct your input.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_user_defined_email_blacklist' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'using the user-defined email address blacklist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_email_blacklist_string' )
            $this->error_notice .= __( 'Error! One or more entered email addresses in the user-defined email address blacklist are invalid. Look at the comments in the field and correct your input.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_user_defined_recipient_name_blacklist')
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' .  __( 'using the user-defined recipient name blacklist.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'user_defined_recipient_name_blacklist_string')
            $this->error_notice .= __( 'Error while trying to update the entries of the user-defined recipient name blacklist.<br/>', 'last-email-address-validator' );

        elseif( $field_name == 'use_role_based_recipient_name_blacklist')
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' .  __( 'using the role-based recipient name blacklist.<br/>', 'last-email-address-validator' );

        // ----- Disposable Email Address Blocking -----------------------------
        //
        elseif( $field_name == 'block_disposable_email_address_services' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'blocking email addresses from disposable email address services.<br/>', 'last-email-address-validator' );

        // ----- Simulate Email Sending ----------------------------------------
        //
        elseif( $field_name == 'simulate_email_sending' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'simulating email sending.<br/>', 'last-email-address-validator' );

        // ----- Catch-all domain ----------------------------------------
        //
        elseif( $field_name == 'allow_catch_all_domains' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'allowing catch-all domains.<br/>', 'last-email-address-validator' );

        // ----- Pingbacks / Trackbacks ----------------------------------------
        //
        elseif( $field_name == 'accept_pingbacks' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'accepting pingbacks.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'accept_trackbacks' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'accepting trackbacks.<br/>', 'last-email-address-validator' );

        // ------ Validation of functions / plugins switches ---
        //
        elseif( $field_name == 'validate_wp_standard_user_registration_email_addresses' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'validating WordPress\'s user registration email addresses.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_wp_comment_user_email_addresses' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'validating WordPress\'s commentator email addresses.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_woocommerce_email_fields' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'validating WooCommerce email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_cf7_email_fields' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'validating Contact Form 7 email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_wpforms_email_fields' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'validating WPforms email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_ninja_forms_email_fields' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'validating Ninja Forms email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_mc4wp_email_fields' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'validating Mailchimp for WordPress (MC4WP) email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_formidable_forms_email_fields' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'validating Formidable Forms email fields.<br/>', 'last-email-address-validator' );
        elseif( $field_name == 'validate_kali_forms_email_fields' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'validating Kali Forms email fields.<br/>', 'last-email-address-validator' );

        // ------ Custom error message override fields -------------------------
        //



        // ------ Main Menu Use & Positions -------------------
        //
        elseif( $field_name == 'use_main_menu' )
            $this->error_notice .= __( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . __( 'the display location of the LEAV menu item (main menu or settings menu.<br/>', 'last-email-address-validator' );

        elseif( in_array( $field_name, array( 'main_menu_position', 'settings_menu_position' ) ) )
            $this->error_notice .= __( 'Error! The values for the LEAV menu position within the main menu or the settings menu have to be numbers in between 0-999.<br/>', 'last-email-address-validator' );

        else
            $this->error_notice .= __( 'Error while trying to update the settings for field <strong>', 'last-email-address-validator' ) . $field_name . '</strong><br/>';
    }

}
?>