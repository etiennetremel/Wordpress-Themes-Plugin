jQuery(document).ready(function($) {
    var media_manager;
    
    $(document).on('click', '.browse-image', function(e) {
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
        media_manager.on( 'select', function() {
            attachment = media_manager.state().get('selection').first().toJSON();
            $button.prev().val(attachment.id);
            var $img = $('<img />').attr({
                width: '100%',
                src: attachment.url
            });
            $thumb.html($img);
        });

        media_manager.open();
    });
});