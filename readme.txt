=== Plugin Name ===
Contributors: Tkama
Tags: analytics, statistics, count, count clicks, clicks, counter, download, downloads, link, kama
Requires at least: 3.6
Tested up to: 4.0
Stable tag: 3.2.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Counts clicks on any link on your site. Creates a pretty file download block in the content. Widget support.

== Description ==

Counts clicks on any link on your site. Creates a pretty file download block in the content. There is a TinyMce button for adding the file download shortcode. There is a customizable widget that allows you to output the "Top Downloads" list.

This plugin gives you statistics on clicks on your files or any other links (not file).

There are no unnecessary file upload functionality. All files are uploaded with a standard WordPress media library.

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