=== Last Email Validator (LEV) by smings ===
Contributors: @smings, @kimpenhaus
Donate link: https://www.patreon.com/smings
Tags: email validation, registration, free, comments, spam, anti-spam, pingbacks, dns check, mx check, blacklist, disposable_email
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


## Origin ##
The foundational code was written by [@kimpenhaus](https://profiles.wordpress.org/kimpenhaus/). 
Since the original plugin only supported the standard WordPress registration, comments and 
Trackbacks/Pingbacks, I forked the code and then extended 
it to work with [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) 
as well as [WooCommerce](https://wordpress.org/plugins/woocommerce/). 

If you need `Last-Email-Validator` to integrate with more plugins, feel free to contact me at [dirk@smings.com](mailto:dirk@smings.com).

== Installation ==

## Installation from within your WordPress installation
1. Go to `` -> Add New`
2. Search for `Last Email Validator`
3. Click on the `Install Now` button
4. Click on the `Activate Plugin` button

## Manual installation
1. Go to [wordpress.org/plugins/last-email-validator/](https://wordpress.org/plugins/last-email-validator/)
2. Click on `Download` - this downloads a zip file
3. Extract the zip file. It contains the directory `last-email-validator`
3. Upload the extracted plugin directory into the `~/wp-content/plugins` directory of your WordPress installation. Afterwards you should have a directory `~/wp-content/plugins/last-email-validator` filled with the contents of the plugin code
4. Go to `Plugins` in your WordPress installation (menu item in the left sidebar)
5. Activate `Last Email Validator` plugin in the plugin list

## Configuration
You find `Last Email Validator`'s settings in your WordPress installation under
`Settings -> Last Email Validator`



== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.

== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`
