jQuery(document).ready(function($) {
    /*
     * Draggable / Sortable:
     */
    $('#image-scroller .items').sortable({
        revert: true
    });
    
    $('#image-scroller .items').delegate('input[type=text]', 'focus', function () {
        $('#image-scroller .items').enableSelection();
    });

    $('#image-scroller .items').delegate('input[type=text]', 'blur', function () {
        $('#image-scroller .items').disableSelection();
    });
    
    
    /*
     * Functions:
     */
    $('#image-scroller .add-new-item').live('click', function(e) {
        e.preventDefault();
        var n = $('#image-scroller .items .item').length+1;
        $('#image-scroller .items').append( [
            '<div class="item">',
            '    <div class="image">',
            '        <div class="thumb"></div>',
            '        <div class="field"><p><label>Image ' + n + '</label></p><p><input type="hidden" name="images_id[]" value="" /><button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>',
            '    </div>',
            '    <div class="metas">',
            '        <p><label for="link_to">Link To:</label></p>',
            '        <p><input type="text" name="link_to[]" id="link_to" value="" /></p>',
            '    </div>',
            '</div>'
        ].join(''));
    });
    
    $('#image-scroller .delete-image').live('click', function() {
        var r = confirm('Remove this image from the scroller?');
        if(r) {
            $(this).parents('.item').remove();
        }
    });

    var media_manager;
    $('.browse-image').on('click', function(e) {
        e.preventDefault();

        var $button = $(this),
            thumb = $button.parents('.item').find('.thumb'),
            send_attachment_bkp = wp.media.editor.send.attachment;
        
        if (media_manager) {
            media_manager.open();
            return;
        }

        media_manager = wp.media.frames.media_manager = wp.media({
            title: 'Choose an image',
            library : { type : 'image'},
            button : { text : 'Select' },
            multiple: false
        });

        media_manager.on('select', function() {
            var attachment = media_manager.state().get('selection').first().toJSON();
            var img = $('<img />').attr({
                'width': '100',
                'src': attachment.url
            });
            thumb.html(img);
            $button.prev().val(attachment.id);
        });

        media_manager.open();
    });
});