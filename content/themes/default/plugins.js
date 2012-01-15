// usage: log('inside coolFunc', this, arguments);
// paulirish.com/2009/log-a-lightweight-wrapper-for-consolelog/
window.log = function(){
  log.history = log.history || [];   // store logs to an array for reference
  log.history.push(arguments);
  if(this.console) {
    arguments.callee = arguments.callee.caller;
    var newarr = [].slice.call(arguments);
    (typeof console.log === 'object' ? log.apply.call(console.log, console, newarr) : console.log.apply(console, newarr));
  }
};

// make it safe to use console.log always
(function(b){function c(){}for(var d="assert,clear,count,debug,dir,dirxml,error,exception,firebug,group,groupCollapsed,groupEnd,info,log,memoryProfile,memoryProfileEnd,profile,profileEnd,table,time,timeEnd,timeStamp,trace,warn".split(","),a;a=d.pop();){b[a]=b[a]||c}})((function(){try
{console.log();return window.console;}catch(err){return window.console={};}})());


!function(c){var a;c(document).ready(function(){c.support.transition=(function(){var e=document.body||document.documentElement,f=e.style,d=f.transition!==undefined||f.WebkitTransition!==undefined||f.MozTransition!==undefined||f.MsTransition!==undefined||f.OTransition!==undefined;return d})();if(c.support.transition){a="TransitionEnd";if(c.browser.webkit){a="webkitTransitionEnd"}else{if(c.browser.mozilla){a="transitionend"}else{if(c.browser.opera){a="oTransitionEnd"}}}}});var b=function(e,d){this.settings=c.extend({},c.fn.alert.defaults,d);this.$element=c(e).delegate(this.settings.selector,"click",this.close)};b.prototype={close:function(g){var f=c(this).parent(".alert-message");g&&g.preventDefault();f.removeClass("in");function d(){f.remove()}c.support.transition&&f.hasClass("fade")?f.bind(a,d):d()}};c.fn.alert=function(d){if(d===true){return this.data("alert")}return this.each(function(){var e=c(this);if(typeof d=="string"){return e.data("alert")[d]()}c(this).data("alert",new b(this,d))})};c.fn.alert.defaults={selector:".close"};c(document).ready(function(){new b(c("body"),{selector:".alert-message[data-alert] .close"})})}(window.jQuery||window.ender);!function(b){function c(f,h){var i="disabled",e=b(f),g=e.data();h=h+"Text";g.resetText||e.data("resetText",e.html());e.html(g[h]||b.fn.button.defaults[h]);h=="loadingText"?e.addClass(i).attr(i,i):e.removeClass(i).removeAttr(i)}function a(d){b(d).toggleClass("active")}b.fn.button=function(d){return this.each(function(){if(d=="toggle"){return a(this)}d&&c(this,d)})};b.fn.button.defaults={loadingText:"loading..."};b(function(){b("body").delegate(".btn[data-toggle]","click",function(){b(this).button("toggle")})})}(window.jQuery||window.ender);!function(f){var b;f(document).ready(function(){f.support.transition=(function(){var j=document.body||document.documentElement,k=j.style,i=k.transition!==undefined||k.WebkitTransition!==undefined||k.MozTransition!==undefined||k.MsTransition!==undefined||k.OTransition!==undefined;return i})();if(f.support.transition){b="TransitionEnd";if(f.browser.webkit){b="webkitTransitionEnd"}else{if(f.browser.mozilla){b="transitionend"}else{if(f.browser.opera){b="oTransitionEnd"}}}}});var a=function(j,i){this.settings=f.extend({},f.fn.modal.defaults,i);this.$element=f(j).delegate(".close","click.modal",f.proxy(this.hide,this));if(this.settings.show){this.show()}return this};a.prototype={toggle:function(){return this[!this.isShown?"show":"hide"]()},show:function(){var i=this;this.isShown=true;this.$element.trigger("show");e.call(this);d.call(this,function(){var j=f.support.transition&&i.$element.hasClass("fade");i.$element.appendTo(document.body).show();if(j){i.$element[0].offsetWidth}i.$element.addClass("in");j?i.$element.one(b,function(){i.$element.trigger("shown")}):i.$element.trigger("shown")});return this},hide:function(j){j&&j.preventDefault();if(!this.isShown){return this}var i=this;this.isShown=false;e.call(this);this.$element.trigger("hide").removeClass("in");f.support.transition&&this.$element.hasClass("fade")?h.call(this):g.call(this);return this}};function h(){var i=this,j=setTimeout(function(){i.$element.unbind(b);g.call(i)},500);this.$element.one(b,function(){clearTimeout(j);g.call(i)})}function g(i){this.$element.hide().trigger("hidden");d.call(this)}function d(l){var k=this,j=this.$element.hasClass("fade")?"fade":"";if(this.isShown&&this.settings.backdrop){var i=f.support.transition&&j;this.$backdrop=f('<div class="modal-backdrop '+j+'" />').appendTo(document.body);if(this.settings.backdrop!="static"){this.$backdrop.click(f.proxy(this.hide,this))}if(i){this.$backdrop[0].offsetWidth}this.$backdrop.addClass("in");i?this.$backdrop.one(b,l):l()}else{if(!this.isShown&&this.$backdrop){this.$backdrop.removeClass("in");f.support.transition&&this.$element.hasClass("fade")?this.$backdrop.one(b,f.proxy(c,this)):c.call(this)}else{if(l){l()}}}}function c(){this.$backdrop.remove();this.$backdrop=null}function e(){var i=this;if(this.isShown&&this.settings.keyboard){f(document).bind("keyup.modal",function(j){if(j.which==27){i.hide()}})}else{if(!this.isShown){f(document).unbind("keyup.modal")}}}f.fn.modal=function(i){var j=this.data("modal");if(!j){if(typeof i=="string"){i={show:/show|toggle/.test(i)}}return this.each(function(){f(this).data("modal",new a(this,i))})}if(i===true){return j}if(typeof i=="string"){j[i]()}else{if(j){j.toggle()}}return this};f.fn.modal.Modal=a;f.fn.modal.defaults={backdrop:false,keyboard:false,show:false};f(document).ready(function(){f("body").delegate("[data-controls-modal]","click",function(j){j.preventDefault();var i=f(this).data("show",true);f("#"+i.attr("data-controls-modal")).modal(i.data())})})}(window.jQuery||window.ender);!function(b){var c=b(window);function a(e,d){var f=b.proxy(this.processScroll,this);this.$topbar=b(e);this.selector=d||"li > a";this.refresh();this.$topbar.delegate(this.selector,"click",f);c.scroll(f);this.processScroll()}a.prototype={refresh:function(){this.targets=this.$topbar.find(this.selector).map(function(){var d=b(this).attr("href");return/^#\w/.test(d)&&b(d).length?d:null});this.offsets=b.map(this.targets,function(d){return b(d).offset().top})},processScroll:function(){var g=c.scrollTop()+10,f=this.offsets,d=this.targets,h=this.activeTarget,e;for(e=f.length;e--;){h!=d[e]&&g>=f[e]&&(!f[e+1]||g<=f[e+1])&&this.activateButton(d[e])}},activateButton:function(d){this.activeTarget=d;this.$topbar.find(this.selector).parent(".active").removeClass("active");this.$topbar.find(this.selector+'[href="'+d+'"]').parent("li").addClass("active")}};b.fn.scrollSpy=function(d){var e=this.data("scrollspy");if(!e){return this.each(function(){b(this).data("scrollspy",new a(this,d))})}if(d===true){return e}if(typeof d=="string"){e[d]()}return this};b(document).ready(function(){b("body").scrollSpy("[data-scrollspy] li > a")})}(window.jQuery||window.ender);!function(c){function b(e,d){d.find("> .active").removeClass("active").find("> .dropdown-menu > .active").removeClass("active");e.addClass("active");if(e.parent(".dropdown-menu")){e.closest("li.dropdown").addClass("active")}}function a(i){var h=c(this),f=h.closest("ul:not(.dropdown-menu)"),d=h.attr("href"),g,j;if(/^#\w+/.test(d)){i.preventDefault();if(h.parent("li").hasClass("active")){return}g=f.find(".active a").last()[0];j=c(d);b(h.parent("li"),f);b(j,j.parent());h.trigger({type:"change",relatedTarget:g})}}c.fn.tabs=c.fn.pills=function(d){return this.each(function(){c(this).delegate(d||".tabs li > a, .pills > li > a","click",a)})};c(document).ready(function(){c("body").tabs("ul[data-tabs] li > a, ul[data-pills] > li > a")})}(window.jQuery||window.ender);!function(c){var a;c(document).ready(function(){c.support.transition=(function(){var f=document.body||document.documentElement,g=f.style,e=g.transition!==undefined||g.WebkitTransition!==undefined||g.MozTransition!==undefined||g.MsTransition!==undefined||g.OTransition!==undefined;return e})();if(c.support.transition){a="TransitionEnd";if(c.browser.webkit){a="webkitTransitionEnd"}else{if(c.browser.mozilla){a="transitionend"}else{if(c.browser.opera){a="oTransitionEnd"}}}}});var d=function(f,e){this.$element=c(f);this.options=e;this.enabled=true;this.fixTitle()};d.prototype={show:function(){var j,f,i,e,h,g;if(this.hasContent()&&this.enabled){h=this.tip();this.setContent();if(this.options.animate){h.addClass("fade")}h.remove().css({top:0,left:0,display:"block"}).prependTo(document.body);j=c.extend({},this.$element.offset(),{width:this.$element[0].offsetWidth,height:this.$element[0].offsetHeight});f=h[0].offsetWidth;i=h[0].offsetHeight;e=b(this.options.placement,this,[h[0],this.$element[0]]);switch(e){case"below":g={top:j.top+j.height+this.options.offset,left:j.left+j.width/2-f/2};break;case"above":g={top:j.top-i-this.options.offset,left:j.left+j.width/2-f/2};break;case"left":g={top:j.top+j.height/2-i/2,left:j.left-f-this.options.offset};break;case"right":g={top:j.top+j.height/2-i/2,left:j.left+j.width+this.options.offset};break}h.css(g).addClass(e).addClass("in")}},setContent:function(){var e=this.tip();e.find(".twipsy-inner")[this.options.html?"html":"text"](this.getTitle());e[0].className="twipsy"},hide:function(){var f=this,g=this.tip();g.removeClass("in");function e(){g.remove()}c.support.transition&&this.$tip.hasClass("fade")?g.bind(a,e):e()},fixTitle:function(){var e=this.$element;if(e.attr("title")||typeof(e.attr("data-original-title"))!="string"){e.attr("data-original-title",e.attr("title")||"").removeAttr("title")}},hasContent:function(){return this.getTitle()},getTitle:function(){var g,e=this.$element,f=this.options;this.fixTitle();if(typeof f.title=="string"){g=e.attr(f.title=="title"?"data-original-title":f.title)}else{if(typeof f.title=="function"){g=f.title.call(e[0])}}g=(""+g).replace(/(^\s*|\s*$)/,"");return g||f.fallback},tip:function(){return this.$tip=this.$tip||c('<div class="twipsy" />').html(this.options.template)},validate:function(){if(!this.$element[0].parentNode){this.hide();this.$element=null;this.options=null}},enable:function(){this.enabled=true},disable:function(){this.enabled=false},toggleEnabled:function(){this.enabled=!this.enabled},toggle:function(){this[this.tip().hasClass("in")?"hide":"show"]()}};function b(g,e,f){return typeof g=="function"?g.apply(e,f):g}c.fn.twipsy=function(e){c.fn.twipsy.initWith.call(this,e,d,"twipsy");return this};c.fn.twipsy.initWith=function(n,j,e){var m,l,h,g;if(n===true){return this.data(e)}else{if(typeof n=="string"){m=this.data(e);if(m){m[n]()}return this}}n=c.extend({},c.fn[e].defaults,n);function f(p){var o=c.data(p,e);if(!o){o=new j(p,c.fn.twipsy.elementOptions(p,n));c.data(p,e,o)}return o}function i(){var o=f(this);o.hoverState="in";if(n.delayIn==0){o.show()}else{o.fixTitle();setTimeout(function(){if(o.hoverState=="in"){o.show()}},n.delayIn)}}function k(){var o=f(this);o.hoverState="out";if(n.delayOut==0){o.hide()}else{setTimeout(function(){if(o.hoverState=="out"){o.hide()}},n.delayOut)}}if(!n.live){this.each(function(){f(this)})}if(n.trigger!="manual"){l=n.live?"live":"bind";h=n.trigger=="hover"?"mouseenter":"focus";g=n.trigger=="hover"?"mouseleave":"blur";this[l](h,i)[l](g,k)}return this};c.fn.twipsy.Twipsy=d;c.fn.twipsy.defaults={animate:true,delayIn:0,delayOut:0,fallback:"",placement:"above",html:false,live:false,offset:0,title:"title",trigger:"hover",template:'<div class="twipsy-arrow"></div><div class="twipsy-inner"></div>'};c.fn.twipsy.rejectAttrOptions=["title"];c.fn.twipsy.elementOptions=function(j,f){var h=c(j).data(),e=c.fn.twipsy.rejectAttrOptions,g=e.length;while(g--){delete h[e[g]]}return c.extend({},f,h)}}(window.jQuery||window.ender);!function(b){var a=function(d,c){this.$element=b(d);this.options=c;this.enabled=true;this.fixTitle()};a.prototype=b.extend({},b.fn.twipsy.Twipsy.prototype,{setContent:function(){var c=this.tip();c.find(this.options.titleSelector)[this.options.html?"html":"text"](this.getTitle());c.find(this.options.contentSelector)[this.options.html?"html":"text"](this.getContent());c[0].className="popover"},hasContent:function(){return this.getTitle()||this.getContent()},getContent:function(){var d,c=this.$element,e=this.options;if(typeof this.options.content=="string"){d=c.attr(this.options.content)}else{if(typeof this.options.content=="function"){d=this.options.content.call(this.$element[0])}}return d},tip:function(){if(!this.$tip){this.$tip=b('<div class="popover" />').html(this.options.template)}return this.$tip}});b.fn.popover=function(c){if(typeof c=="object"){c=b.extend({},b.fn.popover.defaults,c)}b.fn.twipsy.initWith.call(this,c,a,"popover");return this};b.fn.popover.defaults=b.extend({},b.fn.twipsy.defaults,{placement:"right",content:"data-content",template:'<div class="arrow"></div><div class="inner"><h3 class="title"></h3><div class="content"><p></p></div></div>',titleSelector:".title",contentSelector:".content p"});b.fn.twipsy.rejectAttrOptions.push("content")}(window.jQuery||window.ender);

(function(e){var a="0.7.2",h=e.extend,d=/^(\d{4})-(\d\d)-(\d\d)T(\d\d):(\d\d)(?::(\d\d)(?:[.](\d+))?)?(?:([-+]\d\d):(\d\d)|Z)$/,b=function(j,i,k){if(k){j.setHours(c.H(j)-i,c.M(j)+(i>0?-k:+k))}return j},f=function(i,j){return(i+1000+"").substr(4-(j||2))},c={yy:function(i){return f(c.yyyy(i)%100)},yyyy:function(i){return i.getFullYear()},m:function(i){return i.getMonth()+1},mm:function(i){return f(c.m(i))},mmm:function(i){return g.abbrMonths[c.m(i)-1]},mmmm:function(i){return g.fullMonths[c.m(i)-1]},d:function(i){return i.getDate()},dd:function(i){return f(c.d(i))},ddd:function(i){return g.abbrDays[i.getDay()]},dddd:function(i){return g.fullDays[i.getDay()]},o:function(i){return g.ordinals(c.d(i))},h:function(i){return c.H(i)%12||12},hh:function(i){return f(c.h(i))},H:function(i){return i.getHours()},HH:function(i){return f(c.H(i))},M:function(i){return i.getMinutes()},MM:function(i){return f(c.M(i))},s:function(i){return i.getSeconds()},ss:function(i){return f(c.s(i))},S:function(i){return c.s(i)+"."+f(i%1000,3)},SS:function(i){return c.ss(i)+"."+f(i%1000,3)},a:function(i){return g.periods[+(c.H(i)>11)]},Z:function(i){var k=-i.getTimezoneOffset(),j=Math.abs(k);return(k<0?"-":"+")+f(j/60>>0)+":"+f(j%60)}},g=function(j,p){if(!(j instanceof Date)){p=j;j=new Date}p=p||g.format;if(typeof p==="function"){return p(j)}var o=p.replace("~","~T").replace("%%","~P")+"%",m,k="",q=0,n=o.length,i="",l;while(q<n){m=o.charAt(q++);if(k){if(m===l||k==="%"){k+=m}else{k=k.substr(1);i+=c.hasOwnProperty(k)?g.escaped?e("<b>").text(c[k](j)).html():c[k](j):k}}if(!/%/.test(k)){if(/%/.test(p)){if(m==="%"){k="%"}else{k="";i+=m}}else{k="%"+m}}l=m}return i.replace("~P","%").replace("~T","~")};h(g,{abbrDays:"Sun,Mon,Tue,Wed,Thu,Fri,Sat".split(","),abbrMonths:"Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec".split(","),format:"d mmmm yyyy",fullDays:"Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday".split(","),fullMonths:"January,February,March,April,May,June,July,August,September,October,November,December".split(","),ordinals:function(i){return i+["th","st","nd","rd"][i>10&&i<14||(i%=10)>3?0:i]},periods:["AM","PM"]});e.localize=g;e.localize.version=a;e.fn.localize=function(k){var i=h({},g),j,l;if(typeof k==="object"){h(i,k);k=i.format}k=k||g.format;j=typeof k==="function";l=i.escaped?"html":"text";return this.each(function(){var p=e(this),o,n;if(/^time$/i.test(this.nodeName)){n=d.exec(p.attr("datetime")||g(new Date,"yyyy-mm-ddTHH:MM:ssZ"))}if(!n){return}o=b(new Date(Date.UTC(+n[1],n[2]-1,+n[3],+n[4],+n[5],+n[6]||0,+((n[7]||0)+"00").substr(0,3))),+n[8],n[9]);p.attr("datetime",g(o,"yyyy-mm-ddTHH:MM"+(n[7]?":SS":n[6]?":ss":"")+"Z"))[l](j?k.call(p,o):g(o,k))})}}(jQuery));


/*
 * Lazy Load - jQuery plugin for lazy loading images
 *
 * Copyright (c) 2007-2011 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.appelsiini.net/projects/lazyload
 *
 * Version:  1.7.0-dev
 *
 */
(function($) {

    $.fn.lazyload = function(options) {
        var settings = {
            threshold       : 0,
            failure_limit   : 0,
            event           : "scroll",
            effect          : "show",
            container       : window,
            skip_invisible  : true
        };
                
        if(options) {
            /* Maintain BC for a couple of version. */
            if (null !== options.failurelimit) {
                options.failure_limit = options.failurelimit; 
                delete options.failurelimit;
            }
            
            $.extend(settings, options);
        }

        /* Fire one scroll event per scroll. Not one scroll event per image. */
        var elements = this;
		var lastscroll = 0;
		var triggered_scroll = 0;
		var stopped_scroll = 0;
        if (0 == settings.event.indexOf("scroll")) {
            $(settings.container).bind(settings.event, function(event) {
            triggered_scroll++;
            	var current_date = +new Date();
            	if(current_date - lastscroll < 500)
				{
					return false;
				}
				lastscroll = current_date;
                var counter = 0;
                elements.each(function() {
                    if (settings.skip_invisible && !$(this).is(":visible")) return;
                    if ($.abovethetop(this, settings) ||
                        $.leftofbegin(this, settings)) {
                            /* Nothing. */
                    } else if (!$.belowthefold(this, settings) &&
                        !$.rightoffold(this, settings)) {
                            $(this).trigger("appear");
                    } else {
                        if (++counter > settings.failure_limit) {
                            return false;
                        }
                    }
                });

                /* Remove image from array so it is not looped next time. */
                var temp = $.grep(elements, function(element) {
                    return !element.loaded;
                });
                elements = $(temp);

            });
        }
        
        this.each(function() {
            var self = this;            
            self.loaded = false;
            
            /* When appear is triggered load original image. */
            $(self).one("appear", function() {
                if (!this.loaded) {
                    $("<img />")
                        .bind("load", function() {
                            $(self)
                                .hide()
                                .attr("src", $(self).data("original"))
                                [settings.effect](settings.effectspeed);
                            self.loaded = true;
                        })
                        .attr("src", $(self).data("original"));
                };
            });

            /* When wanted event is triggered load original image */
            /* by triggering appear.                              */
            if (0 != settings.event.indexOf("scroll")) {
                $(self).bind(settings.event, function(event) {
                    if (!self.loaded) {
                        $(self).trigger("appear");
                    }
                });
            }
        });
        
        /* Check if something appears when window is resized. */
        $(window).bind("resize", function(event) {
            $(settings.container).trigger(settings.event);
        });
        
        /* Force initial check if images should appear. */
        $(settings.container).trigger(settings.event);
        
        return this;

    };

    /* Convenience methods in jQuery namespace.           */
    /* Use as  $.belowthefold(element, {threshold : 100, container : window}) */

    $.belowthefold = function(element, settings) {
        if (settings.container === undefined || settings.container === window) {
            var fold = $(window).height() + $(window).scrollTop();
        } else {
            var fold = $(settings.container).offset().top + $(settings.container).height();
        }
        return fold <= $(element).offset().top - settings.threshold;
    };
    
    $.rightoffold = function(element, settings) {
        if (settings.container === undefined || settings.container === window) {
            var fold = $(window).width() + $(window).scrollLeft();
        } else {
            var fold = $(settings.container).offset().left + $(settings.container).width();
        }
        return fold <= $(element).offset().left - settings.threshold;
    };
        
    $.abovethetop = function(element, settings) {
        if (settings.container === undefined || settings.container === window) {
            var fold = $(window).scrollTop();
        } else {
            var fold = $(settings.container).offset().top;
        }
        return fold >= $(element).offset().top + settings.threshold  + $(element).height();
    };
    
    $.leftofbegin = function(element, settings) {
        if (settings.container === undefined || settings.container === window) {
            var fold = $(window).scrollLeft();
        } else {
            var fold = $(settings.container).offset().left;
        }
        return fold >= $(element).offset().left + settings.threshold + $(element).width();
    };
    /* Custom selectors for your convenience.   */
    /* Use as $("img:below-the-fold").something() */

    $.extend($.expr[':'], {
        "below-the-fold" : function(a) { return $.belowthefold(a, {threshold : 0, container: window}) },
        "above-the-fold" : function(a) { return !$.belowthefold(a, {threshold : 0, container: window}) },
        "right-of-fold"  : function(a) { return $.rightoffold(a, {threshold : 0, container: window}) },
        "left-of-fold"   : function(a) { return !$.rightoffold(a, {threshold : 0, container: window}) }
    });
    
})(jQuery);