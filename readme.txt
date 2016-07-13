=== SpamPot ===
Contributors: keith_wp
Donate Link: http://drakard.com/
Tags: spam trap, honeypot, honey pot, honey trap, spam, anti spam, anti-spam, registration, registration spam, login, login spam, hidden field
Requires at least: 3.8
Tested up to: 4.5
Stable tag: 0.34
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a honeypot form field on the registration and login pages to trap spammers.

== Description ==

Blocks spam signups by adding an invisible field to your registration and login forms, and switching its id with the real username or email form field.

When spam bots blindly fill the "required" fake field in, WP will redirect them to an error message and not let them register or login.

No 3rd party service, no intrusive captchas.


== Installation ==

1. Upload the plugin files to the **/wp-content/plugins/spampot/** directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. That's it, no more configuration needed.


== Frequently Asked Questions ==

= Will This Stop Everything? =

* This honeypot technique works against the lazy "drive by" automated spambots (ie. most of them), but will not be effective against spammers specifically targetting your site nor spammers signing up by hand.


== Changelog ==

= 0.3 =
* Initial release.

= 0.31 =
* Fix for 4.5's changed login text.

= 0.32 =
* Changed the way the honeypot field was inserted to avoid a conflict with my Custom Logins plugin.

= 0.33 =
* If the honeypot field is unable to be inserted, then the form will continue working and notify the admin by email of this failure.

= 0.34 =
* Changed the regex that matches the "normal" username field to be slightly more robust.
