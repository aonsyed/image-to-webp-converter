<?php
if (!defined('ABSPATH')) exit;

function image_to_webp_convert() {
    check_ajax_referer('image_to_webp_nonce', 'image_to_webp_nonce_field');
    if (!class_exists('Imagick')) wp_send_json_error(__('Imagick is not available.', 'image-to-webp'));

    process_images($_POST, 'ajax');
}
add_action('wp_ajax_image_to_webp_convert', 'image_to_webp_convert');

function image_to_webp_convert_single() {
    check_ajax_referer('image_to_webp_convert_single');

    $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
    if (!$attachment_id) wp_send_json_error(__('Invalid attachment ID.', 'image-to-webp'));

    $file_path = get_attached_file($attachment_id);
    if (!$file_path) wp_send_json_error(__('File not found.', 'image-to-webp'));

    $formats = ['webp', 'avif'];
    foreach ($formats as $format) {
        if (!Imagick::queryFormats(strtoupper($format))) {
            log_message("Imagick does not support {$format} format.");
            continue;
        }
        $converted_file = convert_image($file_path, $format);
        if ($converted_file) {
            update_media_library_url($file_path, $converted_file);
            wp_send_json_success(__('Conversion completed.', 'image-to-webp'));
        }
    }

    wp_send_json_error(__('Conversion failed.', 'image-to-webp'));
}
add_action('wp_ajax_image_to_webp_convert_single', 'image_to_webp_convert_single');
?>
