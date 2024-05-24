<?php
if (!defined('ABSPATH')) exit;

function convert_image_on_upload($file) {
    if (!class_exists('Imagick')) return $file;

    $formats = ['webp', 'avif'];
    foreach ($formats as $format) {
        if (!Imagick::queryFormats(strtoupper($format))) {
            log_message("Imagick does not support {$format} format.");
            continue;
        }
        $converted_file = convert_image($file['file'], $format);
        if ($converted_file) {
            update_media_library_url($file['file'], $converted_file);
        }
    }

    return $file;
}

function convert_image_sizes_on_upload($attachment_id) {
    if (!class_exists('Imagick')) return;

    $meta = wp_get_attachment_metadata($attachment_id);
    $upload_dir = wp_upload_dir();
    $formats = ['webp', 'avif'];

    if (isset($meta['file'])) {
        $file = $upload_dir['basedir'] . '/' . $meta['file'];
        foreach ($formats as $format) {
            if (!Imagick::queryFormats(strtoupper($format))) {
                log_message("Imagick does not support {$format} format.");
                continue;
            }
            $converted_file = convert_image($file, $format);
            if ($converted_file) {
                update_media_library_url($file, $converted_file);
            }
        }
    }

    if (isset($meta['sizes'])) {
        foreach ($meta['sizes'] as $size => $size_info) {
            $file = $upload_dir['basedir'] . '/' . dirname($meta['file']) . '/' . $size_info['file'];
            foreach ($formats as $format) {
                if (!Imagick::queryFormats(strtoupper($format))) {
                    log_message("Imagick does not support {$format} format.");
                    continue;
                }
                $converted_file = convert_image($file, $format);
                if ($converted_file) {
                    update_media_library_url($file, $converted_file);
                }
            }
        }
    }
}
?>
