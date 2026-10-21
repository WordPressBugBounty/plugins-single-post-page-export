=== Export Single Post Page ===
Contributors: SS88_UK
Donate link: https://paypal.me/SS88/
Tags: export, post export, page export, WXR, migration
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.4.1
Requires PHP: 5.6
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Export (an XML file) a single post or page using WordPress' eXtended RSS (WXR). There's no need to export your entire database anymore!

== Description ==

Say goodbye to exporting your entire content database or manually copying and pasting individual posts or pages. Export exactly the content you need as a standard WordPress (WXR) XML file, then bring it into any other site with the official WordPress Importer.

**Features**

* **One-click export** from the Posts, Pages, Media and custom post type lists.
* **Bulk export.** Select several items and choose "Export" from the Bulk actions menu to get them all in one file.
* **Export from the editor.** Use the block editor's options (⋮) menu, or the Publish box in the classic editor.
* **Export with media.** Include the featured image, attached media, and images, galleries, video, audio and files used in the content. The importer downloads them and updates the links.
* **Only the terms you need.** Categories, tags and custom taxonomy terms used by the exported content are included, along with their parent terms.
* **Clear file names.** Single exports are named after the post slug.
* **Secure.** Export links are protected with nonces and are only shown to users who can export and edit the content.

**Check out our other plugins:** 

* 🦄 [Unicorn Panel](https://unicornpanel.com?ref=WordPress&utm_campaign=WPReadme_ESPP) - the new web hosting control panel built for multi-server fleets; FREE!
* 🎉 [Media Library File Size](https://wordpress.org/plugins/media-library-file-size/)
* 🔒 [Two Factor (2FA) Authentication via Email](https://wordpress.org/plugins/two-factor-2fa-via-email/)
* 🙍‍♂️ [View User Metadata](https://wordpress.org/plugins/view-user-metadata/)
* 🔠 [Enable Turnstile (Cloudflare) for Gravity Forms](https://wordpress.org/plugins/enable-turnstile-cloudflare-for-gravity-forms/)
* ⭐️⭐️⭐️⭐️⭐️ [Gravity Forms to FreeScout](https://ss88.us/plugins/gravity-forms-freescout?utm_campaign=OtherPlugins)

== Installation ==

Use the automatic installer via WordPress or download the plugin and:

1. Upload the plugin files to the `/wp-content/plugins/single-post-page-export` directory.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Go to any list of Posts, Pages, Media or a custom post type.
1. Hover over an item, click 'Export' and choose what to export, or select several items and use the Bulk actions menu.
1. Import the XML file on another site with Tools > Import > WordPress.

== Frequently Asked Questions ==

= Who can export? =

Users with the `export` capability (Administrators by default) who can also edit the content.

= What does "Export with media" include? =

The featured image, any media attached to the post, and media used in the content (image, gallery, video, audio, file, cover and media & text blocks, `wp-image-` classes in classic content, and `[gallery]` / `[playlist]` shortcodes). When importing, tick "Download and import file attachments".

Developers can add more attachment IDs with the `ss88_sppe_media_ids` filter.

= Which post types are supported? =

Any post type registered with `can_export` enabled, which is the default.

== Screenshots ==

1. An example of the 'Export' link when viewing Pages.

== Changelog ==

= 1.4.1 =
* Fix: Dismissing the Unicorn Panel notice now always saves, even if it's closed before the page finishes loading

= 1.4 =
* New: Bulk export from the Bulk actions menu
* New: Export from the block editor options menu and the classic editor Publish box
* New: "Export with media" includes the featured image, attached media and media used in the content
* New: Single exports are named after the post slug
* New: "Need help?" link on the Plugins screen
* Security: Export links now use nonces and check user permissions
* Fix: More reliable export query that can no longer fall back to exporting the whole site
* Fix: Exporting a post in a child category no longer hangs when the parent category wasn't included
* Tested for WP 7.1

= 1.3 =
* Tested for WP 6.5
* Plugin name change

= 1.2 =
* Rewrite to use query instead of custom code for export

= 1.1.1 =
* Category/CDATA fix

= 1.1 =
* Translation ready

= 1.0 =
* Initial release.

== Upgrade Notice ==
