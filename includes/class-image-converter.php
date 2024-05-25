<?php

class Image_Converter {
    public static function convert_image($image_path, $quality = null, $sizes = []) {
        $image_info = getimagesize($image_path);
        $formats = get_option('image_optimizer_conversion_format', 'both');
        $remove_originals = get_option('image_optimizer_remove_originals', false);

        if ($image_info) {
            $mime = $image_info['mime'];
            if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
                try {
                    if (empty($sizes) || in_array($image_info[3], $sizes)) {
                        $original_size = filesize($image_path);
                        $webp_size = ($formats == 'webp' || $formats == 'both') ? self::convert_to_webp($image_path, $quality) : null;
                        $avif_size = ($formats == 'avif' || $formats == 'both') ? self::convert_to_avif($image_path, $quality) : null;

                        if ($remove_originals) {
                            unlink($image_path);
                        }

                        $data = [
                            'original_size' => $original_size,
                            'webp_size' => $webp_size,
                            'avif_size' => $avif_size,
                        ];

                        return $data;
                    }
                } catch (Exception $e) {
                    error_log('Error converting image: ' . $e->getMessage());
                    Admin_UI::add_admin_notice(__('Error converting image: ', 'image-optimizer') . $e->getMessage(), 'error');
                }
            }
        }
        return null;
    }

    private static function convert_to_webp($image_path, $quality) {
        if (is_null($quality)) {
            $quality = get_option('image_optimizer_webp_quality', 80);
        }
        $image = imagecreatefromstring(file_get_contents($image_path));
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $image_path);
        if ($image !== false) {
            imagewebp($image, $webp_path, $quality);
            imagedestroy($image);
            return filesize($webp_path);
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
            $avif_path = preg_replace('/\.(jpe?g|png)$/i', '.avif', $image_path);
            $imagick->writeImage($avif_path);
            return filesize($avif_path);
        } else {
            throw new Exception('Imagick class not found');
        }
    }
}
