=== UniPress API ===
Contributors: layotte, pericson, endocreative
Tags: mobile, app, api
Requires at least: 3.3
Tested up to: 4.3
Stable tag: 1.2.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The easiest way to launch iOS and Android apps for your WordPress readers.

== Description ==

UniPress is the easiest way to launch iOS and Android apps for your WordPress readers.


== Installation ==

1. Upload the entire `unipress-api` folder to your `/wp-content/plugins/` folder.
1. Go to the 'Plugins' page in the menu and activate the plugin.

== FAQ ==

**What are the minimum requirements for UniPress API?**
You must have:
* WordPress 3.3 or later
* PHP 5

**How is UniPress API Licensed?**
* UniPress API is GPL.


== Changelog ==
= 1.2.5 =
* Adding post_url to post variable
* Fixing bug when offset is set and posts_per_page isn't
* Rekeying array values in posts get_content_list return

= 1.2.4 =
* Change how we remove posts that are in excluded cats

= 1.2.3 =
* Adding post date to push notification

= 1.2.2 =
* Fixing bug with post not being removed on an excluded category match

= 1.2.1 =
* Fixing typo in attachment baseurl

= 1.2.0 =
* Changed how tokens are stored in the database
* Added Custom JS Area
* Added CDN Support
* Added Excerpt/Content customizations
* Added ability to exclude certain categories from UniPress

= 1.1.0 =
Adding author arg to get-content-list

= 1.0.0 =
* Initial Release
