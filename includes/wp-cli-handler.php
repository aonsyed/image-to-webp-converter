<?php
if (!defined('ABSPATH')) exit;

function webp_convert_images($args, $assoc_args) {
    if (!class_exists('Imagick')) WP_CLI::error("Imagick is not available.");
    process_images($assoc_args, 'cli');
}

if (defined('WP_CLI') && WP_CLI) WP_CLI::add_command('webp-convert', 'webp_convert_images');
?>
