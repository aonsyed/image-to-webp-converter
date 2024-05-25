<?php
/**
 * Plugin Name: Image Optimizer
 * Description: Optimizes images and converts them to WebP or AVIF format.
 * Version: 0.69
 * Author: Aon
 * Text Domain: image-optimizer
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-image-optimizer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-image-converter.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-ui.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-image-servicer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-image-scheduler.php';
require_once plugin_dir_path(__FILE__) . 'cli/class-cli-commands.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['Image_Optimizer', 'activate']);
register_deactivation_hook(__FILE__, ['Image_Optimizer', 'deactivate']);

// Initialize the plugin
add_action('plugins_loaded', ['Image_Optimizer', 'init']);

// Register deletion hook
add_action('delete_attachment', ['Image_Optimizer', 'delete_converted_images']);

// Add settings link to plugin listing
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="options-general.php?page=image-optimizer">' . __('Settings', 'image-optimizer') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
