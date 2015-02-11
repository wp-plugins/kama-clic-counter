=== Plugin Name ===
Stable tag: 3.2.34
Contributors: Kama
Tags: analytics, statistics, count, count clicks, clicks, counter, download, downloads, link, kama
Requires at least: 3.6
Tested up to: 4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Count clicks on any link on site. Create pretty file download block in content. Widget support.


== Description ==

Count clicks on any link on your site. Create pretty file download block in content. There is TinyMce button for adding file download shortcode. There is customizable widget, that allows you output "Top Downloads" list.

Using this plugin you will have statistics on clicks on your files or any other link (not file).

There is no unnecessary file uploads functionality. All files are uploaded with standart wordpress media library.

Localisation: English, Russian


== Installation ==
Add and activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= How can I customize download block with CSS? =

Just customize CSS styles in plugin options page. Also you can add css styles into your style.css file.


== Screenshots ==

1. Statistics page.
2. Plugin settings page.
3. Single link edit page.
4. TinyMce visual editor downloads button.

== Upgrade Notice ==

In Order to upgrade to version 3.0 or higher you need update content shortcodes from [download=<url>] to [download url=<url>]. Do this with that simple sql query, for it do once this PHP code: <?php global $wpdb; $wpdb->query("UPDATE $wpdb->posts SET post_content=REPLACE(post_content, '[download=', '[download url=')"); ?>

== Changelog ==

= 3.2.34 =
Fix: Some admin css change

= 3.2.3.3 =
added: jQuery links become hidden. All jQuery affected links have #kcc anchor and onclick attr with countclick url
fixed: error with parse_url part. If url had "=" it was exploded...

= 3.2.3.2 =
fixed: didn't correctly redirected to url with " " character
added: round "clicks per day" on admin statistics page to one decimal digit

= 3.2.3.1 =
fixed: "back to stat" link on "edit link" admin page

= 3.2.3 =
fixed: redirects to https doesn't worked correctly
fixed: PHP less than 5.3 support
fixed: go back button on "edit link" admin page
fixed: localization

= 3.2.2 =
Added: "go back" button on "edit link" admin page

= 3.2.1 =
Set autoreplace old shortcodes to new in DB during update: [download=] [download url=]

= 3.2 =
Widget has been added