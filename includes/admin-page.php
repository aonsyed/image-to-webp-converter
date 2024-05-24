<?php
if (!defined('ABSPATH')) exit;

function image_to_webp_admin_menu() {
    add_management_page(__('Image to WebP/AVIF Converter', 'image-to-webp'), __('Image Converter', 'image-to-webp'), 'manage_options', 'image-to-webp', 'image_to_webp_admin_page');
}
add_action('admin_menu', 'image_to_webp_admin_menu');

function image_to_webp_admin_page() {
    if (!class_exists('Imagick')) echo '<div class="error"><p>' . __('Imagick is not available on this server.', 'image-to-webp') . '</p></div>';
    ?>
    <div class="wrap">
        <h1><?php _e('Image to WebP/AVIF Converter', 'image-to-webp'); ?></h1>
        <form id="image-to-webp-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <?php wp_nonce_field('image_to_webp_nonce', 'image_to_webp_nonce_field'); ?>
            <input type="hidden" name="action" value="image_to_webp_convert">
            <table class="form-table">
                <tr valign="top"><th scope="row"><?php _e('Format', 'image-to-webp'); ?></th><td><label><input type="checkbox" name="format[]" value="webp" checked> WebP</label><br><label><input type="checkbox" name="format[]" value="avif"> AVIF</label></td></tr>
                <tr valign="top"><th scope="row"><?php _e('Options', 'image-to-webp'); ?></th><td><label><input type="checkbox" name="stop_on_failure"> <?php _e('Stop on failure', 'image-to-webp'); ?></label><br><label><input type="checkbox" name="skip_larger"> <?php _e('Skip if resultant file is larger', 'image-to-webp'); ?></label></td></tr>
                <tr valign="top"><th scope="row"><?php _e('Year', 'image-to-webp'); ?></th><td><input type="number" name="year" placeholder="YYYY"></td></tr>
                <tr valign="top"><th scope="row"><?php _e('Month', 'image-to-webp'); ?></th><td><input type="number" name="month" placeholder="MM"></td></tr>
                <tr valign="top"><th scope="row"><?php _e('Log Path', 'image-to-webp'); ?></th><td><input type="text" name="log" placeholder="/path/to/logfile.log"></td></tr>
                <tr valign="top"><th scope="row"><?php _e('Batch Size', 'image-to-webp'); ?></th><td><input type="number" name="batch_size" placeholder="10" value="10"></td></tr>
            </table>
            <p class="submit"><button type="submit" class="button button-primary"><?php _e('Start Conversion', 'image-to-webp'); ?></button></p>
        </form>
        <div id="image-to-webp-progress" style="display:none;">
            <h2><?php _e('Progress', 'image-to-webp'); ?></h2>
            <div id="progress-bar" style="width: 100%; background: #ccc;"><div id="progress-bar-inner" style="width: 0%; background: #4caf50; text-align: center; color: white;">0%</div></div>
            <div id="progress-messages"></div>
        </div>
    </div>
    <?php
}

function image_to_webp_admin_enqueue() {
    wp_enqueue_script('image-to-webp-script', plugins_url('../assets/script.js', __FILE__), ['jquery'], null, true);
    wp_enqueue_style('image-to-webp-style', plugins_url('../assets/style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'image_to_webp_admin_enqueue');

function add_convert_button_to_media_library() {
    if (!current_user_can('manage_options')) return;
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.attachments-browser').on('click', '.convert-to-webp-button', function(e) {
                e.preventDefault();
                var attachmentId = $(this).data('attachment-id');
                processSingleImage(attachmentId);
            });

            function processSingleImage(attachmentId) {
                $.post(ajaxurl, {
                    action: 'image_to_webp_convert_single',
                    attachment_id: attachmentId,
                    _ajax_nonce: '<?php echo wp_create_nonce('image_to_webp_convert_single'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Conversion completed!');
                    } else {
                        alert('Error: ' + response.data);
                    }
                }).fail(function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                });
            }
        });
    </script>
    <?php
    echo '<button class="button convert-to-webp-button" data-attachment-id="' . get_the_ID() . '">' . __('Convert to WebP/AVIF', 'image-to-webp') . '</button>';
}
add_action('wp_footer', 'add_convert_button_to_media_library');
?>
