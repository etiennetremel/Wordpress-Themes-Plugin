jQuery(document).ready(function($) {
    /**
     * Draggable / Sortable:
     */
    $('#gallery-to-post .items').sortable({
        revert: true
    });
    
    $('#gallery-to-post .items').delegate('input[type=text], textarea', 'focus', function () {
        $('#gallery-to-post .items').enableSelection();
    });

    $('#gallery-to-post .items').delegate('input[type=text],textarea', 'blur', function () {
        $('#gallery-to-post .items').disableSelection();
    });
    
    
    /**
     * Functions:
     */
    $('#gallery-to-post').on('click', '.add-new-item', function(e) {
        e.preventDefault();
        var n = $('#gallery-to-post .items .item').length+1;
        $('#gallery-to-post .items').append( [
            '<div class="item">',
            '    <div class="image">',
            '        <div class="thumb"></div>',
            '        <div class="field"><p><label>Image ' + n + '</label></p><p><input type="hidden" name="images_id[]" value="" /><button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>',
            '    </div>',
            '    <div class="metas">',
            '        <p><label>Caption:</label></p>',
            '        <p><textarea name="captions[]" cols="50" rows="5"></textarea></p>',
            '    </div>',
            '</div>'
        ].join(''));
    });
    
    $('#gallery-to-post').on('click', '.delete-image', function() {
        var r = confirm('Remove this image from the gallery?');
        if(r) {
            $(this).parents('.item').remove();
        }
    });

    var media_manager;
    $('#gallery-to-post').on('click', '.browse-image', function(e) {
        e.preventDefault();

        var $button = $(this),
            $thumb = $button.parents('.image').find('.thumb'),
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