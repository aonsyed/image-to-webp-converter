<?php
if (!defined('ABSPATH')) exit;

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

function update_media_library_url($old_path, $new_path) {
    global $wpdb;
    $old_url = str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $old_path);
    $new_url = str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $new_path);
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET guid = %s WHERE guid = %s", $new_url, $old_url));
    $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_value = %s", $new_url, $old_url));
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
    $batch_size = isset($args['batch_size']) ? intval($args['batch_size']) : 10;
    $offset = isset($args['offset']) ? intval($args['offset']) : 0;

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));
    $file_paths = [];
    foreach ($files as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['jpg', 'jpeg', 'png']) && is_valid_date($file->getRealPath(), $year, $month)) {
            $file_paths[] = $file->getRealPath();
        }
    }
    $total_files = count($file_paths);
    $processed_files = 0;
    $converted_images = [];
    $progress_updates = [];
    $current_batch = 0;

    for ($i = $offset; $i < $total_files; $i++) {
        $file_path = $file_paths[$i];
        foreach ($formats as $format) {
            if (!Imagick::queryFormats(strtoupper($format))) {
                log_message("Imagick does not support {$format} format.");
                continue;
            }
            $result = convert_image($file_path, $format, $log_path, $skip_larger);
            if ($result) {
                $converted_images[] = $file_path;
                update_media_library_url($file_path, $result);
                break;
            } elseif ($stop_on_failure) {
                if ($mode === 'cli') WP_CLI::error("Conversion failed for: " . $file_path);
                else wp_send_json_error("Conversion failed for: " . $file_path);
            }
        }
        $processed_files++;
        $current_batch++;

        $progress = round(($processed_files / $total_files) * 100);
        $progress_updates[] = [
            'progress' => $progress,
            'message' => "Processed $processed_files of $total_files files.",
            'file' => $file_path
        ];

        if ($current_batch >= $batch_size) {
            $next_offset = $i + 1;
            wp_send_json_success([
                'progress_updates' => $progress_updates,
                'message' => __('Batch processing completed.', 'image-to-webp'),
                'next_offset' => $next_offset
            ]);
        }
    }

    if ($mode === 'ajax') {
        wp_send_json_success([
            'progress_updates' => $progress_updates,
            'message' => __('Conversion process completed.', 'image-to-webp')
        ]);
    }

    if ($scan_only) {
        if (empty($converted_images)) {
            WP_CLI::success("No images found that need conversion.");
        } else {
            $message = __("Images that need conversion:", 'image-to-webp') . " " . implode(', ', $converted_images);
            WP_CLI::success($message);
        }
    } else {
        WP_CLI::success("Conversion process completed.");
    }
}
?>
