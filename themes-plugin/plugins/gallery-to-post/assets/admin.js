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