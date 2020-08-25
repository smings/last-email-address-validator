=== LEAV Last Email Address Validator ===
Contributors: smings
Donate link: https://www.patreon.com/smings
Tags: email validation, email address, email address validation, email address validator, validator, form, forms, contact form, contact forms, user registration, free, comment, comments, spam, anti-spam, pingback, ping back, pingbacks, trackback, track back, trackbacks, dns check, dns checks, dns checker, mx check, mx checks, mx checker, blacklist, blacklists, domain blacklist, domain blacklists, disposable email, disposable email address, disposable email addresses, disposable email address service blocker, contact form, contact form plugin, forms, form builder, custom form, contact button, contact me, custom contact form, form manager, form, forms builder, forms creator, captcha, recaptcha, Akismet, email form, web form, feedback form, payment form, survey form, donation form, email submit form, message form, mailchimp, mailchimp form, aweber, aweber form, paypal, paypal form, stripe, stripe form, getresponse, getresponse form, email subscription, contact form widget, user registration form, wordpress registration, wordpress login form, feedback, fake email, disposable email, trashmail
Requires at least: 4.9
Tested up to: 5.5
Stable tag: 1.1.5
Requires PHP: 5.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

LEAV (light edition) provides is the best email address validation for WP registration/comments, Contact Form 7, WooCommerce and more plugins to come...

== Description ==

= LEAV Last Email Address Validator by smings (light edition) =

We believe that your lifetime is the most precious and protection worthy thing on 
the planet. Protecting it is a critical task. 
LEAV helps you to effectively protect your lifetime against spammers that use 
fake or disposable email adresses for comments, user registrations or any kind of
contact form. There are plenty of bad apples out there and we want to protect you
against them.
We built this plugin, because we were frustrated with the lack of true email
validation beyond just syntax checks in all the functions and plugins we used
ourselves. We always want to make LEAV better. If you miss a plugin or another
way of extra protection, please contact us at <a href="mailto:leav-feature-request@smings.com">leav-feature-request@smings.com</a>.

= Integrations =

LEAV is the only free WordPress plugin that provides email address validation 
that seamlessly integrates with all of the big WordPress form plugins and WordPress 
standard functions even in its light edition:

* WordPress standard user registration 
* [WordPress comments](https://www.wpbeginner.com/glossary/comment/)
* [WooCommerce](https://wordpress.org/plugins/woocommerce/)
* [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)

Additionally you can control whether you want allow pingbacks & trackbacks.
Pingbacks and trackbacks unfortunately don't come with email addresses that could be 
validated
* [WordPress Trackbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)
* [WordPress Pingbacks](https://www.wpbeginner.com/beginners-guide/what-why-and-how-tos-of-trackbacks-and-pingbacks-in-wordpress/)

= Features =
LEAV - Last Email Adress Validator by smings validates email addresses through a 5-step process:
1. User-defined domain blacklist - filters out email addresses from the blacklisted domains (optional)
2. Disposable email address service provider domain list - if activated checks it filters out email addresses from domain on the blacklist. The list is frequently updated (otional.)
3. Syntax check - checks if the email address is syntactically correct (always on).
4. DNS Record check - checks if the domain of the email address is DNS resolvable and has at least one MX Record (Mail eXchange record) (always on)
5. Simulating the sending of an email to one of the MX servers - if the simulated sending of an email fails, the email address also gets rejected (always on)

If an email address passes through all these tests, we know for sure, that it the a real email address that can be reached by your WordPress instance. 

Currently "Last Email Address Validator" integrates with:
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

= Installation =

== Installation from within your WordPress installation ==
1. Go to `` -> Add New`
2. Search for `LEAV Last Email Address Validator`
3. Click on the `Install Now` button
4. Click on the `Activate Plugin` button

== Manual installation ==
1. Go to [wordpress.org/plugins/last-email-validator/](https://wordpress.org/plugins/last-email-validator/)
2. Click on `Download` - this downloads a zip file
3. Extract the zip file. It contains the directory `last-email-validator`
3. Upload the extracted plugin directory into the `~/wp-content/plugins` directory of your WordPress installation. Afterwards you should have a directory `~/wp-content/plugins/last-email-validator` filled with the contents of the plugin code
4. Go to `Plugins` in your WordPress installation (menu item in the left sidebar)
5. Activate `Last Email Address Validator` plugin in the plugin list
6. For using translations, you can optionally copy the language files from ~/wp-content/plugins/last-email-validator/languages/*.mo to ~/wp-content/languages/plugins/

== Configuration ==
You find `Last Email Address Validator`'s settings in your WordPress installation under
`Settings -> Last Email Address Validator`
By default all features are activated and set to the highest level of spam protection. 
You should not need to adjust anything unless you want to deactivate things.
Things should always be as simple as possible, therefore you can usually skip even 
looking at the settings.

= Help us help you =
We are sure that you'll appreciate the extra level of spam protection provided by Last Email Address Validator (LEV) by smings.
As of now it is a free plugin. Yet we ask you to show us your appreciation in return by considering a one-time donation 
(on the settings -> Last Email Address Validator (LEV) page you find a donation link) or by becoming a [patreon](https://patreon.com/smings). 
This will help us help you and gives you good karma! 

= Limitations of the light edition =
In the near future the light edition will be limited to 25 email validations per day.
This will serve the vast majority of small to medium size WordPress instances.
For those who need more protection and more validations, we plan to offer 
limitless email validations as well as RBL-checks and other means of additional 
protection in future release. And all for not more than a coffee per year.

== Screenshots ==

1. settings-01.png
2. settings-02.png
3. settings-03.png
4. settings-04.png
5. cf7-01.png
6. cf7-02.png
7. woocommerce-01.png

== Changelog ==

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

= 1.1 =
Added woocommerce support

= 1.0 =
Initial Version with support for contact form 7

