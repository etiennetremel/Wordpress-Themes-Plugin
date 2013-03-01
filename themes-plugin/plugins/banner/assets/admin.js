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
			'	<div class="image">',
			'		<div class="thumb"></div>',
			'		<div class="field"><p><label>Image ' + n + '</label></p><p><input type="hidden" name="images_id[]" value="" /><button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>',
			'	</div>',
			'	<div class="metas">',
			'		<p><label for="text">Text:</label></p>',
	        '		<p><textarea name="texts[]" id="text" cols="50" rows="5"></textarea></p>',
			'	</div>',
			'</div>'
		].join(''));
	});
	
	$('#banner .delete-image').live('click', function() {
		var r = confirm('Remove this image from the banner?');
		if(r) {
			$(this).parents('.item').remove();
		}
	});
	
	$('#banner .browse-image').live('click', function() {
		var postID = $('#banner').attr('data-post-id'),
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
	 
		tb_show('', 'media-upload.php?type=image&amp;TB_iframe=1');
		return false;
	});	 
 
 
});