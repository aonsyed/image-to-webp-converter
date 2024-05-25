<?php

class Image_Optimizer {
    public static function init() {
        // Hook into image upload
        add_filter('wp_handle_upload', [__CLASS__, 'optimize_and_convert_image']);
        // Hook into image sizes generation
        add_filter('wp_generate_attachment_metadata', [__CLASS__, 'convert_attachment_metadata'], 10, 2);
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        // Add AJAX actions
        add_action('wp_ajax_convert_image', [__CLASS__, 'ajax_convert_image']);
        add_action('wp_ajax_schedule_bulk_conversion', [__CLASS__, 'ajax_schedule_bulk_conversion']);
        add_action('wp_ajax_toggle_scheduler', [__CLASS__, 'ajax_toggle_scheduler']);
        add_action('wp_ajax_toggle_conversion_on_upload', [__CLASS__, 'ajax_toggle_conversion_on_upload']);
        add_action('wp_ajax_clean_up_optimized_images', [__CLASS__, 'ajax_clean_up_optimized_images']);
        add_action('wp_ajax_toggle_remove_originals', [__CLASS__, 'ajax_toggle_remove_originals']);
        add_action('wp_ajax_set_conversion_format', [__CLASS__, 'ajax_set_conversion_format']);
        // Serve WebP/AVIF images
        add_filter('wp_get_attachment_url', [__CLASS__, 'serve_optimized_image'], 10, 2);

        if (get_option('image_optimizer_enable_scheduler', false)) {
            // Hook the scheduled bulk conversion
            add_action('image_optimizer_bulk_conversion', ['Image_Optimizer', 'run_scheduled_conversion']);
        }

        // Initialize logger
        Logger::init();
    }

    public static function activate() {
        // Check for necessary PHP extensions
        if (!extension_loaded('gd') || !extension_loaded('imagick')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('The Image Optimizer plugin requires the GD and Imagick PHP extensions. Please install and activate them.', 'image-optimizer'));
        }
    }

    public static function deactivate() {
        // Deactivation code (if needed)
    }

    public static function optimize_and_convert_image($file) {
        if (get_option('image_optimizer_convert_on_upload', true)) {
            $image_path = $file['file'];
            Image_Converter::convert_image($image_path);
        }
        return $file;
    }

    public static function convert_attachment_metadata($metadata, $attachment_id) {
        $upload_dir = wp_upload_dir();
        foreach ($metadata['sizes'] as $size) {
            $image_path = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $size['file'];
            Image_Converter::convert_image($image_path);
        }
        return $metadata;
    }

    public static function enqueue_scripts() {
        wp_enqueue_style('image-optimizer-admin-css', plugin_dir_url(__FILE__) . '../assets/css/admin.css');
        wp_enqueue_script('image-optimizer-admin-js', plugin_dir_url(__FILE__) . '../assets/js/admin.js', [], '1.0', true);
        wp_localize_script('image-optimizer-admin-js', 'imageOptimizerAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('image_optimizer_nonce'),
        ]);
    }

    public static function ajax_convert_image() {
        check_ajax_referer('image_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized user', 'image-optimizer'));
        }

        $attachment_id = intval($_POST['attachment_id']);
        if ($attachment_id) {
            $image_path = get_attached_file($attachment_id);
            if ($image_path) {
                try {
                    $data = Image_Converter::convert_image($image_path);
                    if ($data) {
                        update_post_meta($attachment_id, 'image_optimizer_optimized', true);
                        update_post_meta($attachment_id, 'image_optimizer_sizes', $data);
                    }
                    wp_send_json_success(__('Image converted successfully', 'image-optimizer'));
                } catch (Exception $e) {
                    error_log($e->getMessage());
                    Logger::log('Error converting image ID ' . $attachment_id . ': ' . $e->getMessage());
                    wp_send_json_error(__('Error converting image', 'image-optimizer'));
                }
            } else {
                wp_send_json_error(__('Invalid image path', 'image-optimizer'));
            }
        } else {
            wp_send_json_error(__('Invalid attachment ID', 'image-optimizer'));
        }
    }

    public static function ajax_schedule_bulk_conversion() {
        check_ajax_referer('image_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized user', 'image-optimizer'));
        }

        // Schedule the bulk conversion
        if (!wp_next_scheduled('image_optimizer_bulk_conversion')) {
            wp_schedule_event(time(), 'hourly', 'image_optimizer_bulk_conversion');
        }

        wp_send_json_success(__('Bulk conversion scheduled successfully', 'image-optimizer'));
    }

    public static function run_scheduled_conversion() {
        // Run the bulk conversion
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'posts_per_page' => -1,
            'post_status' => 'inherit',
            'meta_query' => [
                [
                    'key' => 'image_optimizer_optimized',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        foreach ($attachments as $attachment) {
            $image_path = get_attached_file($attachment->ID);
            try {
                $data = Image_Converter::convert_image($image_path);
                if ($data) {
                    update_post_meta($attachment->ID, 'image_optimizer_optimized', true);
                    update_post_meta($attachment->ID, 'image_optimizer_sizes', $data);
                }
            } catch (Exception $e) {
                error_log('Error converting image ID ' . $attachment->ID . ': ' . $e->getMessage());
                Logger::log('Error converting image ID ' . $attachment->ID . ': ' . $e->getMessage());
            }
        }
    }

    public static function serve_optimized_image($url, $post_id) {
        $upload_dir = wp_get_upload_dir();
        $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $image_path);
        $avif_path = preg_replace('/\.(jpe?g|png)$/i', '.avif', $image_path);

        if (file_exists($avif_path)) {
            return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $avif_path);
        } elseif (file_exists($webp_path)) {
            return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
        }

        return $url;
    }

    public static function ajax_toggle_scheduler() {
        check_ajax_referer('image_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized user', 'image-optimizer'));
        }

        $enabled = isset($_POST['enabled']) ? boolval($_POST['enabled']) : false;
        update_option('image_optimizer_enable_scheduler', $enabled);

        if ($enabled && !wp_next_scheduled('image_optimizer_bulk_conversion')) {
            wp_schedule_event(time(), 'hourly', 'image_optimizer_bulk_conversion');
        } elseif (!$enabled && wp_next_scheduled('image_optimizer_bulk_conversion')) {
            wp_clear_scheduled_hook('image_optimizer_bulk_conversion');
        }

        wp_send_json_success(__('Scheduler toggled successfully', 'image-optimizer'));
    }

    public static function ajax_toggle_conversion_on_upload() {
        check_ajax_referer('image_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized user', 'image-optimizer'));
        }

        $enabled = isset($_POST['enabled']) ? boolval($_POST['enabled']) : false;
        update_option('image_optimizer_convert_on_upload', $enabled);

        wp_send_json_success(__('Conversion on upload toggled successfully', 'image-optimizer'));
    }

    public static function ajax_clean_up_optimized_images() {
        check_ajax_referer('image_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized user', 'image-optimizer'));
        }

        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'posts_per_page' => -1,
            'post_status' => 'inherit',
            'meta_query' => [
                [
                    'key' => 'image_optimizer_optimized',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        foreach ($attachments as $attachment) {
            $image_path = get_attached_file($attachment->ID);
            @unlink(preg_replace('/\.(jpe?g|png)$/i', '.webp', $image_path));
            @unlink(preg_replace('/\.(jpe?g|png)$/i', '.avif', $image_path));
            delete_post_meta($attachment->ID, 'image_optimizer_optimized');
            delete_post_meta($attachment->ID, 'image_optimizer_sizes');
        }

        wp_send_json_success(__('Optimized images cleaned up successfully', 'image-optimizer'));
    }

    public static function ajax_toggle_remove_originals() {
        check_ajax_referer('image_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized user', 'image-optimizer'));
        }

        $enabled = isset($_POST['enabled']) ? boolval($_POST['enabled']) : false;
        update_option('image_optimizer_remove_originals', $enabled);

        wp_send_json_success(__('Remove originals setting toggled successfully', 'image-optimizer'));
    }

    public static function ajax_set_conversion_format() {
        check_ajax_referer('image_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized user', 'image-optimizer'));
        }

        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'both';
        update_option('image_optimizer_conversion_format', $format);

        wp_send_json_success(__('Conversion format set successfully', 'image-optimizer'));
    }
}
