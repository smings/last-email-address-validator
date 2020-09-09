=== LEAV Last Email Address Validator ===
Contributors: smings
Donate link: https://www.patreon.com/smings
Tags: email address validation, email address validator, form, forms, contact form, contact forms, user registration, comments, spam, MX, DNS
Requires at least: 4.9
Tested up to: 5.5
Stable tag: 1.4.1
Requires PHP: 7.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

LEAV (light edition) provides is the best email address validation for WP registration/comments, Contact Form 7, WooCommerce and more plugins to come...

== Description ==

= LEAV - Last Email Address Validator by [smings](https://smings.com/last-email-address-validator) =

We believe that your lifetime is the most precious and protection worthy thing in 
the universe. Protecting it is a critical task. 
LEAV helps you to effectively protect your lifetime against spammers that use 
fake or disposable email adresses for comments, user registrations or any kind of
contact form. There are plenty of bad apples out there and we want to protect you
against them.
We built this plugin, because we were frustrated with the lack of true email
validation beyond just syntax checks in all the WordPress functions and plugins we use
ourselves. We always want to make LEAV better. If you miss a plugin or another
way of extra protection, please contact us at [leav@smings.com](mailto:leav@smings.com).

= Integrations =

LEAV is the only free WordPress plugin that provides unlimited email address validation 
and reliable disposable email address protection that seamlessly integrates with all of the big WordPress form plugins and WordPress standard functions. LEAV makes sure that email addresses are deliverable and (if activated) are not disposable email addresses.

Currently "Last Email Address Validator" integrates with:
* WordPress user registration
* [WordPress comments](https://www.wpbeginner.com/glossary/comment/)
* [WordPress Trackbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)
* [WordPress Pingbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)
* [WooCommerce](https://wordpress.org/plugins/woocommerce/)
* [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)
* [WPForms (light and pro)](https://wordpress.org/plugins/wpforms-lite/)
* [Ninja Forms](https://wordpress.org/plugins/ninja-forms/)

Additionally you can control whether you want to allow pingbacks & trackbacks.
Pingbacks and trackbacks unfortunately don't come with email addresses that could be 
validated
* [WordPress Trackbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)
* [WordPress Pingbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)

= Features =
LEAV - Last Email Adress Validator by smings validates email addresses through an elaborate multi-step validation process:
* Email address syntax check - checks if the email address is syntactically correct. This syntax check usually is more thorough than the normally frontend-based (javascript) validation of your forms plugin. It is a solid server-side email syntax check based on regular expressions (always on). By the way - there are top-level domains like ".CANCERRESEARCH" and even longer ones out there. The currently longest top-level domain is 18 characters long and most email syntax checks don't allow this. For a current list of allowed top level domains look at [iana.org](https://data.iana.org/TLD/tlds-alpha-by-domain.txt).

* User-defined domain whitelist - allows all email addresses from your personal list of whitelisted email domains (optional)

* User-defined email address whitelist - allows all email addresses from your personal list of whitelisted email addresses (optional)

* User-defined domain blacklist - rejects email addresses from your personal list of blacklisted email domains (optional)

* User-defined email address blacklist - rejects any email address from your personal list of blacklisted email addresses (optional)

* Disposable email address service provider domain list - if activated checks and filters out email addresses from domains on the blacklist. The list gets frequently updated (otional)

* DNS MX record check - checks if the domain of the email address is DNS resolvable and has at least one MX server (MX = Mail eXchange record) (always on)

* User-defined MX server domain blacklist - rejects all email addresses who's mail server domains are on this list

* LEAV's MX server domain blacklist - rejects all email addresses who's mail server domains are on this list. This list contains the main domains of known disposable email address services.

* User-defined MX server IP address blacklist - rejects all email addresses who's mail servers' IP addresses are on this list

* LEAV's MX server IP address blacklist - rejects all email addresses who's mail servers' IP addresses are on this list of mail servers of known disposable email address services. This filters out all the aliases of the disposable email address service domains too, since they are all using the same set of mail servers. This keeps our list of disposable email address services lean and maintainable.

* Simulated sending of an email to one of the MX servers. If this siumulation fails, we know that your WordPress instance could not send an email to the email address. Therefore we reject such email addresses (always on)

If an email address passes through all of these tests, we know for sure, that it is a real email address that can be reached by your WordPress instance. This will reduce spam significantly. Still we 
encourage you to use additional spam protection by using reCATCHAs (i.e. googles [reCAPTCHA v3](https://developers.google.com/recaptcha/docs/v3) that
is invisible except for a little banner that has to be added on the form pages at least) and other means to protect your valuable lifetime.

After all - all the above tests just verify the email address's correctness and deliverability, but it doesn't prove that the person in front of the computer entering the email address has access to it. 

This check is part of the LEAV pro version. LEAV's pro version verifies that the person in front of the computer (or on his mobile device) has actual access to the email address. By connecting the email address with the person interacting with your WordPress, you can reduce the amount of SPAM even further.

But even after all this, you'll probably be bothered every now and then. But you'll save a ton of your precious lifetime with the above checks provided by LEAV.


## Origins ##
The inspiration for this plugin stem.s from the plugin [wp-mail-validator](https://wordpress.org/plugins/wp-mail-validator/).
Since this plugin only supported the standard WordPress registration, comments and 
Trackbacks/Pingbacks, we took the code and then extended it to work with [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) as well as [WooCommerce](https://wordpress.org/plugins/woocommerce/). The original code was not following best practices and had some other shortcomings. So with version 1.3.0 we decided to completely rewrite everything and did a major code refactoring. This allowed us to have a solid foundation for a lot of more supported WordPress plugins to come.

If you need "LEAV - Last-Email-Address-Validator" to integrate with a plugin you use, feel free to contact us at [leav@smings.com](mailto:leav@smings.com) for feature requests.

= Installation =

== Installation from within your WordPress installation ==
1. Go to `Plugins` -> Add New`
2. Search for `Last Email Address Validator`
3. Click on the `Install Now` button
4. Click on the `Activate Plugin` button

== Manual installation ==
1. Go to [wordpress.org/plugins/last-email-address-validator/](https://wordpress.org/plugins/last-email-address-validator/)
2. Click on `Download` - this downloads a zip file
3. Extract the zip file. It contains the directory `last-email-address-validator`
3. Upload the extracted plugin directory into the `~/wp-content/plugins` directory of your WordPress installation. Afterwards you should have a directory `~/wp-content/plugins/last-email-address-validator` filled with the contents of the plugin code
4. Go to `Plugins` in your WordPress installation (menu item in the left sidebar)
5. Activate `Last Email Address Validator` plugin in the plugin list
6. For using translations, you can optionally copy the language files from ~/wp-content/plugins/last-email-address-validator/languages/*.mo to ~/wp-content/languages/plugins/

== Configuration ==
You find `Last Email Address Validator`'s settings in your WordPress installation under
`Settings -> Last Email Address Validator`
By default all features are activated and set to the highest level of spam protection. 
You should not need to adjust anything unless you want to deactivate things.
Things should always be as simple as possible, therefore you can usually skip even 
looking at the settings.

= Help us help you =
We are sure that you'll appreciate the extra level of spam protection provided by Last Email Address Validator (LEAV) by smings.
We take great pride in the fact that it is the only plugin to support all major WordPress form plugins out of the box for free. We believe that everyone deserves to get his lifetime SPAM protected. So LEAV isn't limited in the number of validations it does for you. The author, [Dirk Tornow](mailto:dirk@smings.com), has a baby girl and a rascal toddler that need daycare and much more. Therefore we ask you to show him your appreciation by considering a [one-time donation via PayPal](https://paypal.me/DirkTornow) or by becoming a [patreon](https://patreon.com/smings). 
This will help us help you and gives you good karma points! 

= Limitations of the free plugin =
None - there aren't any. LEAV validates as many email addresses as your WordPress instance can handle. It makes sure that all entered email addresses are deliverable and (if activated) not from disposable email address domains.
For those who need more protection and more validations, we currently develop the pro version of LEAV. The pro version of LEAV validates, that the person entering the email address has actual access and control over the email account. It does this by sending a verification code to the entered email address and provides the user who entered the email address with a verification step before the form data gets send to the underlying plugin. No matter the plugin. LEAV pro supplies the functionality.
Additionally LEAV pro will do Realtime Blackhole List (RBL) checks to make sure the email address entered is not from known spammer domains. And this will cost just as much as a starbucks coffee per year.

== Screenshots ==
1. settings-01.png
2. settings-02.png
3. settings-03.png
4. settings-04.png
5. cf7-01.png
6. cf7-02.png
7. woocommerce-01.png


== Changelog ==
= 1.3.0 =
* Added support for Ninja Forms
* Complete refactoring of the code for better readability and easier extending

= 1.2.0 =
* Added support for WPForms

= 1.1.5 =
* Fixed minor validation bugs

= 1.1.4 =
* optimized descriptions and German translations
* added screenshots

= 1.1.3 =
* Completed German translation
* Optimized settings

= 1.1.0 =
* added woocommerce support

= 1.0.0 =
* added Contact Form 7 support
* Initial German translations

== Upgrade Notice ==

= 1.2.0 =
* Added support for WPForms

= 1.1 =
* Added woocommerce support

= 1.0 =
* Initial Version with support for contact form 7

