jQuery(document).ready(function($) {
    $('#image-to-webp-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();

        $('#image-to-webp-progress').show();
        $('#progress-bar-inner').css('width', '0%').text('0%');

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                response.data.progress_updates.forEach(function(update) {
                    $('#progress-bar-inner').css('width', update.progress + '%').text(update.progress + '%');
                });
                alert(response.data.message);
                $('#image-to-webp-progress').hide();
            } else {
                alert('Error: ' + response.data);
            }
        }, 'json').fail(function(xhr, status, error) {
            alert('An error occurred: ' + error);
        });
    });

    $('#convert-to-webp-button').on('click', function() {
        var nonce = $('#_wpnonce').val();
        var data = {
            action: 'image_to_webp_convert',
            nonce: nonce,
            format: ['webp']
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                response.data.progress_updates.forEach(function(update) {
                    $('#progress-bar-inner').css('width', update.progress + '%').text(update.progress + '%');
                });
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data);
            }
        }, 'json').fail(function(xhr, status, error) {
            alert('An error occurred: ' + error);
        });
    });
});
