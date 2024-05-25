<?php

class Admin_UI {
    private static $notices = [];

    public static function init() {
        // Add settings page
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);

        // Add bulk action button
        add_filter('bulk_actions-upload', [__CLASS__, 'register_bulk_actions']);
        add_filter('handle_bulk_actions-upload', [__CLASS__, 'handle_bulk_actions'], 10, 3);
        // Add single convert button
        add_filter('media_row_actions', [__CLASS__, 'add_convert_button'], 10, 2);
        add_action('admin_init', [__CLASS__, 'handle_single_conversion']);

        // Add media library columns
        add_filter('manage_media_columns', [__CLASS__, 'add_media_columns']);
        add_action('manage_media_custom_column', [__CLASS__, 'render_media_columns'], 10, 2);

        // Admin notices
        add_action('admin_notices', [__CLASS__, 'display_admin_notices']);
    }

    public static function add_settings_page() {
        add_options_page(
            __('Image Optimizer Settings', 'image-optimizer'),
            __('Image Optimizer', 'image-optimizer'),
            'manage_options',
            'image-optimizer',
            [__CLASS__, 'create_settings_page']
        );
    }

    public static function create_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Image Optimizer Settings', 'image-optimizer'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('image_optimizer_settings');
                do_settings_sections('image-optimizer');
                submit_button();
                ?>
            </form>
            <button id="schedule-bulk-conversion" class="btn btn-primary"><?php _e('Schedule Bulk Conversion', 'image-optimizer'); ?></button>
            <button id="clean-up-optimized-images" class="btn btn-danger"><?php _e('Clean Up Optimized Images', 'image-optimizer'); ?></button>
            <label>
                <input type="checkbox" id="toggle-scheduler" <?php checked(get_option('image_optimizer_enable_scheduler', false)); ?>>
                <?php _e('Enable Scheduler', 'image-optimizer'); ?>
            </label>
            <label>
                <input type="checkbox" id="toggle-conversion-on-upload" <?php checked(get_option('image_optimizer_convert_on_upload', true)); ?>>
                <?php _e('Convert on Upload', 'image-optimizer'); ?>
            </label>
            <label>
                <input type="checkbox" id="toggle-remove-originals" <?php checked(get_option('image_optimizer_remove_originals', false)); ?>>
                <?php _e('Remove Originals on Conversion', 'image-optimizer'); ?>
            </label>
            <label>
                <select id="set-conversion-format" name="image_optimizer_conversion_format">
                    <option value="both" <?php selected(get_option('image_optimizer_conversion_format', 'both'), 'both'); ?>><?php _e('WebP and AVIF', 'image-optimizer'); ?></option>
                    <option value="webp" <?php selected(get_option('image_optimizer_conversion_format', 'both'), 'webp'); ?>><?php _e('WebP', 'image-optimizer'); ?></option>
                    <option value="avif" <?php selected(get_option('image_optimizer_conversion_format', 'both'), 'avif'); ?>><?php _e('AVIF', 'image-optimizer'); ?></option>
                </select>
                <?php _e('Conversion Format', 'image-optimizer'); ?>
            </label>
            <script src="<?php echo plugin_dir_url(__FILE__) . '../assets/js/admin.js'; ?>"></script>
        </div>
        <?php
    }

    public static function register_settings() {
        register_setting('image_optimizer_settings', 'image_optimizer_webp_quality');
        register_setting('image_optimizer_settings', 'image_optimizer_avif_quality');
        register_setting('image_optimizer_settings', 'image_optimizer_excluded_sizes');

        add_settings_section(
            'image_optimizer_section',
            __('Quality Settings', 'image-optimizer'),
            null,
            'image-optimizer'
        );

        add_settings_field(
            'image_optimizer_webp_quality',
            __('WebP Quality', 'image-optimizer'),
            [__CLASS__, 'webp_quality_callback'],
            'image-optimizer',
            'image_optimizer_section'
        );

        add_settings_field(
            'image_optimizer_avif_quality',
            __('AVIF Quality', 'image-optimizer'),
            [__CLASS__, 'avif_quality_callback'],
            'image-optimizer',
            'image_optimizer_section'
        );

        add_settings_field(
            'image_optimizer_excluded_sizes',
            __('Excluded Sizes', 'image-optimizer'),
            [__CLASS__, 'excluded_sizes_callback'],
            'image-optimizer',
            'image_optimizer_section'
        );
    }

    public static function webp_quality_callback() {
        $webp_quality = get_option('image_optimizer_webp_quality', 80);
        echo '<input type="number" name="image_optimizer_webp_quality" value="' . esc_attr($webp_quality) . '" min="0" max="100">';
        echo '<p class="description">' . __('Set the quality for WebP images (0-100).', 'image-optimizer') . '</p>';
    }

    public static function avif_quality_callback() {
        $avif_quality = get_option('image_optimizer_avif_quality', 80);
        echo '<input type="number" name="image_optimizer_avif_quality" value="' . esc_attr($avif_quality) . '" min="0" max="100">';
        echo '<p class="description">' . __('Set the quality for AVIF images (0-100).', 'image-optimizer') . '</p>';
    }

    public static function excluded_sizes_callback() {
        $excluded_sizes = get_option('image_optimizer_excluded_sizes', []);
        $sizes = get_intermediate_image_sizes();
        foreach ($sizes as $size) {
            $checked = in_array($size, $excluded_sizes) ? 'checked' : '';
            echo '<label><input type="checkbox" name="image_optimizer_excluded_sizes[]" value="' . esc_attr($size) . '" ' . $checked . '> ' . esc_html($size) . '</label>';
        }
        echo '<p class="description">' . __('Select the image sizes to exclude from conversion.', 'image-optimizer') . '</p>';
    }

    public static function register_bulk_actions($bulk_actions) {
        $bulk_actions['convert_images'] = __('Convert to WebP/AVIF', 'image-optimizer');
        return $bulk_actions;
    }

    public static function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'convert_images') {
            return $redirect_to;
        }

        foreach ($post_ids as $post_id) {
            Image_Optimizer::schedule_conversion($post_id);
        }

        $redirect_to = add_query_arg('bulk_converted', count($post_ids), $redirect_to);
        return $redirect_to;
    }

    public static function add_convert_button($actions, $post) {
        $is_optimized = get_post_meta($post->ID, 'image_optimizer_optimized', true);
        if (($post->post_mime_type === 'image/jpeg' || $post->post_mime_type === 'image/png') && !$is_optimized) {
            $actions['convert_to_webp_avif'] = '<a href="#" class="convert-to-webp-avif" data-id="' . esc_attr($post->ID) . '">' . __('Convert to WebP/AVIF', 'image-optimizer') . '</a>';
        }
        return $actions;
    }

    public static function handle_single_conversion() {
        if (isset($_GET['convert_to_webp_avif'])) {
            $post_id = absint($_GET['convert_to_webp_avif']);
            $image_path = get_attached_file($post_id);
            try {
                $data = Image_Converter::convert_image($image_path);
                if ($data) {
                    update_post_meta($post_id, 'image_optimizer_optimized', true);
                    update_post_meta($post_id, 'image_optimizer_sizes', $data);
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
            wp_redirect(remove_query_arg('convert_to_webp_avif'));
            exit;
        }
    }

    public static function add_media_columns($columns) {
        $columns['image_optimizer'] = __('Image Optimizer', 'image-optimizer');
        return $columns;
    }

    public static function render_media_columns($column_name, $post_id) {
        if ($column_name === 'image_optimizer') {
            $is_optimized = get_post_meta($post_id, 'image_optimizer_optimized', true);
            $sizes = get_post_meta($post_id, 'image_optimizer_sizes', true);
            if ($is_optimized && $sizes) {
                echo sprintf(
                    __('Original: %s bytes<br>WebP: %s bytes<br>AVIF: %s bytes', 'image-optimizer'),
                    number_format_i18n($sizes['original_size']),
                    number_format_i18n($sizes['webp_size']),
                    number_format_i18n($sizes['avif_size'])
                );
            } else {
                echo '<a href="' . esc_url(add_query_arg('convert_to_webp_avif', $post_id)) . '" class="btn btn-primary">' . __('Optimize', 'image-optimizer') . '</a>';
            }
        }
    }

    public static function add_admin_notice($message, $type = 'success') {
        self::$notices[] = [
            'message' => $message,
            'type' => $type,
        ];
    }

    public static function display_admin_notices() {
        foreach (self::$notices as $notice) {
            echo '<div class="notice notice-' . esc_attr($notice['type']) . ' is-dismissible">';
            echo '<p>' . esc_html($notice['message']) . '</p>';
            echo '</div>';
        }
    }
}

Admin_UI::init();
