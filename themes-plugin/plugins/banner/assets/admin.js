jQuery(document).ready(function($) {
    /*
     * Draggable / Sortable:
     */
    $('#banner .items').sortable({
        revert: true
    });
    
    $('#banner .items').delegate('input[type=text], textarea', 'focus', function () {
        $('#banner .items').enableSelection();
    });

    $('#banner .items').delegate('input[type=text],textarea', 'blur', function () {
        $('#banner .items').disableSelection();
    });


    /*
     * Functions:
     */
    $('#banner .add-new-item').live('click', function(e) {
        e.preventDefault();
        var n = $('#banner .items .item').length+1;
        $('#banner .items').append( [
            '<div class="item">',
            '    <div class="image">',
            '        <div class="thumb"></div>',
            '        <div class="field"><p><label>Image ' + n + '</label></p><p><input type="hidden" name="images_id[]" value="" /><button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>',
            '    </div>',
            '    <div class="metas">',
            '        <p><label for="text">Text:</label></p>',
            '        <p><textarea name="texts[]" id="text" cols="50" rows="5"></textarea></p>',
            '    </div>',
            '</div>'
        ].join(''));
    });
    
    $('#banner .delete-image').live('click', function() {
        var r = confirm('Remove this image from the banner?');
        if(r) {
            $(this).parents('.item').remove();
        }
    });

    var media_manager;
    $('.browse-image').on('click', function(e) {
        e.preventDefault();

        var $button = $(this),
            thumb = $button.parents('.image'),
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