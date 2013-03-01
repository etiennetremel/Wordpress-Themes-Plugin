jQuery(function($) {
	$('.hscroll').hScroll({
		autoscroll: true
	});
});


/*
 * HSCROLL v0.1: to be improved.
 */
(function($){
	var methods = {
		init : function( options ) {
			var defaults = {
					autoscroll: false,
					navigation: true
				},
				settings = $.extend({}, defaults, options);

			return this.each( function() {
				var $this=$(this),
					$hscrollInner = $this.find('.hscroll-inner'),
					innerWidth=0;

				$this.find('.control.left').click(function(e) {
					$this.find('.item').last().prependTo( $this.find('.hscroll-inner') );
				});

				$this.find('.control.right').click(function(e) {
					$this.find('.item').first().appendTo( $this.find('.hscroll-inner') );
				});

				if( settings.autoscroll ) {
					$this.find('.item').each(function() {
						innerWidth += $(this).width();
					});
					$hscrollInner.width( innerWidth + 'px' );
					scrollElement( $hscrollInner );
				}
			});
		}
	};

	function scrollElement( $this ) {
		var $elt = $this.find('.item').first();

		$elt.animate({
			'margin-left': '-=1'
		}, 40, function() {
			if(parseInt($elt.css('margin-left')) < -$elt.width())
				$elt.appendTo($this).css('margin-left', 0);
			scrollElement( $this );
		});
	}

	$.fn.hScroll = function( method ) {
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.hScroll' );
		}
	};
})(jQuery);