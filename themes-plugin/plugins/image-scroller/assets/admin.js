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
			'	<div class="image">',
			'		<div class="thumb"></div>',
			'		<div class="field"><p><label>Image ' + n + '</label></p><p><input type="hidden" name="images_id[]" value="" /><button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>',
			'	</div>',
			'	<div class="metas">',
			'		<p><label for="link_to">Link To:</label></p>',
	        '		<p><input type="text" name="link_to[]" id="link_to" value="" /></p>',
			'	</div>',
			'</div>'
		].join(''));
	});
	
	$('#image-scroller .delete-image').live('click', function() {
		var r = confirm('Remove this image from the scroller?');
		if(r) {
			$(this).parents('.item').remove();
		}
	});
	
	$('#image-scroller .browse-image').live('click', function() {
		var postID = $('#image-scroller').attr('data-post-id'),
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