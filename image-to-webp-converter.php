<?php
/*
Plugin Name: Image to WebP/AVIF Converter
Plugin URI: https://example.com/plugin-url
Description: Converts images to WebP or AVIF format upon upload or via bulk conversion. Includes WP CLI and UI for bulk operations.
Version: 1.4
Author: Aon
Author URI: https://aon.sh
License: GPLv2 or later
Text Domain: image-to-webp
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

add_filter('wp_handle_upload', 'convert_image_on_upload', 10, 2);
add_action('admin_menu', 'image_to_webp_admin_menu');
add_action('wp_ajax_image_to_webp_convert', 'image_to_webp_convert');
add_action('admin_enqueue_scripts', 'image_to_webp_admin_enqueue');
add_action('admin_footer-upload.php', 'add_convert_button_to_media_library');
if (defined('WP_CLI') && WP_CLI) WP_CLI::add_command('webp-convert', 'webp_convert_images');

function convert_image_on_upload($file) {
    if (!class_exists('Imagick')) return $file;

    $formats = ['webp', 'avif'];
    foreach ($formats as $format) {
        $converted_file = convert_image($file['file'], $format);
        if ($converted_file) {
            // Add the converted file to metadata (this can help in cases where plugins or themes use this data)
            $file['converted_files'][$format] = $converted_file;
        }
    }

    return $file;
}

function webp_convert_images($args, $assoc_args) {
    if (!class_exists('Imagick')) WP_CLI::error("Imagick is not available.");
    process_images($assoc_args, 'cli');
}

function image_to_webp_convert() {
    check_ajax_referer('image_to_webp_nonce', 'nonce');
    if (!class_exists('Imagick')) wp_send_json_error(__('Imagick is not available.', 'image-to-webp'));
    process_images($_POST, 'ajax');
}

function process_images($args, $mode) {
    $formats = isset($args['format']) ? (array) $args['format'] : ['webp', 'avif'];
    $uploads = wp_upload_dir();
    $base_dir = $uploads['basedir'];
    $log_path = isset($args['log']) ? sanitize_text_field($args['log']) : null;
    $stop_on_failure = isset($args['stop_on_failure']);
    $skip_larger = isset($args['skip_larger']);
    $scan_only = isset($args['scan']);
    $year = isset($args['year']) ? intval($args['year']) : null;
    $month = isset($args['month']) ? intval($args['month']) : null;

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));
    $total_files = iterator_count($files);
    $processed_files = 0;
    $converted_images = [];

    foreach ($files as $file) {
        if (in_array($file->getExtension(), ['jpg', 'jpeg', 'png']) && is_valid_date($file->getRealPath(), $year, $month)) {
            foreach ($formats as $format) {
                $result = convert_image($file->getRealPath(), $format, $log_path, $skip_larger);
                if ($result) {
                    $converted_images[] = $file->getRealPath();
                    break;
                } elseif ($stop_on_failure) {
                    if ($mode === 'cli') WP_CLI::error("Conversion failed for: " . $file->getRealPath());
                    else wp_send_json_error("Conversion failed for: " . $file->getRealPath());
                }
            }
        }
        $processed_files++;
        if ($mode === 'ajax') update_progress($processed_files, $total_files);
    }

    if ($scan_only) {
        if (empty($converted_images)) $mode === 'cli' ? WP_CLI::success("No images found that need conversion.") : wp_send_json_success("No images found that need conversion.");
        else {
            $message = __("Images that need conversion:", 'image-to-webp') . " " . implode(', ', $converted_images);
            $mode === 'cli' ? WP_CLI::success($message) : wp_send_json_success($message);
        }
    } else {
        $mode === 'cli' ? WP_CLI::success("Conversion process completed.") : wp_send_json_success(__('Conversion process completed.', 'image-to-webp'));
    }
}

function convert_image($file_path, $format = 'webp', $log_path = null, $skip_larger = false) {
    try {
        $image = new Imagick($file_path);
        $image->setImageFormat($format);
        $converted_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.' . $format, $file_path);
        $image->writeImage($converted_path);

        if ($skip_larger && filesize($converted_path) > filesize($file_path)) {
            unlink($converted_path);
            log_message("Skipped conversion for {$file_path} because the converted file is larger.", $log_path);
            return false;
        }

        log_message("Converted {$file_path} to {$converted_path}.", $log_path);
        return $converted_path;
    } catch (Exception $e) {
        log_message("Error converting {$file_path}: " . $e->getMessage(), $log_path);
        return false;
    }
}

function is_valid_date($file_path, $year, $month) {
    $file_time = filemtime($file_path);
    $file_year = date('Y', $file_time);
    $file_month = date('m', $file_time);
    return (!$year || $file_year == $year) && (!$month || $file_month == $month);
}

function log_message($message, $log_path = null) {
    if ($log_path) file_put_contents($log_path, $message . PHP_EOL, FILE_APPEND);
    else error_log($message);
}

function update_progress($processed_files, $total_files) {
    $progress = round(($processed_files / $total_files) * 100);
    echo json_encode(['progress' => $progress, 'message' => "Processed $processed_files of $total_files files."]);
    flush();
}

function image_to_webp_admin_menu() {
    add_management_page(__('Image to WebP/AVIF Converter', 'image-to-webp'), __('Image Converter', 'image-to-webp'), 'manage_options', 'image-to-webp', 'image_to_webp_admin_page');
}

function image_to_webp_admin_page() {
    if (!class_exists('Imagick')) echo '<div class="error"><p>' . __('Imagick is not available on this server.', 'image-to-webp') . '</p></div>';
    ?>
    <div class="wrap">
        <h1><?php _e('Image to WebP/AVIF Converter', 'image-to-webp'); ?></h1>
        <form id="image-to-webp-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <?php wp_nonce_field('image_to_webp_nonce', 'image_to_webp_nonce_field'); ?>
            <input type="hidden" name="action" value="image_to_webp_convert">
            <table class="form-table">
                <tr valign="top"><th scope="row"><?php _e('Format', 'image-to-webp'); ?></th><td><label><input type="checkbox" name="format[]" value="webp" checked> WebP</label><br><label><input type="checkbox" name="format[]" value="avif"> AVIF</label></td></tr>
                <tr valign="top"><th scope="row"><?php _e('Options', 'image-to-webp'); ?></th><td><label><input type="checkbox" name="stop_on_failure"> <?php _e('Stop on failure', 'image-to-webp'); ?></label><br><label><input type="checkbox" name="skip_larger"> <?php _e('Skip if resultant file is larger', 'image-to-webp'); ?></label></td></tr>
                <tr valign="top"><th scope="row"><?php _e('Year', 'image-to-webp'); ?></th><td><input type="number" name="year" placeholder="YYYY"></td></tr>
                <tr valign="top"><th scope="row"><?php _e('Month', 'image-to-webp'); ?></th><td><input type="number" name="month" placeholder="MM"></td></tr>
                <tr valign="top"><th scope="row"><?php _e('Log Path', 'image-to-webp'); ?></th><td><input type="text" name="log" placeholder="/path/to/logfile.log"></td></tr>
            </table>
            <p class="submit"><button type="submit" class="button button-primary"><?php _e('Start Conversion', 'image-to-webp'); ?></button></p>
        </form>
        <div id="image-to-webp-progress" style="display:none;">
            <h2><?php _e('Progress', 'image-to-webp'); ?></h2>
            <div id="progress-bar" style="width: 100%; background: #ccc;"><div id="progress-bar-inner" style="width: 0%; background: #4caf50; text-align: center; color: white;">0%</div></div>
        </div>
    </div>
    <?php
}

function image_to_webp_admin_enqueue() {
    wp_enqueue_script('image-to-webp-script', plugins_url('script.js', __FILE__), ['jquery'], null, true);
    wp_enqueue_style('image-to-webp-style', plugins_url('style.css', __FILE__));
}

function add_convert_button_to_media_library() {
    if (!current_user_can('manage_options')) return;
    ?>
    <button id="convert-to-webp-button" class="button"><?php _e('Convert to WebP/AVIF', 'image-to-webp'); ?></button>
    <?php
}
