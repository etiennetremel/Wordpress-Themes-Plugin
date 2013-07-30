jQuery(document).ready(function($) {
    $('.hscroll').hScroll({
        autoscroll: true,
        navigation: false
    });
});

/**
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

      return this.each(function () {
        var $this=$(this),
            $hscrollInner = $this.find('.hscroll-inner'),
            innerWidth=0;

        $this.find('.item').each(function () {
          $(this).data('m', $(this).css('margin') );
        });

        $this.on('click', '.control.left', function (e) {
          var $item = $hscrollInner.find('.item').last(),
              m = $item.data('m');

          $item.stop().prependTo($hscrollInner);
          $item.css('margin', m);

          scrollElement($hscrollInner);
        });

        $this.on('click', '.control.right', function (e) {
          var $item = $hscrollInner.find('.item').first(),
              m = $item.data('m');

          $item.stop().appendTo($hscrollInner);
          $item.css('margin', m);

          scrollElement($hscrollInner);
        });

        if( settings.autoscroll ) {
          var $items = $this.find('.item');
          $items.each(function (index) {
            var $item = $(this),
                $image = $item.find('img');
            $image.one('load', function() {
                $item.width( $(this).width() );
                innerWidth += $item.outerWidth();
                if (index==$items.length-1) {
                    $hscrollInner.width(innerWidth + 'px');
                    scrollElement($hscrollInner);
                }
            }).each(function () {
              if (this.complete) {
                $image.load();
              }
            });
          });
        }
      });
    }
  };

  function scrollElement ($this) {
    var $elt = $this.find('.item').first();

    $elt.animate({
      'marginLeft': '-=1'
    }, 40, function() {
      if (parseInt($elt.css('marginLeft'), 10) < -$elt.width()) {
        $elt.appendTo($this).css('marginLeft', 0);
      }
      scrollElement( $this );
    });
  }

  $.fn.hScroll = function (method) {
    if (methods[method]) {
      return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if (typeof method === 'object' || ! method) {
      return methods.init.apply( this, arguments );
    } else {
      $.error( 'Method ' +  method + ' does not exist on jQuery.hScroll' );
    }
  };
})(jQuery);