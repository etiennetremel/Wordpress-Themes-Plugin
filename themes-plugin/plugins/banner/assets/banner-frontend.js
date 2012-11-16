jQuery(function($) {
	$('.carousel').carousel();
});

/* BOOTSTRAP TRANSITION */
!function(a){a(function(){a.support.transition=(function(){var c=document.body||document.documentElement,d=c.style,b=d.transition!==undefined||d.WebkitTransition!==undefined||d.MozTransition!==undefined||d.MsTransition!==undefined||d.OTransition!==undefined;
return b&&{end:(function(){var e="TransitionEnd";if(a.browser.webkit){e="webkitTransitionEnd";}else{if(a.browser.mozilla){e="transitionend";}else{if(a.browser.opera){e="oTransitionEnd";
}}}return e;}())};})();});}(window.jQuery);

/* BOOTSTRAP CAROUSEL */
!function(a){var b=function(d,c){this.$element=a(d);this.options=c;this.options.slide&&this.slide(this.options.slide);this.options.pause=="hover"&&this.$element.on("mouseenter",a.proxy(this.pause,this)).on("mouseleave",a.proxy(this.cycle,this));
};b.prototype={cycle:function(){this.options.interval&&(this.interval=setInterval(a.proxy(this.next,this),this.options.interval));return this;},to:function(g){var c=this.$element.find(".active"),d=c.parent().children(),e=d.index(c),f=this;
if(g>(d.length-1)||g<0){return;}if(this.sliding){return this.$element.one("slid",function(){f.to(g);});}if(e==g){return this.pause().cycle();}return this.slide(g>e?"next":"prev",a(d[g]));
},pause:function(){clearInterval(this.interval);this.interval=null;return this;},next:function(){if(this.sliding){return;}return this.slide("next");},prev:function(){if(this.sliding){return;
}return this.slide("prev");},slide:function(j,d){if(!a.support.transition&&this.$element.hasClass("slide")){this.$element.find(".item").stop(true,true);
}var l=this.$element.find(".active"),c=d||l[j](),i=this.interval,k=j=="next"?"left":"right",f=j=="next"?"first":"last",g=this,h=a.Event("slide");this.sliding=true;
i&&this.pause();c=c.length?c:this.$element.find(".item")[f]();if(c.hasClass("active")){return;}if(a.support.transition&&this.$element.hasClass("slide")){this.$element.trigger(h);
if(h.isDefaultPrevented()){return;}c.addClass(j);c[0].offsetWidth;l.addClass(k);c.addClass(k);this.$element.one(a.support.transition.end,function(){c.removeClass([j,k].join(" ")).addClass("active");
l.removeClass(["active",k].join(" "));g.$element.find(".navigation li").removeClass("active-item");g.$element.find(".navigation li:eq("+g.$element.find(".active").index()+")").addClass("active-item");
g.sliding=false;setTimeout(function(){g.$element.trigger("slid");},0);});}else{if(!a.support.transition&&this.$element.hasClass("slide")){this.$element.trigger(h);
if(h.isDefaultPrevented()){return;}l.animate({left:(k=="right"?"100%":"-100%")},600,function(){l.removeClass("active");g.$element.find(".navigation li").removeClass("active-item");
g.$element.find(".navigation li:eq("+g.$element.find(".active").index()+")").addClass("active-item");g.sliding=false;setTimeout(function(){g.$element.trigger("slid");
},0);});c.addClass(j).css({left:(k=="right"?"-100%":"100%")}).animate({left:"0"},600,function(){c.removeClass(j).addClass("active");});}else{this.$element.trigger(h);
if(h.isDefaultPrevented()){return;}l.removeClass("active");c.addClass("active");this.sliding=false;this.$element.trigger("slid");}}i&&this.cycle();return this;
}};a.fn.carousel=function(c){return this.each(function(){var f=a(this),e=f.data("carousel"),d=a.extend({},a.fn.carousel.defaults,typeof c=="object"&&c);
if(!e){f.data("carousel",(e=new b(this,d)));}if(typeof c=="number"){e.to(c);}else{if(typeof c=="string"||(c=d.slide)){e[c]();}else{if(d.interval){e.cycle();
}}}});};a.fn.carousel.defaults={interval:5000,pause:"hover"};a.fn.carousel.Constructor=b;a(function(){a("body").on("click.carousel.data-api","[data-slide], [data-slide-to]",function(h){var g=a(this),d,c=a(g.attr("data-target")||(d=g.attr("href"))&&d.replace(/.*(?=#[^\s]+$)/,"")),f=(g.attr("data-slide")&&(!c.data("modal")&&a.extend({},c.data(),g.data()))||parseInt(g.attr("data-slide-to")));
c.carousel(f);h.preventDefault();});});}(window.jQuery);