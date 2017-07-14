=== Cache External Scripts ===
Contributors: Voorsie
Donate link: http://www.forcemedia.nl/wordpress-plugins/cache-external-scripts/
Tags: cache, caching, scripts, google analytics, javascripts, local, pagespeed
Requires at least: 3.0.1
Tested up to: 4.5.3
Stable tag: 0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Save the Google Analytics file (analytics.js) locally to be able to cache it for longer than 2 hours for a better PageSpeed score!

== Description ==

Often when trying to optimize the Google Pagespeed score, there is one script which still causing the 'Leverage browser caching' rule popping up: Google's own analytics.js file...

With this plugin you will be able to cache this file on your local server and enable browser caching for longer than 2 hours. The plugin will check every day if there is a newer version of the file to keep the cache up to date.

== Installation ==

Installation is very easy;

1. Upload the plugin directory `cache-external-scripts` to the `/wp-content/plugins/` directory or install it directly from the Wordpress plugin directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Once the external script is cached by the plugin, it will automatically replace the url in your current Google Analytics code (meaning you can use your current Google Analytics plugin).

== Frequently Asked Questions ==

= I have installed the plugin, but I can't find the Google Analytics code in the page source code =

This plugin only caches the script and replaces it in your **current** Analytics code, containing 'analytics.js' script.
We chose not to insert the Analytics code itself because there are tons of plugins for that already, and some users require modifications in the code.

= How can I check if the Analytics code is properly cached =

We store the cached file in the wordpress `uploads` directory, in the folder called `cached-scripts`. If this folder doesn't exist or is empty, please visit the settings page of the plugin to manually fetch the script.

== Screenshots ==

1. Fix this last Google Pagespeed problem

== Changelog ==

= 0.3 =
* Added support for new Google Analytics tracking codes using https protocol as standard
* Small bug fix where cache being checked against the wrong file

= 0.2 =
* Added support for (old) Google Analytics tracking codes using `ga.js` script

= 0.1 =
* Initial release

== Upgrade Notice ==

= 0.3 =
Please upgrade for support of the latest Google Analytics tracking code

= 0.2 =
Please visit the settings of the plugin to force a refresh, or simply wait for the next cron to run.

= 0.1 =
Initial release