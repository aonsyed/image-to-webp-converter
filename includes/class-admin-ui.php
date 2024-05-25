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
            <button id="schedule-bulk-conversion" class="button button-primary"><?php _e('Schedule Bulk Conversion', 'image-optimizer'); ?></button>
            <label>
                <input type="checkbox" id="toggle-scheduler" <?php checked(get_option('image_optimizer_enable_scheduler', false)); ?>>
                <?php _e('Enable Scheduler', 'image-optimizer'); ?>
            </label>
            <label>
                <input type="checkbox" id="toggle-conversion-on-upload" <?php checked(get_option('image_optimizer_convert_on_upload', true)); ?>>
                <?php _e('Convert on Upload', 'image-optimizer'); ?>
            </label>
            <style>
                .image-optimizer-settings input[type="number"] {
                    width: 60px;
                }
                .image-optimizer-settings label {
                    display: block;
                    margin-bottom: 10px;
                }
                .image-optimizer-settings .description {
                    font-size: 14px;
                    color: #666;
                }
            </style>
            <script>
                jQuery(document).ready(function($) {
                    $('#schedule-bulk-conversion').on('click', function() {
                        $.ajax({
                            url: imageOptimizerAjax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'schedule_bulk_conversion',
                                nonce: imageOptimizerAjax.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('<?php _e('Bulk conversion scheduled successfully', 'image-optimizer'); ?>');
                                } else {
                                    alert('<?php _e('Error scheduling bulk conversion', 'image-optimizer'); ?>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error(error);
                                alert('<?php _e('Error scheduling bulk conversion', 'image-optimizer'); ?>');
                            }
                        });
                    });

                    $('#toggle-scheduler').on('change', function() {
                        $.ajax({
                            url: imageOptimizerAjax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'toggle_scheduler',
                                nonce: imageOptimizerAjax.nonce,
                                enabled: $(this).is(':checked')
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('<?php _e('Scheduler toggled successfully', 'image-optimizer'); ?>');
                                } else {
                                    alert('<?php _e('Error toggling scheduler', 'image-optimizer'); ?>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error(error);
                                alert('<?php _e('Error toggling scheduler', 'image-optimizer'); ?>');
                            }
                        });
                    });

                    $('#toggle-conversion-on-upload').on('change', function() {
                        $.ajax({
                            url: imageOptimizerAjax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'toggle_conversion_on_upload',
                                nonce: imageOptimizerAjax.nonce,
                                enabled: $(this).is(':checked')
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('<?php _e('Conversion on upload toggled successfully', 'image-optimizer'); ?>');
                                } else {
                                    alert('<?php _e('Error toggling conversion on upload', 'image-optimizer'); ?>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error(error);
                                alert('<?php _e('Error toggling conversion on upload', 'image-optimizer'); ?>');
                            }
                        });
                    });
                });
            </script>
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
        if ($post->post_mime_type === 'image/jpeg' || $post->post_mime_type === 'image/png') {
            $actions['convert_to_webp_avif'] = '<a href="#" class="convert-to-webp-avif" data-id="' . esc_attr($post->ID) . '">' . __('Convert to WebP/AVIF', 'image-optimizer') . '</a>';
        }
        return $actions;
    }

    public static function handle_single_conversion() {
        if (isset($_GET['convert_to_webp_avif'])) {
            $post_id = absint($_GET['convert_to_webp_avif']);
            $image_path = get_attached_file($post_id);
            try {
                Image_Converter::convert_image($image_path);
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
            wp_redirect(remove_query_arg('convert_to_webp_avif'));
            exit;
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
