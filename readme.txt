=== Image Optimizer ===
Contributors: Aon
Donate link: https://aon.sh
Tags: images, optimization, webp, avif
Requires at least: 5.6
Tested up to: 6.0
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Image Optimizer plugin converts and optimizes images to WebP and AVIF formats to improve website performance.

== Description ==

The Image Optimizer plugin helps to optimize images by converting them to WebP and AVIF formats. This results in faster loading times and better performance for your website. The plugin supports automatic conversion on upload, manual conversion from the media library, and bulk conversion.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/image-optimizer` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to 'Settings' > 'Image Optimizer' to configure the plugin.

== Frequently Asked Questions ==

= How do I disable automatic conversion? =

There is a setting in the plugin's settings page to disable automatic conversion on upload.

= Can I convert GIFs? =

Yes, the plugin supports converting GIFs to WebP and AVIF formats.

= Does the plugin support multi-site installations? =

Yes, the plugin is compatible with WordPress multi-site installations.

= How do I exclude specific image sizes from conversion? =

Go to 'Settings' > 'Image Optimizer' and select the sizes you want to exclude from the conversion.

== Screenshots ==

1. Settings page for configuring the plugin.
2. Media library with conversion options.
3. Bulk conversion options.

== Changelog ==

= 1.0 =
* Initial release of the plugin.

== Upgrade Notice ==

= 1.0 =
* Initial release.

== WP-CLI Commands ==

= Convert Images =
`wp image-optimizer convert --quality=<quality> --sizes=<sizes>`
- `<quality>`: Set the quality for conversion.
- `<sizes>`: Comma-separated list of image sizes to convert.
