jQuery(function($) {
	$('.twitter-widget .slide').lSlider();
});


/*
 * LIST_SLIDER v0.1.
 * Options: 
 * 		- interval (in ms), time to wait before showing the next slide
 * 		- speed (in ms), animation speed
 */
(function($){
	var methods = {
		init: function( options ) {
			var defaults = {
					interval: 3500,
					speed: 200
				},
				settings = $.extend({}, defaults, options);

			return this.each( function() {
				var $this = $(this),
					$feed = $this.find('.feed'),
					width = $this.width();

				$this.data('lSlider', settings);

				$this.find('li').css({
					'width': width,
					'float': 'left'
				});

				$feed.width(width*2);

				$this.css({
					'height': $this.find('li').first().height(),
					'overflow': 'hidden'
				});

				setTimeout(function() { methods.slide($this); }, settings.interval);
			});
		},

		slide: function( $this ) {
			var $elt = $this.find('li').first(),
				settings = $this.data('lSlider');
			
			$this.animate({
				'height': $elt.next().height()
			}, settings.speed);

			$elt.animate({
				'margin-left': -$this.width() + 'px'
			}, settings.speed, function() {
				$(this).appendTo($this.find('.feed')).css('margin-left',0);
				setTimeout(function() { methods.slide($this); }, settings.interval);
			});
		}
	};

	$.fn.lSlider = function( method ) {
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.lSlider' );
		}
	};
})(jQuery);