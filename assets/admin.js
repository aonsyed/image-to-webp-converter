jQuery(document).ready(function($) {
    $('.convert-to-webp-avif').on('click', function(e) {
        e.preventDefault();
        var attachmentId = $(this).data('id');
        var $button = $(this);

        $.ajax({
            url: imageOptimizerAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'convert_image',
                nonce: imageOptimizerAjax.nonce,
                attachment_id: attachmentId
            },
            beforeSend: function() {
                $button.text('Converting...');
                $button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $button.text('Converted');
                } else {
                    $button.text('Error');
                    console.error(response.data);
                }
                $button.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                $button.text('Error');
                console.error(error);
                $button.prop('disabled', false);
            }
        });
    });
});
