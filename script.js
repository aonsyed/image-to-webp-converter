jQuery(document).ready(function($) {
    function processBatch(offset) {
        var form = $('#image-to-webp-form');
        var data = form.serialize() + '&offset=' + offset;

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                response.data.progress_updates.forEach(function(update) {
                    $('#progress-bar-inner').css('width', update.progress + '%').text(update.progress + '%');
                    $('#progress-messages').append('<p>' + update.message + ' - ' + update.file + '</p>');
                });
                if (response.data.next_offset !== undefined) {
                    processBatch(response.data.next_offset);
                } else {
                    alert(response.data.message);
                    $('#image-to-webp-progress').hide();
                }
            } else {
                alert('Error: ' + response.data);
            }
        }, 'json').fail(function(xhr, status, error) {
            alert('An error occurred: ' + error);
        });
    }

    $('#image-to-webp-form').on('submit', function(e) {
        e.preventDefault();
        $('#image-to-webp-progress').show();
        $('#progress-bar-inner').css('width', '0%').text('0%');
        $('#progress-messages').empty();
        processBatch(0);
    });

    $('#convert-to-webp-button').on('click', function() {
        var nonce = $('#_wpnonce').val();
        var data = {
            action: 'image_to_webp_convert',
            nonce: nonce,
            format: ['webp']
        };

        $('#image-to-webp-progress').show();
        $('#progress-bar-inner').css('width', '0%').text('0%');
        $('#progress-messages').empty();

        processBatch(0);
    });
});
