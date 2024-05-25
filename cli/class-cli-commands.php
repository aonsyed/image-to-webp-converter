<?php

if (defined('WP_CLI') && WP_CLI) {
    class Image_Optimizer_CLI {
        public static function register_commands() {
            WP_CLI::add_command('image-optimizer convert', [__CLASS__, 'convert_images']);
        }

        public static function convert_images($args, $assoc_args) {
            $year = isset($assoc_args['year']) ? intval($assoc_args['year']) : null;
            $month = isset($assoc_args['month']) ? intval($assoc_args['month']) : null;
            $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'both';
            $quality = isset($assoc_args['quality']) ? $assoc_args['quality'] : null;
            $sizes = isset($assoc_args['sizes']) ? explode(',', $assoc_args['sizes']) : [];
            
            $date_query = [];
            if ($year) {
                $date_query['year'] = $year;
            }
            if ($month) {
                $date_query['monthnum'] = $month;
            }

            $query = new WP_Query([
                'post_type' => 'attachment',
                'post_mime_type' => ['image/jpeg', 'image/png'],
                'posts_per_page' => -1,
                'date_query' => [$date_query],
                'meta_query' => [
                    [
                        'key' => 'image_optimizer_optimized',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ]);

            $total_images = $query->post_count;
            $converted_images = 0;

            while ($query->have_posts()) {
                $query->the_post();
                $image_path = get_attached_file(get_the_ID());
                try {
                    $data = Image_Converter::convert_image($image_path, $quality, $sizes, $format);
                    if ($data) {
                        update_post_meta(get_the_ID(), 'image_optimizer_optimized', true);
                        update_post_meta(get_the_ID(), 'image_optimizer_sizes', $data);

                        WP_CLI::log(sprintf(
                            'Image %s converted: Original size: %s bytes, WebP size: %s bytes, AVIF size: %s bytes',
                            get_the_ID(),
                            number_format_i18n($data['original_size']),
                            number_format_i18n($data['webp_size']),
                            number_format_i18n($data['avif_size'])
                        ));

                        $converted_images++;
                        WP_CLI::log(sprintf('Progress: %d/%d images converted', $converted_images, $total_images));
                    }
                } catch (Exception $e) {
                    WP_CLI::warning('Error converting image ' . get_the_ID() . ': ' . $e->getMessage());
                }
            }

            WP_CLI::success(sprintf('All images have been converted. Total: %d/%d', $converted_images, $total_images));
        }
    }

    Image_Optimizer_CLI::register_commands();
}
