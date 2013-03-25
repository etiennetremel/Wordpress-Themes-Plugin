jQuery(document).ready(function($) {
                
    $('.browse-image').live('click', function() {
        var button = $(this),
            thumb = button.parent().find('.image');
        
        window.send_to_editor = function(html) {
            var imgurl = $('img', html).attr('src'),
                pat = /wp-image-([0-9]+)/g,
                imgID = pat.exec($('img', html).attr('class'));
            try {
                button.prev().val(imgID[1]);
                thumb.html($('img', html).attr('width','150').removeAttr('height'));
                tb_remove();
            } catch(e){
            }
        }
     
        tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
        return false;
    });

});