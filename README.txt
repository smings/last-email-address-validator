=== Last Email Validator (LEV) by smings ===
Contributors: @smings, @kimpenhaus
Donate link: https://www.patreon.com/smings
Tags: email validation, registration, free, comments, spam, anti-spam, pingbacks, trackbacks, dns check, mx check, blacklist, domain blacklist, disposable email service blocker
Requires at least: 5.2
Tested up to: 5.5
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Last Email Validator (LEV) provides email address validation for WP 
registration, WP comments, CF7, WooCommerce

== Description ==

## Last Email Validator (LEV)
LEV is the only free plugin that provides email address validation for
* WordPress standard user registration 
* [WordPress comments](https://www.wpbeginner.com/glossary/comment/)
* [WooCommerce](https://wordpress.org/plugins/woocommerce/)
* [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)

Additionally you can control whether you want allow pingback & trackbacks
* [WordPress Trackbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)
* [WordPress Pingbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)

## Features ##
Last Email Validator (LEV) by smings validates email addresses by checking the following things:
1. User-defined domain blacklist - filters out email addresses from the blacklisted domains (optional)
2. Disposable email address service provider domain list - if activated checks it filters out email addresses from domain on the blacklist. The list is frequently updated (otional.)
3. Syntax check - checks if the email address is syntactically correct (always on).
4. DNS Record check - checks if the domain of the email address is DNS resolvable and has at least one MX Record (Mail eXchange record) (always on)
5. Simulating the sending of an email to one of the MX servers - if the simulated sending of an email fails, the email address also gets rejected (always on)

If an email address passes through all these tests, we know for sure, that it the a real email address that can be reached by your WordPress instance. 

Currently "Last Email Validator" integrates with:
* WordPress user registration
* [WordPress comments](https://www.wpbeginner.com/glossary/comment/)
* [WordPress Trackbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)
* [WordPress Pingbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)
* [WooCommerce](https://wordpress.org/plugins/woocommerce/)
* [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)


## Origins ##
The foundational code was written by [@kimpenhaus](https://profiles.wordpress.org/kimpenhaus/). 
Since the original plugin only supported the standard WordPress registration, comments and 
Trackbacks/Pingbacks, we forked the code and then extended 
it to work with [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) 
as well as [WooCommerce](https://wordpress.org/plugins/woocommerce/). 
The original code was not following best practices either. These shortcomings got
optimized. 

If you need `Last-Email-Validator` to integrate with more plugins, feel free to contact us at 
[lev-feature-requests@smings.com](mailto:lev-feature-requests).

== Installation ==

## Installation from within your WordPress installation
1. Go to `` -> Add New`
2. Search for `Last Email Validator (LEV) by smings`
3. Click on the `Install Now` button
4. Click on the `Activate Plugin` button

## Manual installation
1. Go to [wordpress.org/plugins/last-email-validator/](https://wordpress.org/plugins/last-email-validator/)
2. Click on `Download` - this downloads a zip file
3. Extract the zip file. It contains the directory `last-email-validator`
3. Upload the extracted plugin directory into the `~/wp-content/plugins` directory of your WordPress installation. Afterwards you should have a directory `~/wp-content/plugins/last-email-validator` filled with the contents of the plugin code
4. Go to `Plugins` in your WordPress installation (menu item in the left sidebar)
5. Activate `Last Email Validator` plugin in the plugin list
6. For using translations, you can optionally copy the language files from ~/wp-content/plugins/last-email-validator/languages/*.mo to ~/wp-content/languages/plugins/

## Configuration
You find `Last Email Validator`'s settings in your WordPress installation under
`Settings -> Last Email Validator`
By default all features are activated and set to the highest level of spam protection. 
You should not need to adjust anything unless you want to deactivate things.

## Help us help you
We are sure that you'll appreciate the extra level of spam protection provided by Last Email Validator (LEV) by smings.
As of now it is a free plugin. Yet we ask you to show us your appreciation in return by considering a one-time donation 
(on the settings -> Last Email Validator (LEV) page you find a donation link) or by becoming a [patreon](https://patreon.com/smings). 
This will help us help you and gives you good karma! 


== Screenshots ==

1. settings-01.png
2. settings-02.png
3. settings-03.png
4. settings-04.png
5. cf7-01.png
6. cf7-02.png
7. woocommerce-01.png

== Changelog ==

= 1.1.3 =
* complete German translation
* Optimized settings

= 1.1.0 =
* added woocommerce support

= 1.0.0 =
* added Contact Form 7 support
* 1st German translations

== Upgrade Notice ==

= 1.1 =
Added woocommerce support

= 1.0 =
Initial Version yet without woocommerce
