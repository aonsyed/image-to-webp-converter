<?php

class Image_Converter {
    public static function convert_image($image_path, $quality = null, $sizes = []) {
        $image_info = getimagesize($image_path);
        if ($image_info) {
            $mime = $image_info['mime'];
            if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
                try {
                    if (empty($sizes) || in_array($image_info[3], $sizes)) {
                        $original_size = filesize($image_path);
                        self::convert_to_webp($image_path, $quality);
                        self::convert_to_avif($image_path, $quality);
                        $new_size = filesize($image_path);
                        Logger::log('Converted ' . basename($image_path) . ' from ' . $original_size . ' bytes to ' . $new_size . ' bytes');
                    }
                } catch (Exception $e) {
                    error_log('Error converting image: ' . $e->getMessage());
                    Admin_UI::add_admin_notice(__('Error converting image: ', 'image-optimizer') . $e->getMessage(), 'error');
                }
            }
        }
    }

    private static function convert_to_webp($image_path, $quality) {
        if (is_null($quality)) {
            $quality = get_option('image_optimizer_webp_quality', 80);
        }
        $image = imagecreatefromstring(file_get_contents($image_path));
        $webp_path = $image_path . '.webp';
        if ($image !== false) {
            imagewebp($image, $webp_path, $quality);
            imagedestroy($image);
        } else {
            throw new Exception('Failed to create image from string');
        }
    }

    private static function convert_to_avif($image_path, $quality) {
        if (is_null($quality)) {
            $quality = get_option('image_optimizer_avif_quality', 80);
        }
        if (class_exists('Imagick')) {
            $imagick = new Imagick($image_path);
            $imagick->setImageFormat('avif');
            $imagick->setImageCompressionQuality($quality);
            $avif_path = $image_path . '.avif';
            $imagick->writeImage($avif_path);
        } else {
            throw new Exception('Imagick class not found');
        }
    }
}
