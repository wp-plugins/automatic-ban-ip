=== Automatic Ban IP ===

Author: SedLex
Contributors: SedLex
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/plugins/automatic-ban-ip/
Tags: ban, ip, automatic, spam, comments, firewall, block
Requires at least: 3.0
Tested up to: 4.2
Stable tag: trunk
License: GPLv3

Block IP addresses which are suspicious and try to post on your blog spam comments.

== Description ==

Block IP addresses which are suspicious and try to post on your blog spam comments.

This plugin need that you create an account on the Honey Pot Project (https://www.projecthoneypot.org, free api) or that you install the Spam Captcha plugin.

In addition, if you want to geolocate the spammers your may create an account on (http://ipinfodb.com/, free api). Thus, you may display a world map with the concentration of spammers.

Spammers may be blocked either by PHP based restrictions (i.e. Wordpress generates a 403 page for such identified users) or by Apache based restriction (using Deny from in .htaccess file).

The Apache restriction is far more efficient when hundreds of hosts sent you spams in few minutes.

= Multisite - Wordpress MU =

= Localization =

* English (United States), default language

= Features of the framework =

This plugin uses the SL framework. This framework eases the creation of new plugins by providing tools and frames (see dev-toolbox plugin for more info).

You may easily translate the text of the plugin and submit it to the developer, send a feedback, or choose the location of the plugin in the admin panel.

Have fun !

== Installation ==

1. Upload this folder automatic-ban-ip to your plugin directory (for instance '/wp-content/plugins/')
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the 'SL plugins' box
4. All plugins developed with the SL core will be listed in this box
5. Enjoy !

== Screenshots ==

1. The world map of banned spammers
2. An extract of list of banned spammers

== Changelog ==

= 1.0.4 = 
* BUG: check if any key is specify before checking

= 1.0.3 = 
* NEW: deletion of temp file upon desinstall

= 1.0.2 = 
* Various enhancements
* NEW : Add icons

= 1.0.1 = 
* Add Banners

= 1.0.0 = 
* Initial release

== Frequently Asked Questions ==

 
InfoVersion:7e9e746e1f31616db856cf709c9915ebe4d2049e