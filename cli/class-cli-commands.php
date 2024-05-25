<?php

if (defined('WP_CLI') && WP_CLI) {
    class Image_Optimizer_CLI {
        public static function register_commands() {
            WP_CLI::add_command('image-optimizer convert', [__CLASS__, 'convert_images']);
        }

        public static function convert_images($args, $assoc_args) {
            $quality = isset($assoc_args['quality']) ? $assoc_args['quality'] : null;
            $sizes = isset($assoc_args['sizes']) ? explode(',', $assoc_args['sizes']) : [];
            
            $query = new WP_Query([
                'post_type' => 'attachment',
                'post_mime_type' => ['image/jpeg', 'image/png'],
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'image_optimizer_optimized',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ]);

            while ($query->have_posts()) {
                $query->the_post();
                $image_path = get_attached_file(get_the_ID());
                try {
                    Image_Converter::convert_image($image_path, $quality, $sizes);
                    update_post_meta(get_the_ID(), 'image_optimizer_optimized', true);
                } catch (Exception $e) {
                    WP_CLI::warning('Error converting image ' . get_the_ID() . ': ' . $e->getMessage());
                }
            }

            WP_CLI::success('All images have been converted.');
        }
    }

    Image_Optimizer_CLI::register_commands();
}
