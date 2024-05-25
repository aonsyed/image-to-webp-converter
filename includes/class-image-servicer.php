<?php

class Image_Servicer {
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
}
