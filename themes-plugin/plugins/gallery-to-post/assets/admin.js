jQuery(document).ready(function($) {
	
	/*
	 * Draggable / Sortable:
	 */
	$('#gallery_to_post .items').sortable({
		revert: true
	});
	
	$('#gallery_to_post .items').delegate('input[type=text], textarea', 'focus', function () {
        $('#gallery_to_post .items').enableSelection();
    });

    $('#gallery_to_post .items').delegate('input[type=text],textarea', 'blur', function () {
        $('#gallery_to_post .items').disableSelection();
    });
	
	
	/*
	 * Functions:
	 */
	$('#gallery_to_post .add-new-item').live('click', function(e) {
		e.preventDefault();
		var n = $('#gallery_to_post .items .item').length+1;
		$('#gallery_to_post .items').append( [
			'<div class="item">',
			'	<div class="image">',
			'		<div class="thumb"></div>',
			'		<div class="field"><p><label>Image ' + n + '</label></p><p><input type="hidden" name="images_id[]" value="" /><button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>',
			'	</div>',
			'	<div class="metas">',
			'		<p><label>Caption:</label></p>',
	        '		<p><textarea name="captions[]" cols="50" rows="5"></textarea></p>',
			'	</div>',
			'</div>'
		].join(''));
	});
	
	$('#gallery_to_post .delete-image').live('click', function() {
		var r = confirm('Remove this image from the gallery?');
		if(r) {
			$(this).parents('.item').remove();
		}
	});
	
	$('#gallery_to_post .browse-image').live('click', function() {
		var postID = $('#gallery_to_post').attr('data-post-id'),
			button = $(this),
			thumb = button.parents('.item').find('.thumb');
			
		if(postID=="") postID = 1;
			
		window.send_to_editor = function(html) {
			var imgurl = $('img', html).attr('src'),
				pat = /wp-image-([0-9]+)/g,
				imgID = pat.exec($('img', html).attr('class'));
			try {
				button.prev().val(imgID[1]);
				thumb.html($('img', html).attr('width','100%').removeAttr('height'));
				tb_remove();
			} catch(e){
			}
		}
	 
		tb_show('', 'media-upload.php?post_id=' + postID + '&amp;type=image&amp;TB_iframe=1');
		return false;
	});
});