<?php

class Image_Scheduler {
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
            $image_path = str_replace(" ", "\\", $image_path);// Handle spaces in filenames
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
}
