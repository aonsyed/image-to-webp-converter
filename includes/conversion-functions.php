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
            $file['converted_files'][$format] = $converted_file;
            update_media_library_url($file['file'], $converted_file);
        }
    }

    return $file;
}
?>
