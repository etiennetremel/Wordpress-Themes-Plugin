jQuery(document).ready(function($) {

    //Remove Image
    $('body').on('click', '.delete-image', function() {
        var r = confirm('Remove this image from the gallery?');
        if(r) {
            $(this).parent().find('.image img').remove();
        }
    });

    //Add Image using Media Manager
    var media_manager;
    $('body').on('click', '.select-image', function(e) {
        e.preventDefault();

        var $button = $(this),
            $thumb = $button.parent().find('.image'),
            send_attachment_bkp = wp.media.editor.send.attachment;

        if ( ! media_manager) {
            media_manager = wp.media.frames.media_manager = wp.media({
                title:    'Choose an image',
                library:  { type : 'image'},
                button:   { text : 'Select' },
                multiple: false
            });
        }

        media_manager.off('select');
        media_manager.on('select', function() {
            var attachment = media_manager.state().get('selection').first().toJSON();
            var $img = $('<img />').attr({
                'width': '100',
                'src': attachment.url
            });
            $thumb.html($img);
            $button.prev().val(attachment.id);
        });

        media_manager.open();
    });
});