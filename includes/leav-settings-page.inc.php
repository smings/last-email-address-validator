<?php
defined( 'ABSPATH' ) or die( 'Nice try! Go away!' );
require_once( 'leav-central.inc.php' );

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
        if( $this->central::$OPTIONS[ 'use_main_menu' ] == 'yes' )
        {
            add_menu_page( $this->central::$PLUGIN_MENU_NAME, $this->central::$PLUGIN_MENU_NAME_SHORT, 'activate_plugins', basename( __FILE__, ".php" ), array( $this, 'display_settings_page' ), $this->central::$MENU_INLINE_ICON, intval( $this->central::$OPTIONS[ 'main_menu_position' ] ) );
        }
        else
            add_options_page( $this->central::$PLUGIN_MENU_NAME, $this->central::$PLUGIN_MENU_NAME, 'activate_plugins', basename( __FILE__, ".php" ), array( $this, 'display_settings_page' ), intval( $this->central::$OPTIONS[ 'settings_menu_position' ] ) );

    }

    public function add_global_warning_wp_email_domain_not_detected() : void
    {
?>
        <div id="setting-error-settings_updated" class="notice notice-warning is-dismissible">
            <p>
                 <?php
                    /* translators: %1$stext%2$s turns into <strond>text</strong> */
                    echo( sprintf( nl2br( esc_html__( 'LEAV - Last Email Address Validator could not automatically detect your email domain.
This usually only happens in development or staging environments.
Please go to %1$sLEAV\'s settings%2$s page and enter an email domain under which your WordPress instance is reachable.' , 'last-email-address-validator' ) ), '<a href="' . esc_url( $this->central::$PLUGIN_SETTING_PAGE ) . '">', '</a>' ) );
                    ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'last-email-address-validator' ) ?>.</span>
            </button>
        </div>
<?php
    }




    public function display_settings_page()
    {

        // even if we just load the settings page without any changes by the user, we look at
        // the wp_email_domain and the simulation settings
        if (   isset( $_POST[ 'leav_options_update_type' ] )
            || (    empty( $this->central::$OPTIONS[ 'wp_email_domain' ] )
                 && $this->central::$OPTIONS[ 'simulate_email_sending' ] == 'yes'
               )
        )
        {
            if( isset( $_POST["leav_options_update_type"] ) )
                $this->sanitize_submitted_settings_form_data();

            if(
                   empty( $this->central::$OPTIONS[ 'wp_email_domain' ] )
                && $this->central::$OPTIONS[ 'simulate_email_sending' ] == 'yes'
            )
                $this->warning_notice = 
                    /* translators: %1$stext%3$s and %2$stex%3$s are translated into <a href>text</a> */
                    sprintf( nl2br( esc_html__( 'Could not automatically determine the email domain for simulated sending of emails. %1$sPlease enter your email domain below%3$s or %2$sdeactivate the simulated email sending%3$s to permanently dismiss this warning message.', 'last-email-address-validator' ) ), '<a href="#email_domain">', '<a href="#ses">', '</a>' ) ;

            if( ! empty( $this->warning_notice ) )
            {
?>
        <div id="setting-error-settings_updated" class="notice notice-warning is-dismissible">
            <p>
                <?php echo $this->warning_notice ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'last-email-address-validator' ) ?>.</span>
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
                <span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'last-email-address-validator' ) ?>.</span>
            </button>
        </div>
<?php
            }
            elseif( ! empty( $this->error_notice ) )
            {
                $this->error_notice .= esc_html( 'Your changes have not been saved! Correct your input and click on "Save Changes" again.', 'last-email-address-validator' );
?>
        <div id="setting-error-settings_updated" class="notice notice-error is-dismissible">
            <p>
                <?php echo $this->error_notice ?>
            </p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'last-email-address-validator' ) ?>.</span>
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
            <h1 style="display: flex;  align-items: center; color:#89A441; font-size: 30px;">
                <img width="75px" src="<?php echo( esc_url( plugin_dir_url( __FILE__ ) . '../' . $this->central::$SETTINGS_PAGE_LOGO_URL ) ); ?>"/>
                &nbsp;&nbsp;&nbsp;<strong><?php
                    echo( esc_html( $this->central::$PLUGIN_DISPLAY_NAME_LONG ) ); ?></strong></h1>
                 <h1><?php esc_html_e( 'Settings', 'last-email-address-validator' ); ?></h1>
                 <br/>
                <div>
                    <span>
                        <strong>
                            <?php esc_html_e( 'Quick Navigation', 'last-email-address-validator' ); ?>
                        </strong>
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#test_email_address">
                            <?php esc_html_e( 'Test Email Address Vaildation', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#email_domain">
                            <?php esc_html_e( 'Email Domain', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#allow_recipient_name_catch_all">
                            <?php esc_html_e( 'Recipient Name Catch All', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#whitelists">
                            <?php esc_html_e( 'Whitelists', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#blacklists">
                            <?php esc_html_e( 'Blacklists', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#dea">
                            <?php esc_html_e( 'Disposable Email Address Blocking', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#ses">
                            <?php esc_html_e( 'Simulate Email Sending', 'last-email-address-validator' ) ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#cad">
                            <?php esc_html_e( 'Catch-all domains', 'last-email-address-validator' ) ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#functions_plugins">
                            <?php esc_html_e( 'LEAV-validated Functions / Plugins', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#ping_track_backs">
                            <?php esc_html_e( 'Pingbacks / Trackbacks', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#custom_messages">
                            <?php esc_html_e( 'Custom Error Messages', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#menu_location">
                            <?php esc_html_e( 'LEAV Menu Item Location', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <div>
                    <span>
                        <a href="#faq">
                            <?php esc_html_e( 'FAQ', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#feature_requests">
                            <?php esc_html_e( 'Feature Requests', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#help">
                            <?php esc_html_e( 'Help Us, Help You', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                    <span>
                        <a href="#stats">
                            <?php esc_html_e( 'Statistics / Version', 'last-email-address-validator' ); ?>
                        </a>
                        &nbsp;&nbsp;&nbsp;
                    </span>
                </div>
                <a name="test_email_address"></a>
                <br/><br/>
            <form name="leav_options" method="post">
                <input type="hidden" name="leav_options_update_type" value="update" />

                <h2><?php esc_html_e( 'Test Current Email Address Validation Settings', 'last-email-address-validator' ) ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email address to test', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="test_email_address" placeholder="<?php 
                                    esc_attr_e( 'email.address.to@test.com', 'last-email-address-validator' ); 
                                    ?>" 
                                    value="<?php 
                                    if( isset( $_POST[ 'test_email_address' ] ) )
                                        echo( esc_attr( $_POST[ 'test_email_address' ] ) );
                                ?>" size="40" />
                            </label>
                            <?php

                                if( ! empty( $_POST[ 'test_email_address' ] ) )
                                {
                                    $sanitized_test_email_address = sanitize_text_field( $_POST[ 'test_email_address' ] );
                                    if( ! $this->leav_plugin->validate_email_address( $sanitized_test_email_address, false ) )
                                    {
                            ?>
                            <p>
                                <span style="color:#a00">
                                    <strong>
                                        <?php esc_html_e( 'Validation result for email address ', 'last-email-address-validator' ); ?>
                                    </strong>
                                </span>
                                <span>
                                    "<?php echo( esc_html( $sanitized_test_email_address ) ); ?>"
                                </span>
                                <span style="color:#a00">
                                    <strong>
                                        <?php esc_html_e( 'is negative!', 'last-email-address-validator' ); ?>
                                    </strong>
                                </span>
                            </p>
                            <p>
                                <span style="color:#a00">
                                    <strong>
                                        <?php esc_html_e( 'ERROR TYPE:', 'last-email-address-validator' ); ?>
                                    </strong>
                                </span>
                                <span>
                                    "<?php esc_html_e( $this->leav_plugin->get_email_validation_error_type() ) ; ?>"
                                </span>
                            </p>
                            <p>
                                <span style="color:#a00">
                                    <strong>
                                        <?php esc_html_e( 'ERROR MESSAGE:', 'last-email-address-validator' ); ?>
                                    </strong>
                                </span>
                                <span>
                                    "<?php echo( esc_html( $this->leav_plugin->get_email_validation_error_message() ) ); ?>"
                                </span>
                            </p>
                                <?php
                                    }
                                    else
                                    {
                                ?>   
                            <p>
                                <span style="color:#89A441">
                                    <strong>
                                        <?php esc_html_e( 'Validation result for email address', 'last-email-address-validator' ); ?>
                                    </strong>
                                </span>
                                <span>
                                    "<?php echo( esc_html( $sanitized_test_email_address ) ); ?>"
                                </span>
                                <span  style="color:#89A441">
                                    <strong>
                                        <?php esc_html_e( 'is positive!', 'last-email-address-validator' ); ?>
                                    </strong>
                                </span>
                            </p>
                            <p>
                                <span style="color:#89A441">
                                        <?php esc_html_e( 'The email address got successfully validated. It is good to go!', 'last-email-address-validator' ); ?>
                                </span>
                            </p>
                                <?php                                    }
                                    }
                                ?>
                            <p class="description">
                                <?php echo ( sprintf( nl2br( esc_html__( 'Test any email address against LEAV\'s current settings.
No emails will be sent out or saved anywhere.
Feel free to adjust the settings to your individual needs. We encourage you to do thorough testing.', 'last-email-address-validator' ) ) ) ); ?>
                            </p>

                        </td>
                    </tr>
                </table>


                <a name="email_domain"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Test Email Address', 'last-email-address-validator' ); 
                    ?>" />
                </p>

                <h2><?php esc_html_e( 'Email Domain', 'last-email-address-validator' ) ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php 
                                        esc_html_e( 'Email domain for simulated email sending to entered email addresses', 'last-email-address-validator' ); 
                                        ?>:
                        </th>
                        <td>
                            <label>
                                <input name="wp_email_domain" type="text" size="40" value="<?php 
                                    echo esc_attr( $this->central::$OPTIONS["wp_email_domain"] ); 
                                    ?>" placeholder="<?php 
                                    esc_attr_e( 'your-wp-email-domain.com', 'last-email-address-validator' ); 
                                    ?>" />
                            </label>
                            <p class="description">
                                <?php 
                                esc_html_e( 'The Email domain is used for simulating the sending of an email from "no-reply@', 'last-email-address-validator' );
                                ?><strong><?php
                                    if( ! empty( $this->central::$OPTIONS["wp_email_domain"] ) )
                                        echo( esc_html( $this->central::$OPTIONS["wp_email_domain"] ) );
                                    else
                                        esc_html_e( 'your-wp-email-domain.com', 'last-email-address-validator' );
                                    ?></strong>" <?php 
                                    esc_html_e( 'to the entered email address, that gets validated. ', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <?php 
                                    echo( sprintf( nl2br( esc_html__( 'Please make sure you enter the email domain that you use for sending emails from your WordPress instance. 
If the email domain doesn\'t point to your WordPress instance\'s IP address, simulating the sending of emails might fail.
This is usually only the case in development or test environments.', 'last-email-address-validator' ) ) ) );
                                ?>
                                <br/>
                                <?php
                                    /* translators: %1$stext%2$s is translated into <a href>text</a> */
                                    echo( sprintf( esc_html__( 'In this case you might have to disable the %1$ssimulated email sending%2$s.', 'last-email-address-validator' ), '<a href="#ses">', '</a>' ) );
                                ?>
                                <br/>
                                <strong>
                                <?php
                                    esc_html_e( 'Default: Automatically detected WordPress Domain.', 'last-email-address-validator' );
                                ?>
                                </strong>
                            </p>
                        </td>
                    </tr>
                </table>
                <a name="allow_recipient_name_catch_all"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>


                <h1>
                    <?php
                        esc_html_e( 'Filter Function Settings', 'last-email-address-validator' );
                    ?>
                </h1>
                <?php
                    echo( sprintf( nl2br( esc_html__( 'From here onwards you can configure the filter/validation steps. 
You can find an overview and description of the filter/validation steps in ', 'last-email-address-validator' ) ) ) );
                ?>
                <a href="#faq"><?php esc_html_e( 'our FAQ', 'last-email-address-validator' ); ?></a>

                <h2><?php esc_html_e( 'Recipient Name Catch-All Syntax', 'last-email-address-validator' ) ?></h2>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Allow recipient name catch-all syntax', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="allow_recipient_name_catch_all_email_addresses" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'allow_recipient_name_catch_all_email_addresses' ] == 'yes' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="allow_recipient_name_catch_all_email_addresses" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'allow_recipient_name_catch_all_email_addresses' ] == 'no' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                echo( sprintf( nl2br( esc_html__( 'Allow recipient name (the part of an email address before the "@") catch-all syntax. google and other email address providers allow you to extend the recipient name part of an email address with a "+" followed by whatever text. The only limitation is a maximum length of 64 characters for the recipient name.', 'last-email-address-validator' ) ) ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( '"my.name+anything@gmail.com"', 'last-email-address-validator' );
                                    ?>
                                </strong>
                                <?php
                                    esc_html_e( 'is the same as ', 'last-email-address-validator' );
                                ?>
                                <strong>
                                    <?php
                                        esc_html_e( '"my.name@gmail.com"', 'last-email-address-validator' );
                                    ?>
                                </strong> 
                                <?php
                                    echo( sprintf( nl2br( esc_html__( 'for google. 
This allows users to "cloak" their "main" email address, which is usually used to differentiate where and what the user signed up for.
You can choose to allow this or block such email addresses.', 'last-email-address-validator' ) ) ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>
                </table>


                <a name="whitelists">
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

                <h2></a><?php esc_html_e( 'Whitelists', 'last-email-address-validator' ) ?></h2>
                <?php 
                    /* translators: %1$stext%2$s is translated into <a href>text</a> */
                    echo( sprintf( nl2br( esc_html__( 'Any email address that gets whitelisted will skip the corresponding blacklist filter. This doesn\'t mean that it doesn\'t get filtered out by other filters. 
I.e. if a domain gets whitelisted, but at the same time it is a catch-all domain and you disallow catch-all domains, all email addresses from this domain will still get rejected.
Look at our %1$sFAQ%2$s for detailed information on how the filter/validation process works.', 'last-email-address-validator' ) ), '<a href="#faq">', '</a>' ) ); 
                ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <a name="dwl"/>
                        <th scope="row"><?php esc_html_e( 'Use Domain Whitelist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'use_user_defined_domain_whitelist' ] == 'yes' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_whitelist" type="radio" value="no" <?php if( $this->central::$OPTIONS["use_user_defined_domain_whitelist"] == 'no' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Email addresses from the listed domains will be accepted without further domain blacklist  checks (if active).', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <?php 
                                    /* translators: %1$stext%2$s is translated into <a href>text</a> */
                                    echo( sprintf( nl2br( esc_html__( 'For information on how to use wildcards, see our %1$sFAQ entry%2$s.', 'last-email-address-validator' ) ), '<a href="#faq-wildcards">', '</a>' ) );
                                ?>
                                <br/>
                                <strong>
                                <?php
                                    esc_html_e( 'Enter one domain per line.', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <?php
                                    esc_html_e( 'Default: No', 'last-email-address-validator' );
                                ?>
                                </strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_whitelist_string" name="user_defined_domain_whitelist_string" rows="7" cols="40" placeholder="<?php esc_html_e( 'your-whitelisted-domain-1.com
your-whitelisted-domain-2.com', 'last-email-address-validator' ); 
                                                ?>" ><?php 
                                    echo esc_textarea( $this->central::$OPTIONS[ 'user_defined_domain_whitelist_string' ] ); 
                                ?></textarea><br/>
                                <?php
                                    esc_html_e( 'Number of entries: ', 'last-email-address-validator' );

                                    $size = 0;
                                    if( is_array( $this->central::$OPTIONS[ 'user_defined_domain_whitelist' ] ) )
                                    {
                                        if( array_key_exists( 'domains', $this->central::$OPTIONS[ 'user_defined_domain_whitelist' ] ) )
                                            $size += sizeof( 
                                                    $this->central::$OPTIONS[ 'user_defined_domain_whitelist' ][ 'domains' ] 
                                            );
                                        if( array_key_exists( 'regexps', $this->central::$OPTIONS[ 'user_defined_domain_whitelist' ] ) )
                                            $size += sizeof( 
                                                $this->central::$OPTIONS[ 'user_defined_domain_whitelist' ][ 'regexps' ] 
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
                        <th scope="row"><?php esc_html_e( 'Use email address whitelist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="yes" <?php if( $this->central::$OPTIONS["use_user_defined_email_whitelist"] == 'yes' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_whitelist" type="radio" value="no" <?php if( $this->central::$OPTIONS["use_user_defined_email_whitelist"] == 'no' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    echo( sprintf( nl2br( esc_html__( 'Email addresses on this list will be accepted without further email address blacklist checks (if active).
Unlike with domains and recipient names, you can\'t use wildcards for email addresses.', 'last-email-address-validator' ) ) ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Enter one email address per line.', 'last-email-address-validator' );
                                    ?>
                                </strong>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: No', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_whitelist_string" name="user_defined_email_whitelist_string" rows="7" cols="40" placeholder="<?php esc_html_e( 'your.whitelisted@email-address-1.com
your.whitelisted@email-address-2.com', 'last-email-address-validator' );
                                                ?>"><?php 
                                    echo esc_textarea( $this->central::$OPTIONS["user_defined_email_whitelist_string"] );
                                ?></textarea><br/>
                                <?php
                                    esc_html_e( 'Number of entries: ', 'last-email-address-validator' );
                                    echo( 
                                        strval( 
                                            sizeof( 
                                                $this->central::$OPTIONS[ 'user_defined_email_whitelist' ]
                                            )
                                        ) 
                                    );
                                ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <a name="rnwl"/>
                        <th scope="row"><?php esc_html_e( 'Use recipient name whitelist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_recipient_name_whitelist" type="radio" value="yes" <?php if( $this->central::$OPTIONS["use_user_defined_recipient_name_whitelist"] == 'yes' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_recipient_name_whitelist" type="radio" value="no" <?php if( $this->central::$OPTIONS["use_user_defined_recipient_name_whitelist"] == 'no' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s is translated into <a href>text</a> */
                                    echo( sprintf( nl2br( sprintf( esc_html__( 'Recipient names on this list will be accepted without further recipient name blacklist checks, either user-defined and/or role-based (if active).
Entered recipient names will automatically be stripped of any non-letter (a-z) characters except for wildcards.
For information on how to use wildcards, see our %1$sFAQ entry%2$s.', 'last-email-address-validator' ), '<a href="#faq-wildcards">', '</a>' ) ) ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Enter one recipient name per line.', 'last-email-address-validator' );
                                    ?>
                                </strong>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: No', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_recipient_name_whitelist_string" name="user_defined_recipient_name_whitelist_string" rows="7" cols="40" placeholder="<?php esc_html_e( 'your-whitelisted-recipient-name-1
your-whitelisted-recipient-name-2', 'last-email-address-validator' ); 
                                                            ?>"><?php 
                            echo esc_textarea( $this->central::$OPTIONS["user_defined_recipient_name_whitelist_string"] );
                        ?></textarea><br/>
                                <?php
                                    esc_html_e( 'Number of entries: ', 'last-email-address-validator' );
                                    $size = 0;
                                    if( is_array( $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist' ] ) )
                                    {
                                        if( array_key_exists( 'recipient_names', $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist' ] ) )
                                            $size += sizeof( 
                                                    $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist' ][ 'recipient_names' ] 
                                            );
                                        if( array_key_exists( 'regexps', $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist' ] ) )
                                            $size += sizeof( 
                                                $this->central::$OPTIONS[ 'user_defined_recipient_name_whitelist' ][ 'regexps' ] 
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
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

                <h2></a><?php esc_html_e( 'Blacklists', 'last-email-address-validator' ) ?></h2>
                <?php 
                    echo( sprintf( nl2br( esc_html__( 'Any email address that gets matched by a blacklist rule gets rejected, unless it has previously been whitelisted for the blacklist rule. 
If an email address gets matched by a blacklist rule, all subsequent validations get skipped.', 'last-email-address-validator' ) ) ) ); ?><br/>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">

                    <tr>
                        <a name="dbl"/>
                        <th scope="row"><?php esc_html_e( 'Use domain blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="yes" <?php if( $this->central::$OPTIONS["use_user_defined_domain_blacklist"] == 'yes' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_domain_blacklist" type="radio" value="no" <?php if( $this->central::$OPTIONS["use_user_defined_domain_blacklist"] == 'no' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s is translated into <a href>text</a> */
                                    echo( sprintf( nl2br( esc_html__( 'Email addresses from these domains will be rejected (if active).
For information on how to use wildcards, see our %1$sFAQ entry%2$s.', 'last-email-address-validator' ) ), '<a href="#faq-wildcards">', '</a>' ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Enter one domain per line.', 'last-email-address-validator' );
                                    ?>
                                </strong>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: No', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_domain_blacklist_string" name="user_defined_domain_blacklist_string" rows="7" cols="40" placeholder="<?php
                                    esc_html_e( 'your-blacklisted-domain-1.com
your-blacklisted-domain-2.com', 'last-email-address-validator' ); 
                                            ?>"><?php 
                                    echo esc_textarea( $this->central::$OPTIONS["user_defined_domain_blacklist_string"] );
                                ?></textarea><br/>
                                <?php
                                    esc_html_e( 'Number of entries: ', 'last-email-address-validator' );
                                    if( is_array( $this->central::$OPTIONS[ 'user_defined_domain_blacklist' ] ) )
                                    {
                                        if( array_key_exists( 'domains', $this->central::$OPTIONS[ 'user_defined_domain_blacklist' ] ) )
                                            $size += sizeof( 
                                                    $this->central::$OPTIONS[ 'user_defined_domain_blacklist' ][ 'domains' ] 
                                            );
                                        if( array_key_exists( 'regexps', $this->central::$OPTIONS[ 'user_defined_domain_blacklist' ] ) )
                                            $size += sizeof( 
                                                $this->central::$OPTIONS[ 'user_defined_domain_blacklist' ][ 'regexps' ] 
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
                        <th scope="row"><?php esc_html_e( 'Use free email address provider domain blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_free_email_address_provider_domain_blacklist" type="radio" value="yes" <?php if( $this->central::$OPTIONS["use_free_email_address_provider_domain_blacklist"] == 'yes' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_free_email_address_provider_domain_blacklist" type="radio" value="no" <?php if( $this->central::$OPTIONS["use_free_email_address_provider_domain_blacklist"] == 'no' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s is translated into <a href>text</a> */
                                    echo( sprintf( nl2br( esc_html__( 'The list comprises the most common free email address services. If for example you want to enforce business email addresses, you can activate this blacklist feature and reject email addresses from domains on this list.
If you feel that we missed important domains, you can add them on the user-defined domain blacklist above. But please also %1$sinform us%2$s about it. This list is not editable.
If you should wonder why we block the entire top-level-domains .cf, .ga, .gq, .mk and .tk, here is why: these top-level-domains are free of charge and therefore wildy popular with private individuals, that don\'t want to spend anything on a domain. Because of this we treat them like free email address providers. These top-level-domains are almost exclusively registered by individuals and not (relevant) companies.', 'last-email-address-validator' ) ), '<a href="mailto:' . esc_attr( $this->central::$PLUGIN_CONTACT_EMAIL ) . '">', '</a>' ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: No', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="free_email_address_provider_domain_blacklist_string_display_only" name="free_email_address_provider_domain_blacklist_string_display_only" rows="7" cols="40" readonly><?php 
                                                        echo esc_textarea( $this->central::$OPTIONS["free_email_address_provider_domain_blacklist_string"] );
                                                                ?></textarea><br/>
                                <?php
                                    esc_html_e( 'Number of entries: ', 'last-email-address-validator' );
                                    echo( 
                                        strval( 
                                            sizeof( 
                                                $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ][ 'domains' ] 
                                            ) 
                                            +  
                                            sizeof( 
                                                $this->central::$OPTIONS[ 'free_email_address_provider_domain_blacklist' ][ 'regexps' ] 
                                            )
                                        ) 
                                    );
                                ?>
                            </label>
                        </td>
                    </tr>


                    <tr>
                        <a name="ebl"/>
                        <th scope="row"><?php esc_html_e( 'Use email address blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="yes" <?php if( $this->central::$OPTIONS["use_user_defined_email_blacklist"] == 'yes' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_email_blacklist" type="radio" value="no" <?php if( $this->central::$OPTIONS["use_user_defined_email_blacklist"] == 'no' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    echo( sprintf( nl2br( esc_html__( 'Email addresses from this list will be rejected (if active).
Unlike with domains and recipient names, you can\'t use wildcards for email addresses.', 'last-email-address-validator' ) ) ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Enter one email address per line.', 'last-email-address-validator' );
                                    ?>
                                </strong>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: No', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label>
                                <textarea id="user_defined_email_blacklist_string" name="user_defined_email_blacklist_string" rows="7" cols="40" placeholder="<?php esc_html_e( 'blacklisted-email-address-1@domain.com
blacklisted-email-address-2@domain.com', 'last-email-address-validator' ); ?>"><?php 
                                        echo esc_textarea( $this->central::$OPTIONS["user_defined_email_blacklist_string"] );
                                        ?></textarea><br/>
                                <?php
                                    esc_html_e( 'Number of entries: ', 'last-email-address-validator' );
                                    $size = 0;
                                    if( is_array( $this->central::$OPTIONS[ 'user_defined_email_blacklist' ] ) )
                                        $size += sizeof( $this->central::$OPTIONS[ 'user_defined_email_blacklist' ] );
                                    echo( 
                                        strval( $size )
                                    );
                                ?>


                            </label>
                        </td>
                    </tr>


                    <tr>
                        <a name="rnbl"/>
                        <th scope="row"><?php esc_html_e( 'Use recipient name blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_user_defined_recipient_name_blacklist" type="radio" value="yes" <?php if( $this->central::$OPTIONS["use_user_defined_recipient_name_blacklist"] == 'yes' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_user_defined_recipient_name_blacklist" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'use_user_defined_recipient_name_blacklist' ] == 'no' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s is translated into <strong>text</strong> and %3$stext%4$s is translated into <a href...>text</a> */
                                    echo( sprintf( nl2br( esc_html__( 'If activated, email addresses with recipient names (the part before the "@" sign) from the list below, will be rejected. The recipient names will get automatically "collapsed" to only their letters. This means that non-letter characters get stripped from the original recipient name. "%1$sd.e.m.o.123@domain.com%2$s" gets collapsed into "%1$sdemo@domain.com%2$s".
This way, we automatically block role-based recipient names, that are altered with punctuation and non-letter characters.
This list is meant for user-defined additional entries that are not (yet) covered by our built-in role-based recipient name blacklist below.
Entered recipient names will automatically be stripped of any non-letter (a-z) characters except for wildcards.
For information on how to use wildcards, see our %3$sFAQ entry%4$s.', 'last-email-address-validator' ) ), '<strong>', '</strong>', '<a  href="#faq-wildcards">', '</a>' ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Enter one recipient name per line.', 'last-email-address-validator' );
                                    ?>

                                </strong>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: No', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <label>
                                <textarea id="user_defined_recipient_name_blacklist_string" name="user_defined_recipient_name_blacklist_string" rows="7" cols="40" placeholder="<?php esc_html_e( 'blacklisted recipient name 1
blacklisted recipient name 2', 'last-email-address-validator' );
                                                       ?>"><?php 
                                echo esc_textarea( $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist_string' ] );
                                ?></textarea><br/>
                                <?php
                                    esc_html_e( 'Number of entries: ', 'last-email-address-validator' );
                                    $size = 0;
                                    if( is_array( $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist' ] ) )
                                    {
                                        if( array_key_exists( 'recipient_names', $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist' ] ) )
                                            $size += sizeof( 
                                                    $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist' ][ 'recipient_names' ] 
                                            );
                                        if( array_key_exists( 'regexps', $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist' ] ) )
                                            $size += sizeof( 
                                                $this->central::$OPTIONS[ 'user_defined_recipient_name_blacklist' ][ 'regexps' ] 
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
                        <th scope="row"><?php esc_html_e( 'Use role-based recipient name blacklist', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="use_role_based_recipient_name_blacklist" type="radio" value="yes" <?php if( $this->central::$OPTIONS["use_role_based_recipient_name_blacklist"] == 'yes' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="use_role_based_recipient_name_blacklist" type="radio" value="no" <?php if( $this->central::$OPTIONS["use_role_based_recipient_name_blacklist"] == 'no' ) { echo ( 'checked="checked" ' ); } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s is translated into <strong>text</strong> and %3$stext%4$s is translated into <a href...>text</a> */
                                    echo( sprintf( nl2br( esc_html__( 'If activated, email addresses with generic, role-based recipient names (the part before the "@" sign) from the list below, will be rejected. 
The recipient names are validated in their "collapsed" form. This means that all punctuation is stripped from the original recipient name.
"%1$si.n.f.o@domain.com%2$s" gets collapsed into "%1$sinfo@domain.com%2$s" (which is on the list). 
"%1$s123-all-456-employees@domain.com%2$s" gets collapsed into "%1$sallemployees@domain.com%2$s" and so on. Essentially, we strip away all non-letter characters. 
This way, we can block role-based recipient names, that are altered with punctuation.
If the collapsed recipient name is empty, it will also be detected as role-based recipient name. In this case it contains only digits and non-letter characters, which we consider a role-based recipient name.
This list is not editable. If you want to block other recipient names than on this list, please use the recipient name blacklist above.
If we block too much for you, you can add recipient names to the whitelist above.
If you think we missed important common role-based recipient names, %3$splease let us know%4$s.', 'last-email-address-validator' ) ), '<strong>', '</strong>', '<a href="mailto:leav@sming.com">', '</a>' ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: No', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <label>
                                <textarea id="display_only" name="display_only" rows="7" cols="40" readonly><?php 
                                    echo esc_textarea( $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist_string' ] ); 
                                ?></textarea><br/>
                                <?php
                                    esc_html_e( 'Number of entries: ', 'last-email-address-validator' );
                                    echo( 
                                        strval( 
                                            sizeof( 
                                                $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ][ 'recipient_names' ] 
                                            ) 
                                            +  
                                            sizeof( 
                                                $this->central::$OPTIONS[ 'role_based_recipient_name_blacklist' ][ 'regexps' ] 
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
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

                <h2><a name="dea"></a><?php esc_html_e( 'Disposable Email Address Blocking', 'last-email-address-validator' ) ?></h2>


                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Use disposable email address service (DEA) blacklist', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="block_disposable_email_address_services" type="radio" value="yes" <?php if( $this->central::$OPTIONS["block_disposable_email_address_services"] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="block_disposable_email_address_services" type="radio" value="no" <?php if( $this->central::$OPTIONS["block_disposable_email_address_services"] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s is translated into <a href>text</q> */
                                    echo( sprintf( nl2br( esc_html__(  'If activated email adresses from disposable email address services (DEA) i.e. mailinator.com, maildrop.cc, guerrillamail.com and many more will be rejected. 
LEAV manages a comprehensive list of DEA services that is frequently updated. We block the underlying MX server domains and IP addresses - not just the website domains. This bulletproofs the validation against domain aliases and makes it extremely reliable, since it attacks DEAs at their core. 
If you found a DEA service that doesn\'t get blocked yet, please %1$scontact us%2$s.', 'last-email-address-validator' ) ), '<a href="mailto:' . esc_attr( $this->central::$PLUGIN_CONTACT_EMAIL ). '">', '</a>' ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>

                </table>

                <a name="ses"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

                <h2><?php esc_html_e( 'Simulate Email Sending', 'last-email-address-validator' ) ?></h2>


                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Simulate Email Sending', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="simulate_email_sending" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'simulate_email_sending' ] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="simulate_email_sending" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'simulate_email_sending' ] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s turns into <strond>text</strong> */
                                    echo( sprintf( nl2br( esc_html__( 'If activated LEAV tries to simulate the sending of an email. For this we connect to one of the MX servers and test if it would accept an email from your email domain (see above) to the email address that gets validated. 
If the used email domain doesn\'t point to your WordPress instance\'s IP address, this might fail. This is usually only the case in development or test environments. 
Test this with a working email address. If it gets rejected, you might have to deactivate this option.
%1$sThis option should always be active in production environments.%2$s', 'last-email-address-validator' ) ), '<strong>', '</strong>' ) ); 
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>

                </table>


                <a name="cad"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

                <h2><?php esc_html_e( 'Allow catch-all domains', 'last-email-address-validator' ) ?></h2>


                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Accept email addresses from catch-all domains', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="allow_catch_all_domains" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'allow_catch_all_domains' ] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="allow_catch_all_domains" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'allow_catch_all_domains' ] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s turns into <strond>text</strong> */
                                    echo( sprintf( nl2br( esc_html__( 'Here you can control whether to accept email addresses from domains, that allow arbritary recipient names. These are domains that allow arbritary recipient names like %1$sdtras657td8giuy23gtf7e3628@catch-all-domain.com%2$s.
For whom might this be important? I.e. if you have a website with a free trial, you might want to make it a bit harder for leechers to get an unlimited amount of free accounts. 
Of course users with their own domains can create an unlimited amount of email accounts, but by not allowing catch-all domains, it makes it harder for them. 
I use catch-all domains myself and there is generally nothing wrong about it. You\'ll have to decide for yourself, whether this is important for you or not. 
Just so you know: %1$seven gmail.com allows any recipient name%2$s. 
If you set this option to "No", you should also reject email addresses from free email address providers above.', 'last-email-address-validator' ) ), '<strong>', '</strong>' ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>

                </table>



                <a name="functions_plugins"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

                <h1><?php esc_html_e( 'LEAV-validated Functions / Plugins', 'last-email-address-validator' ) ?></h1>
                <?php esc_html_e( 'Control which functions and plugins will get validated by LEAV\'s algorithm.', 'last-email-address-validator' ) ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'WordPress user registration', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <?php if( get_option("users_can_register") == 1 && $this->central::$OPTIONS["validate_wp_standard_user_registration_email_addresses"] == "yes" ) : ?>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="yes" <?php if( $this->central::$OPTIONS["validate_wp_standard_user_registration_email_addresses"] == 'yes' ) { echo 'checked="checked" '; } ?>/><?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?></label>
                            <label>
                                <input name="validate_wp_standard_user_registration_email_addresses" type="radio" value="no" <?php if( $this->central::$OPTIONS["validate_wp_standard_user_registration_email_addresses"] == 'no' ) { echo 'checked="checked" '; } ?>/><?php esc_html_e( 'No', 'last-email-address-validator' ) ?></label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s is translated into <a href>text</q> */
                                    echo( sprintf( nl2br( esc_html__( 'This validates all registrants email address\'s that register through WordPress\'s standard user registration. (%1$sSettings -> General%2$s)', 'last-email-address-validator' ) ), '<a href="/wp-admin/options-general.php" target="_blank" target="_blank">', '</a>' ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( get_option("users_can_register") == 0 || $this->central::$OPTIONS["validate_wp_standard_user_registration_email_addresses"] == "no" )
                                  {
                                    /* translators: %1$stext%2$s is translated into <a href>text</q> */
                                    echo( sprintf( nl2br( esc_html__( 'WordPress\'s built-in user registration is currently deactivated. (%1$sSettings -> General%2$s)', 'last-email-address-validator' ) ), '<a href="/wp-admin/options-general.php" target="_blank" target="_blank">', '</a>' ) );
                                  }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'WordPress comments', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="yes" <?php if( $this->central::$OPTIONS["validate_wp_comment_user_email_addresses"] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_wp_comment_user_email_addresses" type="radio" value="no" <?php if( $this->central::$OPTIONS["validate_wp_comment_user_email_addresses"] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    /* translators: %1$stext%2$s is translated into <a href>text</q> */
                                    echo( sprintf( nl2br( esc_html__( 'This validates all (not logged in) commentator\'s email address\'s that comment through WordPress\'s standard comment functionality. (%1$sSettings -> Discussion%2$s)', 'last-email-address-validator' ) ), '<a href="/wp-admin/options-discussion.php" target="_blank">', '</a>' ) );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">WooCommerce:</th>
                        <td>
                            <?php if( is_plugin_active( "woocommerce/woocommerce.php" ) ) : ?>
                            <label>
                                <input name="validate_woocommerce_email_fields" type="radio" value="yes" <?php if( $this->central::$OPTIONS["validate_woocommerce_email_fields"] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_woocommerce_email_fields" type="radio" value="no" <?php if( $this->central::$OPTIONS["validate_woocommerce_email_fields"] == 'no' ) { echo 'checked="checked" '; } ?>/><?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    esc_html_e( 'Validate all WooCommerce email addresses during registration and checkout.', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "woocommerce/woocommerce.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> ';
                                      esc_html_e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>

                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Contact Form 7:</th>
                        <td>
                            <?php if( is_plugin_active( "contact-form-7/wp-contact-form-7.php" )  ) : ?>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="yes" <?php if( $this->central::$OPTIONS["validate_cf7_email_fields"] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_cf7_email_fields" type="radio" value="no" <?php if( $this->central::$OPTIONS["validate_cf7_email_fields"] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    esc_html_e( 'Validate all Contact Form 7 email address fields.', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "contact-form-7/wp-contact-form-7.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7</a> ';
                                      esc_html_e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row">WPForms (lite):</th>
                        <td>
                            <?php if( is_plugin_active( "wpforms-lite/wpforms.php" ) || is_plugin_active( "wpforms/wpforms.php" )  ) : ?>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="yes" <?php if( $this->central::$OPTIONS["validate_wpforms_email_fields"] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_wpforms_email_fields" type="radio" value="no" <?php if( $this->central::$OPTIONS["validate_wpforms_email_fields"] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    esc_html_e( 'Validate all WPForms email address fields.', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "wpforms-lite/wpforms.php" ) && ! is_plugin_active( "wpforms/wpforms.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">WPForms </a>';
                                      esc_html_e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Ninja Forms:</th>
                        <td>
                            <?php if( is_plugin_active( "ninja-forms/ninja-forms.php" )  ) : ?>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="yes" <?php if( $this->central::$OPTIONS["validate_ninja_forms_email_fields"] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_ninja_forms_email_fields" type="radio" value="no" <?php if( $this->central::$OPTIONS["validate_ninja_forms_email_fields"] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    esc_html_e( 'Validate all Ninja Forms email address fields.', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <?php
                                    esc_html_e( 'The names of the fields that will get validated by LEAV must contain "email", "e-mail", "e.mail", "E-Mail"... (case insensitive)', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "ninja-forms/ninja-forms.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/ninja-forms/" target="_blank">Ninja Forms </a>';
                                      esc_html_e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Mailchimp for WordPress (MC4WP):</th>
                        <td>
                            <?php if( is_plugin_active( "mailchimp-for-wp/mailchimp-for-wp.php" )  ) : ?>
                            <label>
                                <input name="validate_mc4wp_email_fields" type="radio" value="yes" <?php if( $this->central::$OPTIONS["validate_mc4wp_email_fields"] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_mc4wp_email_fields" type="radio" value="no" <?php if( $this->central::$OPTIONS["validate_mc4wp_email_fields"] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    esc_html_e( 'Validate all MC4WP email address fields.', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <?php
                                    esc_html_e( 'The names of the fields that will get validated by LEAV must contain "email", "e-mail", "e.mail", "E-Mail"... (case insensitive)', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "mailchimp-for-wp/mailchimp-for-wp.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/mailchimp-for-wp/" target="_blank">Mailchimp for WordPress (MC4WP) </a>';
                                      esc_html_e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Formidable Forms:</th>
                        <td>
                            <?php if( is_plugin_active( "formidable/formidable.php" )  ) : ?>
                            <label>
                                <input name="validate_formidable_forms_email_fields" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'validate_formidable_forms_email_fields' ] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_formidable_forms_email_fields" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'validate_formidable_forms_email_fields' ] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    esc_html_e( 'Validate all Formidable Forms email address fields.', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "formidable/formidable.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/formidable/" target="_blank">Formidable Forms</a> ';
                                      esc_html_e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Kali Forms:</th>
                        <td>
                            <?php if( is_plugin_active( "kali-forms/kali-forms.php" )  ) : ?>
                            <label>
                                <input name="validate_kali_forms_email_fields" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'validate_kali_forms_email_fields' ] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_kali_forms_email_fields" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'validate_kali_forms_email_fields' ] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    esc_html_e( 'Validate all Kali Forms email address fields.', 'last-email-address-validator' )
                                ?>
                                <br/>
                                <?php
                                    esc_html_e( 'The names of the fields that will get validated by LEAV must contain "email", "e-mail", "e.mail", "E-Mail"... (case insensitive)', 'last-email-address-validator' );
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "kali-forms/kali-forms.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/kali-forms/" target="_blank">Kali Forms</a> ';
                                      esc_html_e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Elementor Pro:</th>
                        <td>
                            <?php if( is_plugin_active( "elementor-pro/elementor-pro.php" )  ) : ?>
                            <label>
                                <input name="validate_elementor_pro_email_fields" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'validate_elementor_pro_email_fields' ] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_elementor_pro_email_fields" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'validate_elementor_pro_email_fields' ] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    esc_html_e( 'Validate all Elementor Pro email address fields.', 'last-email-address-validator' )
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "elementor-pro/elementor-pro.php" ) )
                                  {
                                      echo '<a href="https://wordpress.org/plugins/elementor/" target="_blank">Elementor Pro</a> ';
                                      esc_html_e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Gravity Forms:</th>
                        <td>
                            <?php if( is_plugin_active( "gravityforms/gravityforms.php" )  ) : ?>
                            <label>
                                <input name="validate_gravity_forms_email_fields" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'validate_gravity_forms_email_fields' ] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="validate_gravity_forms_email_fields" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'validate_gravity_forms_email_fields' ] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <?php
                                    esc_html_e( 'Validate all Graviy Forms email address fields.', 'last-email-address-validator' )
                                ?>
                                <br/>
                                <strong>
                                    <?php
                                        esc_html_e( 'Default: Yes', 'last-email-address-validator' );
                                    ?>
                                </strong>
                            </p>
                            <?php endif;
                                  if( ! is_plugin_active( "gravityforms/gravityforms.php" ) )
                                  {
                                      echo '<a href="https://www.gravityforms.com/" target="_blank">Gravity Forms</a> ';
                                      esc_html_e( 'not found in list of active plugins', 'last-email-address-validator' );
                                  }
                            ?>
                        </td>
                    </tr>

                </table>


                <a name="ping_track_backs"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

                <h2><a name="pingbacks"></a><?php esc_html_e( 'Pingbacks / Trackbacks', 'last-email-address-validator' ) ?></h2>
                <?php 
                    /* translators: %1$stext%2$s turns into <strond>text</strong> */
                    echo( sprintf( nl2br( esc_html__( 'Pingbacks and trackbacks can\'t be validated because they don\'t come with an email address, that could be run through our validation process.
Therefore %1$spingbacks and trackbacks pose a certain spam risk%2$s.  But they are also free marketing.
By default we therefore accept them.', 'last-email-address-validator' ) ), '<strong>', '</strong>' ) ); 
                ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Accept pingbacks', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'accept_pingbacks' ] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="accept_pingbacks" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'accept_pingbacks' ] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <strong><?php esc_html_e( 'Default:', 'last-email-address-validator' ) ?> <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Accept trackbacks', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'accept_trackbacks' ] == 'yes' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?>
                            </label>
                            <label>
                                <input name="accept_trackbacks" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'accept_trackbacks' ] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'No', 'last-email-address-validator' ) ?>
                            </label>
                            <p class="description">
                                <strong><?php esc_html_e( 'Default:', 'last-email-address-validator' ) ?> <?php esc_html_e( 'Yes', 'last-email-address-validator' ) ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

                <a name="custom_messages"></a>
                <h1><?php esc_html_e( 'Custom Error Messages', 'last-email-address-validator' ) ?></h1>
                <?php echo( sprintf( nl2br( esc_html__( 'If you want to override the default validation error messages or if you want to translate them without having to go through .po files, you can replace the default validation error messages below. 
The placeholder texts are the default error messages. Overwrite them to use your custom validation error messages. 
Delete the field\'s contents for using the defaults again.
In multi-language sites, you will have to do the translations within the .po files that come with the plugin. 
Of course you can do this with the help of plugins like WPML and others as well.', 'last-email-address-validator' ) ) ) ); ?>

                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email address contains invalid characters error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_address_contains_invalid_characters" type="text" size="80" value="<?php 
                                    echo esc_attr( $this->central::$OPTIONS[ 'cem_email_address_contains_invalid_characters' ]); 
                                    ?>"  placeholder="<?php 
                                    echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'email_address_contains_invalid_characters' ] ); 
                                    ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email address syntax error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_address_syntax_error" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_email_address_syntax_error' ] ); ?>"  placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'email_address_syntax_error' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email address recipient name catch-all syntax error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_recipient_name_catch_all_email_address_error" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_recipient_name_catch_all_email_address_error' ]); ?>"  placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'recipient_name_catch_all_email_address_error' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email domain blacklisted error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_domain_is_blacklisted" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_email_domain_is_blacklisted' ]); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'email_domain_is_blacklisted' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email domain is on list of free email address provider domains error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_domain_is_on_free_email_address_provider_domain_list" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_email_domain_is_on_free_email_address_provider_domain_list' ] ); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'email_domain_is_on_free_email_address_provider_domain_list' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email address is blacklisted error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_address_is_blacklisted" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_email_address_is_blacklisted' ] ); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'email_address_is_blacklisted' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Recipient name is blacklisted error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_recipient_name_is_blacklisted" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_recipient_name_is_blacklisted' ] ); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'recipient_name_is_blacklisted' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Recipient name is role-based error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_recipient_name_is_role_based" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_recipient_name_is_role_based' ] ); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'recipient_name_is_role_based' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'No MX (Mail eXchange) server found error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_domain_has_no_mx_record" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_email_domain_has_no_mx_record' ] ); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'email_domain_has_no_mx_record' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email address from disposable email address service error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_domain_is_on_dea_blacklist" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_email_domain_is_on_dea_blacklist' ] ); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'email_domain_is_on_dea_blacklist' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Simulating sending an email failed error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_simulated_sending_of_email_failed" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_simulated_sending_of_email_failed' ] ); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'simulated_sending_of_email_failed' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Catch-all domains not allowed error message', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_email_from_catch_all_domain" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_email_from_catch_all_domain' ] ); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'email_from_catch_all_domain' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row"><?php esc_html_e( 'General email validation error', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="cem_general_email_validation_error" type="text" size="80" value="<?php echo esc_attr( $this->central::$OPTIONS[ 'cem_general_email_validation_error' ] ); ?>" placeholder="<?php echo esc_attr( $this->central::$VALIDATION_ERROR_LIST_DEFAULTS[ 'general_email_validation_error' ] ); ?>"/>
                            </label>
                        </td>
                    </tr>

                 </table>

                <a name="menu_location"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

                <h1><?php esc_html_e( 'LEAV Menu Item Location', 'last-email-address-validator' ) ?></h1>
                <?php echo( sprintf( nl2br( esc_html__( 'We believe that LEAV will provide great value for you for as long as you use it. But after setting it up, you don\'t have to worry about it anymore. 
We understand that after having set up LEAV you might want to move the LEAV menu item to a different location in the main menu or move it away from the main menu into the settings menu. 
Here you can control where to place it.
The lower the number for a location, the higher up in the menu the LEAV menu item will be displayed. 
We allow locations in between 0-999.
After changing the values, you\'ll have to reload the page.', 'last-email-address-validator' ) ) ) ); 
                ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Show LEAV menu item in main menu / settings menu', 'last-email-address-validator' ) ?>:</th>
                        <td>
                            <label>
                                <?php esc_html_e( 'Show in ', 'last-email-address-validator' ); ?> &nbsp;&nbsp;
                                <input name="use_main_menu" type="radio" value="yes" <?php if( $this->central::$OPTIONS[ 'use_main_menu' ] == 'yes' ) { echo 'checked="checked" '; } ?> />
                                <?php esc_html_e( 'main menu &nbsp;&nbsp;or', 'last-email-address-validator' ) ?> &nbsp;&nbsp;
                            </label>
                            <label>
                                <input name="use_main_menu" type="radio" value="no" <?php if( $this->central::$OPTIONS[ 'use_main_menu' ] == 'no' ) { echo 'checked="checked" '; } ?>/>
                                <?php esc_html_e( 'settings menu', 'last-email-address-validator' ) ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'LEAV menu item location (main menu)', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="main_menu_position" type="number" size="3" value="<?php echo ( $this->central::$OPTIONS[ 'main_menu_position' ]); ?>" min="0" max="999" required />
                            </label>
                            <p class="description">
                                <?php 
                                    esc_html_e( 'Values in between 0-999 are allowed.', 'last-email-address-validator' );
                                    echo( '<br/>' );
                                    esc_html_e( '0 = top menu position', 'last-email-address-validator' );
                                    echo( '<br/><strong>' );
                                    esc_html_e( 'Default: ', 'last-email-address-validator' );
                                    echo( '24</strong>' );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'LEAV menu item location (settings menu)', 'last-email-address-validator' ); ?>:</th>
                        <td>
                            <label>
                                <input name="settings_menu_position" type="number" size="3" value="<?php echo ( $this->central::$OPTIONS[ 'settings_menu_position' ]); ?>" min="0" max="999" required />
                            </label>
                            <p class="description">
                                <?php 
                                    esc_html_e( 'Values in between 0-999 are allowed.', 'last-email-address-validator' );
                                    echo( '<br/>' );
                                    esc_html_e( '0 = top menu position', 'last-email-address-validator' );
                                    echo( '<br/><strong>' );
                                    esc_html_e( 'Default: ', 'last-email-address-validator' );
                                    echo( '3</strong>' );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <a name="faq"></a>
                <p class="submit">
                    <input class="button button-primary" type="submit" id="options_update" name="submit" value="<?php 
                        esc_attr_e( 'Save Changes', 'last-email-address-validator' );
                    ?>" />
                </p>

            </form>
            <h1><?php esc_html_e( 'FAQ - Frequently Asked Questions', 'last-email-address-validator' ); ?></h1>
            <h2><?php esc_html_e( 'How exactly does LEAV validate email addresses?', 'last-email-address-validator' ); ?></h2>
            <?php
                /* translators: %1$s gets replaced with the plugin "long name", %2$sby %3$ssmings%4$s turns into <i>by <a href...>smings</a></i> */
                echo( sprintf( nl2br( esc_html__( '%1$s %2$sby %3$ssmings%4$s validates email addresses of the supported WordPress functions and plugins in the following multi-step filter/validation process:', 'last-email-address-validator' ) ), $this->central::$PLUGIN_DISPLAY_NAME_FULL, '<i>', '<a href="'.  $this->central::$PLUGIN_WEBSITE . '" target="_blank">', '</a></i>' ) );
            ?>


            <ol>
                <li>
                    <strong>
                        <?php esc_html_e( 'Email Address Syntax Validation (always active)', 'last-email-address-validator' ); ?>
                    </strong>
                    <br/>
                    <?php echo( sprintf( nl2br( esc_html__( 'Checks if the email address is syntactically correct. This acts as a backup check for the plugin\'s checks. 
Some plugins only have a frontend based email syntax check. LEAV\'s implementation is a solid regular expression based server-side check. 
We wouldn\'t even need it, but use it for performance reasons to filter out wrong emails without further checking', 'last-email-address-validator' ) ) ) ); 
                    ?>
                </li>
                <li>
                    <strong>
                        <?php
                            esc_html_e( 'Recipient Name Catch-All Syntax (optional)', 'last-email-address-validator' );
                        ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */    
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'allow_recipient_name_catch_all_email_addresses' ), '</strong>', '<a href="#allow_recipient_name_catch_all">', '</a>' ) );
                        ?>
                    <br/>
                    <?php
                        echo( sprintf( nl2br( esc_html__( 'Control if you want to filter out email addresses with a recipient name catch-all syntax. 
For more information what a recipient name catch-all syntax is, please check our FAQ entry below.', 'last-email-address-validator' ) ) ) );
                    ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Domain Whitelist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'use_user_defined_domain_whitelist' ), '</strong>', '<a href="#dwl">', '</a>' ) );
                        ?>
                    <br/>
                    <?php
                        /* translators: '%1$stext"%2$s turns into <a href...>text</a> */
                        echo( sprintf( nl2br( esc_html__( 'Filters against the user-defined email domain whitelist (if activated).
Use this whitelist to override potential false positives from extensive (wildcard) domain blacklist rules. 
Whenever an email address gets matches by this whitelist, the domain blacklist check gets skipped.
We kindly ask you to %1$sinform us%2$s about wrongfully blacklisted domains, so that we can correct any errors as soon as possible.', 'last-email-address-validator' ) ),  '<a href="mailto:' . esc_attr( $this->central::$PLUGIN_CONTACT_EMAIL ) . '">', '</a>' ) );
                    ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Email Address Whitelist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'use_user_defined_email_whitelist' ), '</strong>', '<a href="#ewl">', '</a>' ) );
                        ?>
                    <br/>
                    <?php 
                        echo( sprintf( nl2br( esc_html__( 'Filters against the user-defined email whitelist (if activated)
If you need to override specific email addresses that would otherwise get filtered out by the blacklist filters.' , 'last-email-address-validator' ) ) ) ); ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Recipient Name Whitelist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'use_user_defined_recipient_name_whitelist' ), '</strong>', '<a href="#rnwl">', '</a>' ) );
                        ?>
                    <br/>
                    <?php 
                        echo( sprintf( nl2br( esc_html__( 'Filters against the user-defined recipient name whitelist (if activated)
If you need to override specific recipient names that would otherwise get filtered out 
by either the user-defined recipient name blacklist or the role-based recipient name blacklist. 
If a recipient name gets matched by this whitelist, both recipient name blacklist checks get skipped.' , 'last-email-address-validator' ) ) ) ); 
                    ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Domain Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'use_user_defined_domain_blacklist' ), '</strong>', '<a href="#dbl">', '</a>' ) );
                        ?>
                    <br/>
                    <?php esc_html_e( 'Filters against the user-defined email domain blacklist (if activated).' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Free Email Address Provider Domain Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'use_free_email_address_provider_domain_blacklist' ), '</strong>', '<a href="#feapdbl">', '</a>' ) );
                        ?>
                    <br/>
                    <?php 
                        echo( sprintf( nl2br( esc_html__( 'Filters against the built-in free email address provider domain blacklist (if activated).
This list gets updated with new plugin releases.' , 'last-email-address-validator' ) ) ) ); 
                    ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Email Address Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'use_user_defined_email_blacklist' ), '</strong>', '<a href="#ebl">', '</a>' ) );
                        ?>
                    <br/>
                    <?php esc_html_e( 'Filters against the user-defined email address blacklist (if activated).' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Recipient Name Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'use_user_defined_recipient_name_blacklist' ), '</strong>', '<a href="#rnbl">', '</a>' ) );
                        ?>
                    <br/>
                    <?php esc_html_e( 'Filters against the user-defined recipient name blacklist (if activated).' , 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Role-Based Recipient Name Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'use_role_based_recipient_name_blacklist' ), '</strong>', '<a href="#rbrnbl">', '</a>' ) );
                        ?>
                    <br/>
                    <?php esc_html_e( 'Filters against the built-in role-based recipient name blacklist (if activated).' , 'last-email-address-validator' ); ?>
                </li>

                <li>
                    <strong>
                        <?php esc_html_e( 'DNS MX Server Lookup (always active)', 'last-email-address-validator' ); ?>
                    </strong>
                    <br/>
                    <?php esc_html_e( 'Check if the email address\'s domain has a DNS entry with MX records (always)', 'last-email-address-validator' ); ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Disposable Email Address (DEA) Service Blacklist (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'block_disposable_email_address_services' ), '</strong>', '<a href="#dea">', '</a>' ) );
                        ?>
                    <br/>
                    <?php 
                        echo( sprintf( nl2br( esc_html__( 'Filters against the built-in extensive blacklist of disposable email services (if activated).
                            This list gets updated with new plugin releases.', 'last-email-address-validator' ) ) ) ); 
                    ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Simulate Email Sending (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'simulate_email_sending' ), '</strong>', '<a href="#ses">', '</a>' ) );
                        ?>
                    <br/>
                    <?php
                        /* translators: '%1$stext%2$s turns into <a href...>text</a> */
                        echo( sprintf( nl2br( esc_html__( 'Connects to one of the MX servers and simulates the sending of an email 
from %1$sno-reply@%2$s%3$s to the entered email address. No actual email will be sent out. 
This is just LEAV asking the receiving server, if it would accept the email address. 
Then the dialog with the MX server gets terminated without any email being sent. 
It\'s essentially like looking at a house\'s mailboxes and checking if there is a mailbox 
with a specific name on it and if we can open it and see if the letter would fit in without dropping it into the mailbox.', 'last-email-address-validator' ) ), '<strong>', $this->get_wp_email_domain_as_string(), '</strong>' ) );
                    ?>
                </li>
                <li>
                    <strong>
                        <?php esc_html_e( 'Allow Email Addresses from Catch-All Domains (optional)', 'last-email-address-validator' ); ?>
                    </strong>
                        <?php
                            /* translators: '%1$s"%2$s"%3$s - %4$sChange settings%5$s' turns into <strond>"Yes/No"</strong> - <a href...>Change settings</a>. %2$s gets replaced by Yes / No or their translated values */ 
                            echo( sprintf( nl2br( esc_html__( ' - Current setting is %1$s"%2$s"%3$s - %4$sChange settings%5$s', 'last-email-address-validator' ) ), '<strong>', $this->get_option_state( 'allow_catch_all_domains' ), '</strong>', '<a href="#cad">', '</a>' ) );
                        ?>
                    <br/>
                    <?php 
                        echo( sprintf( nl2br( esc_html__( 'If set to "No", this filters out all email addresses that originate from domains that accept emails for ANY recipient name.', 'last-email-address-validator' ) ) ) ); 
                        ?>
                </li>

            </ol>
            <a name="faq-wildcards"></a>
            <br/>
            <h2><?php esc_html_e( 'Can I use wildcards for the whitelists/blacklists?', 'last-email-address-validator' ); ?></h2>
            <?php esc_html_e( 'The short answer is yes and here is how it works', 'last-email-address-validator' ); ?>
            <h3>
                <strong>
                    <?php
                        esc_html_e( 'Wildcard syntax for domains:', 'last-email-address-validator' ); 
                    ?>
                </strong>
            </h3>
                <?php
                    /* translators: '%1$stext%2$s turns into <strong>text</strong> */
                    echo( sprintf( nl2br( esc_html__( 'You can use asterisks "%1$s*%2$s" as wildcards in domain names. It stands for zero up to any amount of characters. 
I.e. "%1$smail4*.com%2$s" matches all emails from domains starting with "%1$smail4%2$s" followed any number of characters and ending in "%1$s.com%2$s". 
In this example "%1$smail4.com%2$s", "%1$smail4i.com%2$s", "%1$smail4me.com%2$s", "%1$smail4myparents.com%2$s" would all be matched.
You can use "*" for entire subdomains and top-level domains (TLDs) (Explanation: subdomain2.subdomain1.domain.tld).
"%1$s*.mail.*%2$s" matches "%1$sa.mail.tk%2$s" or "%1$sthis-is-a-subdomain.mail.com%2$s".
If you want to block entire top-level-domains, you\'ll have to use "%1$s**%2$s" as domain name. I.e. "%1$s**.tk%2$s" will match all domains ending with "%1$s.tk%2$s".
You can see further examples on our list of free email address provider domains in the blacklists section.
', 'last-email-address-validator' ) ), '<strong>', '</strong>' ) );
                    /* translators: '%1$stext%2$s turns into a link */
                    echo( sprintf( nl2br( esc_html__( 'Be careful to not over do any kind of matching with wildcards.
We urge you to extensively test whether email addresses would get matched or not with the test option %1$sat the very top%2$s of the settings page.', 'last-email-address-validator' ) ), '<a href="#test_email_address">', '</a>' ) );

                ?>
            <h3>
                <strong>
                    <?php
                        esc_html_e( 'Wildcard syntax for recipient names:', 'last-email-address-validator' ); ?>
                </strong>
            </h3>
                <?php
                    /* translators: '%1$stext%2$s turns into <strong>text</strong> */
                    echo( sprintf( nl2br( esc_html__( 'You can use asterisks "%1$s*%2$s" as wildcards in recipient names as well. It stands for zero up to any amount of characters. 
I.e. "%1$s*spammer*%2$s" matches all recipient names containing the word "%1$sspammer%2$s". 
It matches "%1$sall-spammers-go%2$s" or just "%1$sspammer%2$s". %1$smailfrom*%2$s" matches all recipient names starting with "%1$smailfrom%2$s". 
I.e. "%1$smailfrom%2$s", "%1$smailfroma%2$s", "%1$smailfromme%2$s", etc. 
You can place the asterisk anywhere. I.e. "%1$s*spam*from*%2$s" matches "%1$sspamfrom%2$s" as well as "%1$sall-spam-from-me%2$s".
You can see plenty examples on our list of role-based recipient names in the blacklists section. 
These are mostly trailing "*", so that we don\'t match too many recipient names.
', 'last-email-address-validator' ) ), '<strong>', '</strong>' ) );
                    /* translators: '%1$stext%2$s turns into a link */
                    echo( sprintf( nl2br( esc_html__( 'Be careful to not over do any kind of matching with wildcards.
We urge you to extensively test whether email addresses would get matched or not with the test option %1$sat the very top%2$s of the settings page.', 'last-email-address-validator' ) ), '<a href="#test_email_address">', '</a>' ) );

                ?>
            <h3>
                <strong>
                    <?php
                    esc_html_e( 'Wildcard syntax for email address:', 'last-email-address-validator' ); ?>
                </strong>
            </h3>
                <?php
                    /* translators: '%1$stext%2$s turns into a link */
                    echo( sprintf( nl2br( esc_html__( 'Wildcards are NOT available for email addresses as of now. 
If there is a real usecase for this, feel free to send us a %1$sfeature request%2$s.', 'last-email-address-validator' ) ), '<a href="#feature_requests">', '</a>' ) );
                ?>
            <h3>
                <strong>
                <?php
                    esc_html_e( 'What are the different parts of an email address:', 'last-email-address-validator' ); ?>
                </strong>
            </h3>
                <?php
                    /* translators: %1$s get replaced with '&nbsp;' and '%2$stext%3$s' turns into '<strong>text</strong>'' */
                    echo( sprintf( nl2br( esc_html__( 'Of course you know what an email generally looks like.
%1$s%1$s%1$s%2$srecipient-name%3$s@%2$sdomain%3$s.%2$stld%3$s
But do you really understand its different parts?
An email address consists of 3 parts with delimiters in between them.', 'last-email-address-validator' ) ), '&nbsp;', '<strong>', '</strong>' ) );
                ?>
                <ol>
                    <li>
                        <?php esc_html_e( 'Recipient name', 'last-email-address-validator' ); ?>
                    </li>
                    <li>
                        <?php esc_html_e( 'Domain', 'last-email-address-validator' ); ?>
                    </li>
                    <li>
                        <?php esc_html_e( 'Top-level domain (tld)', 'last-email-address-validator' ); ?>
                    </li>
                </ol>
                <?php
                    /* translators: '%1$stext%2$s turns into <strong>text</strong> and %3$siana.org%4$s and %5$sdomaintools.com%4$s turn into links*/
                    echo( sprintf( nl2br( esc_html__( 'Let\'s use a physical world analogy for these elements of an email address. For this, we have to start at the 3rd part of an email address.

The %1$stop-level domain%2$s part usually represents a country or an organizational type. And in the beginning of the internet there were
aside from some top-level domains like .com, .net, .org, .mil, .edu ...) indeed mostly country domains. 
Today there are more than 1,500 top-level domains, which gets more and more confusing. But essentially top-level domains are still 
more or less describing geography, organizational types and more and more lifestyle. There are new top-level domains that are 
up to 18 charactes long and if you include non-aasci TLDs, they are up to 24 characters long. The current valid list of 
top-level domains is available at %3$siana.org%4$s.
A somewhat current list of how many domains are registered with each top-level domain is available at %5$sdomaintools.com%4$s.
For the sake of our analogy, let\'s pretend top-level domains are describing a type of building i.e. simple houses, 
company buildings, private mansions, public buildings, condo buildings, appartment buildings etc..

The %1$sdomain%2$s part is the equivalent of a specific building or house of the general type defined by 
the top-level domain. The house or building has one or multiple mailboxes. Each mailbox represents a real life person, 
an entire household, a company, a department and so on.

A %1$srecipient name%2$s is a name on one of the mailboxes of the house. And there can be multiple names on one mailbox.
In this analogy a mailbox is an email account. An email account can have multiple recipient names. 
Just like a real life mailbox labelled "XYZ family" will receive all mail addressed to any of the XYZ family members, 
email accounts can have so called aliases. There is usually one "main" or "real" recipient name but additionally 
there can be "alias" recipient names. For instance companies tend to have a generic main recipient name syntax 
like this: first.last@company.com
Beyond this they tend to also have aliases like f.last@company.com, firstlast@company.com, fl@company.com, first@company, 
last@company.com etc. You get the picture.', 'last-email-address-validator' ) ), '<strong>', '</strong>', '<a href="https://data.iana.org/TLD/tlds-alpha-by-domain.txt" target="_blank">', '</a>', '<a href="https://research.domaintools.com/statistics/tld-counts/" target="_blank">' ) );
                ?>

            <a name="faq-recipient-name"></a>
            <h3>
                <strong>
                <?php
                    esc_html_e( 'What is a "recipient name":', 'last-email-address-validator' ); 
                ?>
                </strong>
            </h3>
                <?php
                    echo( sprintf( nl2br( esc_html__( 'A Recipient name is the part of an email that is in front of the "@" sign. It is also called "local part". 
This part defines the concrete mailbox an email gets received by. The mailbox might also be reachable 
under aliases for the "main" recipient name.', 'last-email-address-validator' ) ) ) );
                ?>

            <a name="faq-recipient-name-catch-all-syntax"></a>
            <h3>
                <strong>
                <?php
                    esc_html_e( 'What does "recipient name catch-all syntax" mean?', 'last-email-address-validator' ); 
                ?>
                </strong>
            </h3>
                <?php
                    /* translators: '%1$stext%2$s turns into <strong>text</strong> */
                    echo( sprintf( nl2br( esc_html__( 'Email address service providers like %1$sgmail.com%2$s and others allow users to place a "+" sign after 
their actual recipient name and append whatever string they want as long as the recipient name\'s 
total length doesn\'t exceed 64 characters.  
If your email address is "%1$stester.testing@gmail.com%2$s" you are allowed to use the following 
email addresses as well and they will all be delivered into your mailbox: 
"%1$stester.testing+domain1@gmail.com%2$s", "%1$stester.testing+newsletter.xyz@gmail.com%2$s", "%1$stester.testing+website.signup.for.lottery@gmail.com%2$s" etc.
This is a very easy way for users to differentiate between where and what they signed up for or subscribed to. 
This allows users to "cloak" their "main" email address. Well - at least a tiny bit. This gives users an 
infinite amount of email addresses, which sometimes makes it easy for leechers to sign up to free or 
freemium offers multiple times. You might wan\'t to disallow this, if it interferes with your business model', 'last-email-address-validator' ) ), '<strong>', '</strong>' ) );
                ?>

            <a name="feature_requests"></a>
            <br/><br/><br/>
            <h1><?php esc_html_e( 'Feature Requests', 'last-email-address-validator' ); ?></h1>
            <?php
                /* translators: '%1$stext%2$s turns into a link and %3$s gets replaced with an email link */
                echo( sprintf( nl2br( esc_html__( 'If you look for more supported plugins or an extension of the base functionality of how we validate and filter 
email addresses, we at %1$ssmings%2$s (website will be online soon) are always happy to optimize
LEAV - Last Email Address Validator to help you to protect your non-renewable lifetime even better.
Just shoot us an email to %3$s', 'last-email-address-validator' ) ), '<a href="' . esc_url( $this->central::$PLUGIN_WEBSITE ) . '" target="_blank">', '</a>', '<a href="mailto:' . esc_attr( $this->central::$PLUGIN_CONTACT_EMAIL ). '">' . esc_html( $this->central::$PLUGIN_CONTACT_EMAIL ). '</a>' ) );
            ?>
            <a name="help"></a>
            <br/><br/>
            <h1><?php esc_html_e( 'Help us help you!', 'last-email-address-validator' ); ?></h1>
            <?php
                /* translators: The placeholder get turned into links */
                echo( sprintf( nl2br( esc_html__( 'Lastly - if LEAV - Last Email Address Validator delivers substancial value to you, i.e. saving
lots of your precious non-renewable lifetime by filtering out tons of
spam attempts, please show us your appreciation and consider a %1$s%3$sone-time donation%4$s%2$s
or become a patreon on our patreon page at %1$s%5$spatreon.com/smings%4$s%2$s
We appreciate your support and send you virtual hugs and good karma points.
Thank you and enjoy LEAV', 'last-email-address-validator' ) ), '<strong>', '</strong>', '<a href="' . esc_url( $this->central::$PLUGIN_ONETIME_DONATION_LINK ) .'" target="_blank">', '</a>', '<a href="' . esc_url( $this->central::$PLUGIN_PATREON_LINK ) . '" target="_blank">' ) );
            ?>
        </div>
        <div class="wrap">
            <a name="stats"></a>
            <h1><?php esc_html_e( 'Statistics', 'last-email-address-validator' ) ?></h1>
            <div class="card">
                <p>
                    <?php 
                        /* translators: '%1$s%3$s%2$s turns into <strong>current version number</strong> */
                        echo( sprintf( nl2br( esc_html__( 'Version: %1$s%3$s%2$s', 'last-email-address-validator' ) ), '<strong>', '</strong>', $this->central::$PLUGIN_VERSION ) );
                    ?>
                    &nbsp;|
                    <?php 
                        /* translators: '%1$s%3$s%2$s turns into <strong>current number of prevented spam attemps</strong> */
                        echo( sprintf( nl2br( esc_html__( 'LEAV prevented %1$s%3$s%2$s SPAM email address attempts so far.', 'last-email-address-validator' ) ), '<strong>', '</strong>', esc_html( $this->central::$OPTIONS["spam_email_addresses_blocked_count"] ) ) );
                    ?>
                </p>
                <p>
                    <a href="<?php 
                                echo( esc_url( $this->central::$PLUGIN_DOCUMENTATION_WEBSITE ) ); 
                             ?>" 
                        target="_blank"><?php 
                                            esc_html_e( 'Documentation', 'last-email-address-validator' );
                                        ?></a>
                    &nbsp;|
                    <a href="<?php 
                                echo( esc_url( $this->central::$PLUGIN_BUGREPORTS_WEBSITE ) ); 
                             ?>" 
                        target="_blank"><?php 
                                            esc_html_e( 'Bugs', 'last-email-address-validator' ); 
                                        ?></a>
                    <?php
                        esc_html_e( 'Both will be available soon.', 'last-email-address-validator' ); 
                    ?>
                </p>
            </div>
        </div>
<?php
    }


    private function get_option_state( string $option_name ) : string
    {
        if( $this->central::$OPTIONS[ $option_name ] == 'no' )
            return esc_html__( 'No', 'last-email-address-validator' );
        elseif( $this->central::$OPTIONS[ $option_name ] == 'yes' )
            return esc_html__( 'Yes', 'last-email-address-validator' );
        return '';
    }

    private function get_wp_email_domain_as_string() : string
    {
        if( ! empty( $this->central::$OPTIONS[ 'wp_email_domain' ] ) )
            return esc_html( $this->central::$OPTIONS[ 'wp_email_domain' ] );
        else
            return esc_html( 'your-wp-email-domain.com', 'last-email-address-validator' );
    }

    private function sanitize_submitted_settings_form_data()
    {

        $this->update_notice = '';
        $this->error_notice = '';

        foreach ( $_POST as $key => $value )
        {
            // upfront preparation of data
            // this is not the sanitizing. Sanitizing takes place later
            $value = stripslashes( $value );
            $value = rtrim( $value );
            $value = preg_replace( "/\r/", '', $value);

            // if we have to test an entered email address
            if( $key == 'test_email_address' )
            {
                $this->leav->reuse( sanitize_text_field( $value ) );
                continue;
            }

            // we only look at defined keys who's values have changed
            if( ! array_key_exists( $key, $this->central::$OPTIONS ) )
            {
                // if we look at an undefined key, we discard the value and
                // continue. This way we prevent any field injection
                $value = '';
                continue;
            }

            // we only look at defined keys who's values have changed
            // anything unchanged gets skipped
            if( $this->central::$OPTIONS[ $key ] == $value )
                continue;


            // First we validate all radio button fields
            if( in_array( $key, $this->central::$RADIO_BUTTON_FIELDS ) )
            {
                $value = trim( sanitize_text_field( $value ) );
                if( $this->validate_radio_button_form_fields( $key, $value ) )
                    continue;
                else
                    $value = '';
            }

            elseif( in_array( $key, $this->central::$TEXT_FIELDS ) )
            {
                $value = trim( sanitize_text_field( $value ) );
                $this->central::$OPTIONS[$key] = $value;
                $this->add_update_notification_for_form_field( $key );
                continue;
            }

            elseif( $key == 'wp_email_domain' )
            {
                $value = trim( sanitize_text_field( $value ) );
                if( empty( $value ) || $this->leav->validate_domain( $value ) )
                {
                    $this->central::$OPTIONS[$key] = $value;
                    $this->add_update_notification_for_form_field( $key );
                }
                else
                    $this->add_error_notification_for_form_field( $key, $value );
                continue;

            }

            elseif( in_array( $key, $this->central::$INTEGER_GEZ_FIELDS ) )
            {
                $value = trim( sanitize_text_field( $value ) );
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
                $lines = preg_split("/[\r\n]+/", sanitize_textarea_field( $value ), -1, PREG_SPLIT_NO_EMPTY);
                $value = '';
                $has_errors = false;
                if( in_array( $key, $this->central::$DOMAIN_LIST_FIELDS ) )
                {
                    $sanitized_internal_values[ 'domains' ] = array();
                    $sanitized_internal_values[ 'regexps' ] = array();
                }
                elseif( in_array( $key, $this->central::$RECIPIENT_NAME_FIELDS ) )
                {
                    $sanitized_internal_values[ 'recipient_names' ] = array();
                    $sanitized_internal_values[ 'regexps' ] = array();
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
                            array_push( $sanitized_internal_values[ 'domains' ], $line );
                        else
                        {
                            $pattern = '/' . preg_replace( "/\*/", '[a-z0-9-]*', $line ) . '/';
                            array_push( $sanitized_internal_values[ 'regexps' ], $pattern );
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
                            array_push( $sanitized_internal_values[ 'regexps' ], $line );
                        else
                            array_push( $sanitized_internal_values[ 'recipient_names' ], $line );
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
                            $line = esc_html__( '# The value on the next line was automatically corrected/normalized', 'last-email-address-validator' ) . "\n" . "# " . $original_line . "\n" . $corrected_line;
                            $value .= $line . "\n";
                            array_push( $sanitized_internal_values, $corrected_line );
                        }
                        // ----- here we just comment out the errors for domains and email addresses
                        else
                        {
                            $line = esc_html__( '# The value on the next line is invalid', 'last-email-address-validator' ) . "\n". "# " . $original_line;
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
        $value = trim( sanitize_text_field( $value ) );
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
            if( $field_name == 'allow_recipient_name_catch_all_email_addresses' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'allowing recipient name catch-all syntax.', 'last-email-address-validator' ) . '<br/>';

        // ----- Email Domain --------------------------------------------------
        //
        elseif( $field_name == 'wp_email_domain' )
            $this->update_notice .= esc_html__( 'Updated the email domain for simulating the sending of emails.', 'last-email-address-validator' ) . '<br/>';

        // ----- Whitelists ----------------------------------------------------
        //
        elseif( $field_name == 'use_user_defined_domain_whitelist' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the user-defined domain whitelist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_domain_whitelist_string' )
            $this->update_notice .= esc_html__( 'Updated the user-defined domain whitelist.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_user_defined_email_whitelist' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the user-defined email address whitelist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_email_whitelist_string' )
            $this->update_notice .= esc_html__( 'Updated the user-defined email address whitelist.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_user_defined_recipient_name_whitelist' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the user-defined recipient name whitelist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_recipient_name_whitelist_string' )
            $this->update_notice .= esc_html__( 'Updated the user-defined recipient name whitelist.', 'last-email-address-validator' ) . '<br/>';
        // ----- Blacklists ----------------------------------------------------
        //
        elseif( $field_name == 'use_user_defined_domain_blacklist' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the user-defined domain blacklist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_domain_blacklist_string' )
            $this->update_notice .= esc_html__( 'Updated the user-defined domain blacklist.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_free_email_address_provider_domain_blacklist' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the built-in blacklist of free email address providers.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_user_defined_email_blacklist' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the user-defined email address blacklist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_email_blacklist_string' )
            $this->update_notice .= esc_html__( 'Updated the user-defined email address blacklist.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_user_defined_recipient_name_blacklist' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the user-defined recipient name blacklist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_recipient_name_blacklist_string' )
            $this->update_notice .= esc_html__( 'Updated the entries of the user-defined recipient name blacklist.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_role_based_recipient_name_blacklist' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the role-based recipient name blacklist.', 'last-email-address-validator' ) . '<br/>';

        // ----- Disposable Email Address Blocking -----------------------------
        //
        elseif( $field_name == 'block_disposable_email_address_services' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'blocking email addresses from disposable email address services.', 'last-email-address-validator' ) . '<br/>';

        // ----- Simulate Email Sending ----------------------------------------
        //
        elseif( $field_name == 'simulate_email_sending' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'simulating email sending.', 'last-email-address-validator' ) . '<br/>';

        // ----- Catch-all domain ----------------------------------------
        //
        elseif( $field_name == 'allow_catch_all_domains' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'allowing catch-all domains.', 'last-email-address-validator' ) . '<br/>';

        // ----- Pingbacks / Trackbacks ----------------------------------------
        //
        elseif( $field_name == 'accept_pingbacks' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'accepting pingbacks.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'accept_trackbacks' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'accepting trackbacks.', 'last-email-address-validator' ) . '<br/>';

        // ------ Validation of functions / plugins switches ---
        //
        elseif( $field_name == 'validate_wp_standard_user_registration_email_addresses' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating WordPress\'s user registration email addresses.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_wp_comment_user_email_addresses' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating WordPress\'s commentator email addresses.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_woocommerce_email_fields' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating WooCommerce email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_cf7_email_fields' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating Contact Form 7 email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_wpforms_email_fields' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating WPforms email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_ninja_forms_email_fields' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating Ninja Forms email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_mc4wp_email_fields' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating Mailchimp for WordPress (MC4WP) email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_formidable_forms_email_fields' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating Formidable Forms email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_kali_forms_email_fields' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating Kali Forms email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_elementor_pro_email_fields' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating Elementor Pro email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_gravity_forms_email_fields' )
            $this->update_notice .= esc_html__( 'Updated the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'validating Gravity Forms email fields.', 'last-email-address-validator' ) . '<br/>';


        // ------ Custom error message override fields -------------------------
        //
        elseif( $field_name == 'cem_email_address_contains_invalid_characters' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'email address contains invalid characters errors.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_email_address_syntax_error' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'email address syntax errors.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_recipient_name_catch_all_email_address_error' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'recipient name catch-all errors.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_email_domain_is_blacklisted' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'blacklisted email domains.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_email_domain_is_on_free_email_address_provider_domain_list' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'email domains on the free email address provider domain list.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_email_address_is_blacklisted' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'blacklisted email addresses.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_recipient_name_is_blacklisted' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'recipient name is on blacklist error message.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_recipient_name_is_role_based' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'role-based recipient names.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_email_domain_has_no_mx_record' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'email domains without MX records.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_email_domain_is_on_dea_blacklist' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'disposable email addresses (DEA).', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_simulated_sending_of_email_failed' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'errors during simulating sending an email.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_email_from_catch_all_domain' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'email addresses from catch-all domains.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'cem_general_email_validation_error' )
            $this->update_notice .= esc_html__( 'Updated the custom validation error message for', 'last-email-address-validator' ) . ' ' . esc_html__( 'general email validation errors.', 'last-email-address-validator' ) . '<br/>';

        // ------ Main Menu Use & Positions -------------------
        //
        elseif( in_array( $field_name, array( 'use_main_menu', 'main_menu_position', 'settings_menu_position' ) ) )
            $this->update_notice .= esc_html__( 'Changed the display location of the LEAV menu item. You have to hard-reload  this page before the change takes effect.', 'last-email-address-validator' ) . '<br/>';
        else
            $this->update_notice .= esc_html__( 'Updated the settings for field ', 'last-email-address-validator' ) . '<strong>' . $field_name . '</strong><br/>';

        return true;
    }


    private function add_error_notification_for_form_field( string &$field_name, string &$value = '' ) : void
    {

        // ----- Allow recipient name catch-all syntax --------------------------------------------
        //
        if( $field_name == 'allow_recipient_name_catch_all_email_addresses' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'allowing recipient name catch-all syntax.', 'last-email-address-validator' ) . '<br/>';

        // ----- Email Domain --------------------------------------------------
        //
        elseif( $field_name == 'wp_email_domain' )
        {
            if( empty( $value ) )
                $this->error_notice .= esc_html__( 'Error while trying to update the email domain for the simulated sending of emails. The email domain can\'t be empty while simulated email sending is active.', 'last-email-address-validator' ) . '<br/>';
            else
                $this->error_notice .= esc_html__( 'Error while trying to update the email domain for the simulated sending of emails. The entered value "' . $value . '" is not a valid domain.', 'last-email-address-validator' )  . '<br/>';

        }

        // ----- Whitelists ----------------------------------------------------
        //
        elseif( $field_name == 'use_user_defined_domain_whitelist' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'using the user-defined domain whitelist.', 'last-email-address-validator' )  . '<br/>';
        elseif( $field_name == 'user_defined_domain_whitelist_string' )
            $this->error_notice .= esc_html__( 'Error! One or more entered domains in the user-defined domain whitelist are invalid. Look at the comments in the field and correct your input.', 'last-email-address-validator' )  . '<br/>';

        elseif( $field_name == 'use_user_defined_email_whitelist' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'using the user-defined email address whitelist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_email_whitelist_string' )
            $this->error_notice .= esc_html__( 'Error! One or more entered email addresses in the user-defined email address whitelist are invalid. Look at the comments in the field and correct your input.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_user_defined_recipient_name_whitelist' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'using the user-defined recipient name whitelist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_recipient_name_whitelist_string' )
            $this->error_notice .= esc_html__( 'Error! One or more entered recipient names in the user-defined recipient name whitelist are invalid. Look at the comments in the field and correct your input.', 'last-email-address-validator' ) . '<br/>';

        // ----- Blacklists ----------------------------------------------------
        //
        elseif( $field_name == 'use_user_defined_domain_blacklist' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'using the user-defined domain blacklist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_domain_blacklist_string' )
            $this->error_notice .= esc_html__( 'Error! One or more entered domains in the user-defined domain blacklist are invalid. Look at the comments in the field and correct your input.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_user_defined_email_blacklist' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'using the user-defined email address blacklist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_email_blacklist_string' )
            $this->error_notice .= esc_html__( 'Error! One or more entered email addresses in the user-defined email address blacklist are invalid. Look at the comments in the field and correct your input.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_user_defined_recipient_name_blacklist' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the user-defined recipient name blacklist.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'user_defined_recipient_name_blacklist_string' )
            $this->error_notice .= esc_html__( 'Error while trying to update the entries of the user-defined recipient name blacklist.', 'last-email-address-validator' ) . '<br/>';

        elseif( $field_name == 'use_role_based_recipient_name_blacklist' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' .  esc_html__( 'using the role-based recipient name blacklist.', 'last-email-address-validator' ) . '<br/>';

        // ----- Disposable Email Address Blocking -----------------------------
        //
        elseif( $field_name == 'block_disposable_email_address_services' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'blocking email addresses from disposable email address services.', 'last-email-address-validator' ) . '<br/>';

        // ----- Simulate Email Sending ----------------------------------------
        //
        elseif( $field_name == 'simulate_email_sending' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'simulated email sending.', 'last-email-address-validator' ) . '<br/>';

        // ----- Catch-all domain ----------------------------------------
        //
        elseif( $field_name == 'allow_catch_all_domains' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'allowing catch-all domains.', 'last-email-address-validator' ) . '<br/>';

        // ----- Pingbacks / Trackbacks ----------------------------------------
        //
        elseif( $field_name == 'accept_pingbacks' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'accepting pingbacks.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'accept_trackbacks' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'accepting trackbacks.', 'last-email-address-validator' ) . '<br/>';

        // ------ Validation of functions / plugins switches ---
        //
        elseif( $field_name == 'validate_wp_standard_user_registration_email_addresses' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating WordPress\'s user registration email addresses.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_wp_comment_user_email_addresses' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating WordPress\'s commentator email addresses.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_woocommerce_email_fields' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating WooCommerce email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_cf7_email_fields' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating Contact Form 7 email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_wpforms_email_fields' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating WPforms email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_ninja_forms_email_fields' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating Ninja Forms email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_mc4wp_email_fields' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating Mailchimp for WordPress (MC4WP) email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_formidable_forms_email_fields' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating Formidable Forms email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_kali_forms_email_fields' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating Kali Forms email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_elementor_pro_email_fields' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating Elementor Pro email fields.', 'last-email-address-validator' ) . '<br/>';
        elseif( $field_name == 'validate_gravity_forms_email_fields' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'validating Gravity Forms email fields.', 'last-email-address-validator' ) . '<br/>';


        // ------ Custom error message override fields -------------------------
        //



        // ------ Main Menu Use & Positions -------------------
        //
        elseif( $field_name == 'use_main_menu' )
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for', 'last-email-address-validator' ) . ' ' . esc_html__( 'the display location of the LEAV menu item (main menu or settings menu.', 'last-email-address-validator' ) . '<br/>';

        elseif( in_array( $field_name, array( 'main_menu_position', 'settings_menu_position' ) ) )
            $this->error_notice .= esc_html__( 'Error! The values for the LEAV menu position within the main menu or the settings menu have to be numbers in between 0-999.', 'last-email-address-validator' ) . '<br/>';

        else
            $this->error_notice .= esc_html__( 'Error while trying to update the settings for field', 'last-email-address-validator' ) .'<strong>' . $field_name . '</strong><br/>';
    }

}
?>