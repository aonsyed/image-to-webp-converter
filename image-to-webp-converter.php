<?php
/*
Plugin Name: Image to WebP/AVIF Converter
Plugin URI: https://example.com/plugin-url
Description: Converts images to WebP or AVIF format upon upload or via bulk conversion. Includes WP CLI and UI for bulk operations.
Version: 2.1
Author: Aon
Author URI: https://aon.sh
License: GPLv2 or later
Text Domain: image-to-webp
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/conversion-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/conversion-helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/wp-cli-handler.php';

add_filter('wp_handle_upload', 'convert_image_on_upload', 10, 2);
add_action('add_attachment', 'convert_image_sizes_on_upload');
?>
