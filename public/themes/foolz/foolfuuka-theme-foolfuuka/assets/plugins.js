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


var setCookie = function (name, value, expires, path, domain, secure)
{
	name = backend_vars.cookie_prefix + name;
	var today = new Date();
	today.setTime( today.getTime() );
	if ( expires )
	{
		expires = expires * 1000 * 60 * 60 * 24;
	}
	var expires_date = new Date( today.getTime() + (expires) );

	document.cookie = name + "=" +escape( value ) +
		( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) +
		( ( path ) ? ";path=" + path : "" ) +
		( ( domain ) ? ";domain=" + domain : "" ) +
		( ( secure ) ? ";secure" : "" );
}


var getCookie = function(check_name) {
	check_name = backend_vars.cookie_prefix + check_name;
	var a_all_cookies = document.cookie.split( ';' );
	var a_temp_cookie = '';
	var cookie_name = '';
	var cookie_value = '';
	var b_cookie_found = false;
	for ( i = 0; i < a_all_cookies.length; i++ )
	{
		a_temp_cookie = a_all_cookies[i].split( '=' );
		cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');
		if ( cookie_name == check_name )
		{
			b_cookie_found = true;
			if ( a_temp_cookie.length > 1 )
			{
				cookie_value = unescape( a_temp_cookie[1].replace(/^\s+|\s+$/g, '') );
			}
			return cookie_value;
			break;
		}
		a_temp_cookie = null;
		cookie_name = '';
	}
	if ( !b_cookie_found )
	{
		return null;
	}
}

var fuel_set_csrf_token = function(form)
{
	if (document.cookie.length > 0 && typeof form != undefined)
	{
		var c_name = backend_vars.csrf_token_key;
		c_start = document.cookie.indexOf(c_name + "=");
		if (c_start != -1)
		{
			c_start = c_start + c_name.length + 1;
			c_end = document.cookie.indexOf(";" , c_start);
			if (c_end == -1)
			{
				c_end=document.cookie.length;
			}
			value=unescape(document.cookie.substring(c_start, c_end));
			if (value != "")
			{
				for(i=0; i<form.elements.length; i++)
				{
					if (form.elements[i].name == c_name)
					{
						form.elements[i].value = value;
						break;
					}
				}
			}
		}
	}
}

var eliminateDuplicates = function(arr) {
	var i,
		len=arr.length,
		out=[],
		obj={};

	for (i=0;i<len;i++) {
		obj[arr[i]]=0;
	}
	for (i in obj) {
		out.push(i);
	}
	return out;
}

var isEventSupported = (function() {

	var TAGNAMES = {
		'select': 'input',
		'change': 'input',
		'submit': 'form',
		'reset': 'form',
		'error': 'img',
		'load': 'img',
		'abort': 'img'
	};

	function isEventSupported( eventName, element ) {

		element = element || document.createElement(TAGNAMES[eventName] || 'div');
		eventName = 'on' + eventName;

		// When using `setAttribute`, IE skips "unload", WebKit skips "unload" and "resize", whereas `in` "catches" those
		var isSupported = eventName in element;

		if ( !isSupported ) {
			// If it has no `setAttribute` (i.e. doesn't implement Node interface), try generic element
			if ( !element.setAttribute ) {
				element = document.createElement('div');
			}
			if ( element.setAttribute && element.removeAttribute ) {
				element.setAttribute(eventName, '');
				isSupported = typeof element[eventName] == 'function';

				// If property was created, "remove it" (by setting value to `undefined`)
				if ( typeof element[eventName] != 'undefined' ) {
					element[eventName] = undefined;
				}
				element.removeAttribute(eventName);
			}
		}

		element = null;
		return isSupported;
	}
	return isEventSupported;
})();

// make it safe to use console.log always
(function(b){function c(){}for(var d="assert,clear,count,debug,dir,dirxml,error,exception,firebug,group,groupCollapsed,groupEnd,info,log,memoryProfile,memoryProfileEnd,profile,profileEnd,table,time,timeEnd,timeStamp,trace,warn".split(","),a;a=d.pop();){b[a]=b[a]||c}})((function(){try
{console.log();return window.console;}catch(err){return window.console={};}})());


/*
 * jQuery Pines Notify (pnotify) Plugin 1.2.0
 *
 * http://pinesframework.org/pnotify/
 * Copyright (c) 2009-2012 Hunter Perrin
 *
 * Triple license under the GPL, LGPL, and MPL:
 *	  http://www.gnu.org/licenses/gpl.html
 *	  http://www.gnu.org/licenses/lgpl.html
 *	  http://www.mozilla.org/MPL/MPL-1.1.html
 */
(function(d){var q,j,r,i=d(window),u={jqueryui:{container:"ui-widget ui-widget-content ui-corner-all",notice:"ui-state-highlight",notice_icon:"ui-icon ui-icon-info",info:"",info_icon:"ui-icon ui-icon-info",success:"ui-state-default",success_icon:"ui-icon ui-icon-circle-check",error:"ui-state-error",error_icon:"ui-icon ui-icon-alert",closer:"ui-icon ui-icon-close",pin_up:"ui-icon ui-icon-pin-w",pin_down:"ui-icon ui-icon-pin-s",hi_menu:"ui-state-default ui-corner-bottom",hi_btn:"ui-state-default ui-corner-all",
hi_btnhov:"ui-state-hover",hi_hnd:"ui-icon ui-icon-grip-dotted-horizontal"},bootstrap:{container:"alert",notice:"",notice_icon:"icon-exclamation-sign",info:"alert-info",info_icon:"icon-info-sign",success:"alert-success",success_icon:"icon-ok-sign",error:"alert-error",error_icon:"icon-warning-sign",closer:"icon-remove",pin_up:"icon-pause",pin_down:"icon-play",hi_menu:"well",hi_btn:"btn",hi_btnhov:"",hi_hnd:"icon-chevron-down"}},s=function(){r=d("body");i=d(window);i.bind("resize",function(){j&&clearTimeout(j);
j=setTimeout(d.pnotify_position_all,10)})};document.body?s():d(s);d.extend({pnotify_remove_all:function(){var e=i.data("pnotify");e&&e.length&&d.each(e,function(){this.pnotify_remove&&this.pnotify_remove()})},pnotify_position_all:function(){j&&clearTimeout(j);j=null;var e=i.data("pnotify");e&&e.length&&(d.each(e,function(){var d=this.opts.stack;if(d)d.nextpos1=d.firstpos1,d.nextpos2=d.firstpos2,d.addpos2=0,d.animation=true}),d.each(e,function(){this.pnotify_position()}))},pnotify:function(e){var g,
a;typeof e!="object"?(a=d.extend({},d.pnotify.defaults),a.text=e):a=d.extend({},d.pnotify.defaults,e);for(var p in a)typeof p=="string"&&p.match(/^pnotify_/)&&(a[p.replace(/^pnotify_/,"")]=a[p]);if(a.before_init&&a.before_init(a)===false)return null;var k,o=function(a,c){b.css("display","none");var f=document.elementFromPoint(a.clientX,a.clientY);b.css("display","block");var e=d(f),g=e.css("cursor");b.css("cursor",g!="auto"?g:"default");if(!k||k.get(0)!=f)k&&(n.call(k.get(0),"mouseleave",a.originalEvent),
n.call(k.get(0),"mouseout",a.originalEvent)),n.call(f,"mouseenter",a.originalEvent),n.call(f,"mouseover",a.originalEvent);n.call(f,c,a.originalEvent);k=e},f=u[a.styling],b=d("<div />",{"class":"ui-pnotify "+a.addclass,css:{display:"none"},mouseenter:function(l){a.nonblock&&l.stopPropagation();a.mouse_reset&&g=="out"&&(b.stop(true),g="in",b.css("height","auto").animate({width:a.width,opacity:a.nonblock?a.nonblock_opacity:a.opacity},"fast"));a.nonblock&&b.animate({opacity:a.nonblock_opacity},"fast");
a.hide&&a.mouse_reset&&b.pnotify_cancel_remove();a.sticker&&!a.nonblock&&b.sticker.trigger("pnotify_icon").css("visibility","visible");a.closer&&!a.nonblock&&b.closer.css("visibility","visible")},mouseleave:function(l){a.nonblock&&l.stopPropagation();k=null;b.css("cursor","auto");a.nonblock&&g!="out"&&b.animate({opacity:a.opacity},"fast");a.hide&&a.mouse_reset&&b.pnotify_queue_remove();a.sticker_hover&&b.sticker.css("visibility","hidden");a.closer_hover&&b.closer.css("visibility","hidden");d.pnotify_position_all()},
mouseover:function(b){a.nonblock&&b.stopPropagation()},mouseout:function(b){a.nonblock&&b.stopPropagation()},mousemove:function(b){a.nonblock&&(b.stopPropagation(),o(b,"onmousemove"))},mousedown:function(b){a.nonblock&&(b.stopPropagation(),b.preventDefault(),o(b,"onmousedown"))},mouseup:function(b){a.nonblock&&(b.stopPropagation(),b.preventDefault(),o(b,"onmouseup"))},click:function(b){a.nonblock&&(b.stopPropagation(),o(b,"onclick"))},dblclick:function(b){a.nonblock&&(b.stopPropagation(),o(b,"ondblclick"))}});
b.opts=a;b.container=d("<div />",{"class":f.container+" ui-pnotify-container "+(a.type=="error"?f.error:a.type=="info"?f.info:a.type=="success"?f.success:f.notice)}).appendTo(b);a.cornerclass!=""&&b.container.removeClass("ui-corner-all").addClass(a.cornerclass);a.shadow&&b.container.addClass("ui-pnotify-shadow");b.pnotify_version="1.2.0";b.pnotify=function(l){var c=a;typeof l=="string"?a.text=l:a=d.extend({},a,l);for(var e in a)typeof e=="string"&&e.match(/^pnotify_/)&&(a[e.replace(/^pnotify_/,"")]=
a[e]);b.opts=a;a.cornerclass!=c.cornerclass&&b.container.removeClass("ui-corner-all").addClass(a.cornerclass);a.shadow!=c.shadow&&(a.shadow?b.container.addClass("ui-pnotify-shadow"):b.container.removeClass("ui-pnotify-shadow"));a.addclass===false?b.removeClass(c.addclass):a.addclass!==c.addclass&&b.removeClass(c.addclass).addClass(a.addclass);a.title===false?b.title_container.slideUp("fast"):a.title!==c.title&&(a.title_escape?b.title_container.text(a.title).slideDown(200):b.title_container.html(a.title).slideDown(200));
a.text===false?b.text_container.slideUp("fast"):a.text!==c.text&&(a.text_escape?b.text_container.text(a.text).slideDown(200):b.text_container.html(a.insert_brs?String(a.text).replace(/\n/g,"<br />"):a.text).slideDown(200));b.pnotify_history=a.history;b.pnotify_hide=a.hide;a.type!=c.type&&b.container.removeClass(f.error+" "+f.notice+" "+f.success+" "+f.info).addClass(a.type=="error"?f.error:a.type=="info"?f.info:a.type=="success"?f.success:f.notice);if(a.icon!==c.icon||a.icon===true&&a.type!=c.type)b.container.find("div.ui-pnotify-icon").remove(),
a.icon!==false&&d("<div />",{"class":"ui-pnotify-icon"}).append(d("<span />",{"class":a.icon===true?a.type=="error"?f.error_icon:a.type=="info"?f.info_icon:a.type=="success"?f.success_icon:f.notice_icon:a.icon})).prependTo(b.container);a.width!==c.width&&b.animate({width:a.width});a.min_height!==c.min_height&&b.container.animate({minHeight:a.min_height});a.opacity!==c.opacity&&b.fadeTo(a.animate_speed,a.opacity);!a.closer||a.nonblock?b.closer.css("display","none"):b.closer.css("display","block");
!a.sticker||a.nonblock?b.sticker.css("display","none"):b.sticker.css("display","block");b.sticker.trigger("pnotify_icon");a.sticker_hover?b.sticker.css("visibility","hidden"):a.nonblock||b.sticker.css("visibility","visible");a.closer_hover?b.closer.css("visibility","hidden"):a.nonblock||b.closer.css("visibility","visible");a.hide?c.hide||b.pnotify_queue_remove():b.pnotify_cancel_remove();b.pnotify_queue_position();return b};b.pnotify_position=function(a){var c=b.opts.stack;if(c){if(!c.nextpos1)c.nextpos1=
c.firstpos1;if(!c.nextpos2)c.nextpos2=c.firstpos2;if(!c.addpos2)c.addpos2=0;var d=b.css("display")=="none";if(!d||a){var f,e={},g;switch(c.dir1){case "down":g="top";break;case "up":g="bottom";break;case "left":g="right";break;case "right":g="left"}a=parseInt(b.css(g));isNaN(a)&&(a=0);if(typeof c.firstpos1=="undefined"&&!d)c.firstpos1=a,c.nextpos1=c.firstpos1;var h;switch(c.dir2){case "down":h="top";break;case "up":h="bottom";break;case "left":h="right";break;case "right":h="left"}f=parseInt(b.css(h));
isNaN(f)&&(f=0);if(typeof c.firstpos2=="undefined"&&!d)c.firstpos2=f,c.nextpos2=c.firstpos2;if(c.dir1=="down"&&c.nextpos1+b.height()>i.height()||c.dir1=="up"&&c.nextpos1+b.height()>i.height()||c.dir1=="left"&&c.nextpos1+b.width()>i.width()||c.dir1=="right"&&c.nextpos1+b.width()>i.width())c.nextpos1=c.firstpos1,c.nextpos2+=c.addpos2+(typeof c.spacing2=="undefined"?25:c.spacing2),c.addpos2=0;if(c.animation&&c.nextpos2<f)switch(c.dir2){case "down":e.top=c.nextpos2+"px";break;case "up":e.bottom=c.nextpos2+
"px";break;case "left":e.right=c.nextpos2+"px";break;case "right":e.left=c.nextpos2+"px"}else b.css(h,c.nextpos2+"px");switch(c.dir2){case "down":case "up":if(b.outerHeight(true)>c.addpos2)c.addpos2=b.height();break;case "left":case "right":if(b.outerWidth(true)>c.addpos2)c.addpos2=b.width()}if(c.nextpos1)if(c.animation&&(a>c.nextpos1||e.top||e.bottom||e.right||e.left))switch(c.dir1){case "down":e.top=c.nextpos1+"px";break;case "up":e.bottom=c.nextpos1+"px";break;case "left":e.right=c.nextpos1+"px";
break;case "right":e.left=c.nextpos1+"px"}else b.css(g,c.nextpos1+"px");(e.top||e.bottom||e.right||e.left)&&b.animate(e,{duration:500,queue:false});switch(c.dir1){case "down":case "up":c.nextpos1+=b.height()+(typeof c.spacing1=="undefined"?25:c.spacing1);break;case "left":case "right":c.nextpos1+=b.width()+(typeof c.spacing1=="undefined"?25:c.spacing1)}}}};b.pnotify_queue_position=function(a){j&&clearTimeout(j);a||(a=10);j=setTimeout(d.pnotify_position_all,a)};b.pnotify_display=function(){b.parent().length||
b.appendTo(r);a.before_open&&a.before_open(b)===false||(a.stack.push!="top"&&b.pnotify_position(true),a.animation=="fade"||a.animation.effect_in=="fade"?b.show().fadeTo(0,0).hide():a.opacity!=1&&b.show().fadeTo(0,a.opacity).hide(),b.animate_in(function(){a.after_open&&a.after_open(b);b.pnotify_queue_position();a.hide&&b.pnotify_queue_remove()}))};b.pnotify_remove=function(){if(b.timer)window.clearTimeout(b.timer),b.timer=null;a.before_close&&a.before_close(b)===false||b.animate_out(function(){a.after_close&&
a.after_close(b)===false||(b.pnotify_queue_position(),a.remove&&b.detach())})};b.animate_in=function(d){g="in";var c;c=typeof a.animation.effect_in!="undefined"?a.animation.effect_in:a.animation;c=="none"?(b.show(),d()):c=="show"?b.show(a.animate_speed,d):c=="fade"?b.show().fadeTo(a.animate_speed,a.opacity,d):c=="slide"?b.slideDown(a.animate_speed,d):typeof c=="function"?c("in",d,b):b.show(c,typeof a.animation.options_in=="object"?a.animation.options_in:{},a.animate_speed,d)};b.animate_out=function(d){g=
"out";var c;c=typeof a.animation.effect_out!="undefined"?a.animation.effect_out:a.animation;c=="none"?(b.hide(),d()):c=="show"?b.hide(a.animate_speed,d):c=="fade"?b.fadeOut(a.animate_speed,d):c=="slide"?b.slideUp(a.animate_speed,d):typeof c=="function"?c("out",d,b):b.hide(c,typeof a.animation.options_out=="object"?a.animation.options_out:{},a.animate_speed,d)};b.pnotify_cancel_remove=function(){b.timer&&window.clearTimeout(b.timer)};b.pnotify_queue_remove=function(){b.pnotify_cancel_remove();b.timer=
window.setTimeout(function(){b.pnotify_remove()},isNaN(a.delay)?0:a.delay)};b.closer=d("<div />",{"class":"ui-pnotify-closer",css:{cursor:"pointer",visibility:a.closer_hover?"hidden":"visible"},click:function(){b.pnotify_remove();b.sticker.css("visibility","hidden");b.closer.css("visibility","hidden")}}).append(d("<span />",{"class":f.closer})).appendTo(b.container);(!a.closer||a.nonblock)&&b.closer.css("display","none");b.sticker=d("<div />",{"class":"ui-pnotify-sticker",css:{cursor:"pointer",visibility:a.sticker_hover?
"hidden":"visible"},click:function(){a.hide=!a.hide;a.hide?b.pnotify_queue_remove():b.pnotify_cancel_remove();d(this).trigger("pnotify_icon")}}).bind("pnotify_icon",function(){d(this).children().removeClass(f.pin_up+" "+f.pin_down).addClass(a.hide?f.pin_up:f.pin_down)}).append(d("<span />",{"class":f.pin_up})).appendTo(b.container);(!a.sticker||a.nonblock)&&b.sticker.css("display","none");a.icon!==false&&d("<div />",{"class":"ui-pnotify-icon"}).append(d("<span />",{"class":a.icon===true?a.type=="error"?
f.error_icon:a.type=="info"?f.info_icon:a.type=="success"?f.success_icon:f.notice_icon:a.icon})).prependTo(b.container);b.title_container=d("<h4 />",{"class":"ui-pnotify-title"}).appendTo(b.container);a.title===false?b.title_container.hide():a.title_escape?b.title_container.text(a.title):b.title_container.html(a.title);b.text_container=d("<div />",{"class":"ui-pnotify-text"}).appendTo(b.container);a.text===false?b.text_container.hide():a.text_escape?b.text_container.text(a.text):b.text_container.html(a.insert_brs?
String(a.text).replace(/\n/g,"<br />"):a.text);typeof a.width=="string"&&b.css("width",a.width);typeof a.min_height=="string"&&b.container.css("min-height",a.min_height);b.pnotify_history=a.history;b.pnotify_hide=a.hide;var h=i.data("pnotify");if(h==null||typeof h!="object")h=[];h=a.stack.push=="top"?d.merge([b],h):d.merge(h,[b]);i.data("pnotify",h);a.stack.push=="top"&&b.pnotify_queue_position(1);a.after_init&&a.after_init(b);if(a.history){var m=i.data("pnotify_history");typeof m=="undefined"&&(m=
d("<div />",{"class":"ui-pnotify-history-container "+f.hi_menu,mouseleave:function(){m.animate({top:"-"+q+"px"},{duration:100,queue:false})}}).append(d("<div />",{"class":"ui-pnotify-history-header",text:"Redisplay"})).append(d("<button />",{"class":"ui-pnotify-history-all "+f.hi_btn,text:"All",mouseenter:function(){d(this).addClass(f.hi_btnhov)},mouseleave:function(){d(this).removeClass(f.hi_btnhov)},click:function(){d.each(h,function(){this.pnotify_history&&(this.is(":visible")?this.pnotify_hide&&
this.pnotify_queue_remove():this.pnotify_display&&this.pnotify_display())});return false}})).append(d("<button />",{"class":"ui-pnotify-history-last "+f.hi_btn,text:"Last",mouseenter:function(){d(this).addClass(f.hi_btnhov)},mouseleave:function(){d(this).removeClass(f.hi_btnhov)},click:function(){var a=-1,b;do{b=a==-1?h.slice(a):h.slice(a,a+1);if(!b[0])break;a--}while(!b[0].pnotify_history||b[0].is(":visible"));if(!b[0])return false;b[0].pnotify_display&&b[0].pnotify_display();return false}})).appendTo(r),
q=d("<span />",{"class":"ui-pnotify-history-pulldown "+f.hi_hnd,mouseenter:function(){m.animate({top:"0"},{duration:100,queue:false})}}).appendTo(m).offset().top+2,m.css({top:"-"+q+"px"}),i.data("pnotify_history",m))}a.stack.animation=false;b.pnotify_display();return b}});var t=/^on/,v=/^(dbl)?click$|^mouse(move|down|up|over|out|enter|leave)$|^contextmenu$/,w=/^(focus|blur|select|change|reset)$|^key(press|down|up)$/,x=/^(scroll|resize|(un)?load|abort|error)$/,n=function(e,g){var a,e=e.toLowerCase();
document.createEvent&&this.dispatchEvent?(e=e.replace(t,""),e.match(v)?(d(this).offset(),a=document.createEvent("MouseEvents"),a.initMouseEvent(e,g.bubbles,g.cancelable,g.view,g.detail,g.screenX,g.screenY,g.clientX,g.clientY,g.ctrlKey,g.altKey,g.shiftKey,g.metaKey,g.button,g.relatedTarget)):e.match(w)?(a=document.createEvent("UIEvents"),a.initUIEvent(e,g.bubbles,g.cancelable,g.view,g.detail)):e.match(x)&&(a=document.createEvent("HTMLEvents"),a.initEvent(e,g.bubbles,g.cancelable)),a&&this.dispatchEvent(a)):
(e.match(t)||(e="on"+e),a=document.createEventObject(g),this.fireEvent(e,a))};d.pnotify.defaults={title:false,title_escape:false,text:false,text_escape:false,styling:"bootstrap",addclass:"",cornerclass:"",nonblock:false,nonblock_opacity:0.2,history:true,width:"300px",min_height:"16px",type:"notice",icon:true,animation:"fade",animate_speed:"slow",opacity:1,shadow:true,closer:true,closer_hover:true,sticker:true,sticker_hover:true,hide:true,delay:8E3,mouse_reset:true,remove:true,insert_brs:true,stack:{dir1:"down",
dir2:"left",push:"bottom",spacing1:25,spacing2:25}}})(jQuery);



//fgnass.github.com/spin.js#v1.2.5
(function(a,b,c){function g(a,c){var d=b.createElement(a||"div"),e;for(e in c)d[e]=c[e];return d}function h(a){for(var b=1,c=arguments.length;b<c;b++)a.appendChild(arguments[b]);return a}function j(a,b,c,d){var g=["opacity",b,~~(a*100),c,d].join("-"),h=.01+c/d*100,j=Math.max(1-(1-a)/b*(100-h),a),k=f.substring(0,f.indexOf("Animation")).toLowerCase(),l=k&&"-"+k+"-"||"";return e[g]||(i.insertRule("@"+l+"keyframes "+g+"{"+"0%{opacity:"+j+"}"+h+"%{opacity:"+a+"}"+(h+.01)+"%{opacity:1}"+(h+b)%100+"%{opacity:"+a+"}"+"100%{opacity:"+j+"}"+"}",0),e[g]=1),g}function k(a,b){var e=a.style,f,g;if(e[b]!==c)return b;b=b.charAt(0).toUpperCase()+b.slice(1);for(g=0;g<d.length;g++){f=d[g]+b;if(e[f]!==c)return f}}function l(a,b){for(var c in b)a.style[k(a,c)||c]=b[c];return a}function m(a){for(var b=1;b<arguments.length;b++){var d=arguments[b];for(var e in d)a[e]===c&&(a[e]=d[e])}return a}function n(a){var b={x:a.offsetLeft,y:a.offsetTop};while(a=a.offsetParent)b.x+=a.offsetLeft,b.y+=a.offsetTop;return b}var d=["webkit","Moz","ms","O"],e={},f,i=function(){var a=g("style");return h(b.getElementsByTagName("head")[0],a),a.sheet||a.styleSheet}(),o={lines:12,length:7,width:5,radius:10,rotate:0,color:"#000",speed:1,trail:100,opacity:.25,fps:20,zIndex:2e9,className:"spinner",top:"auto",left:"auto"},p=function q(a){if(!this.spin)return new q(a);this.opts=m(a||{},q.defaults,o)};p.defaults={},m(p.prototype,{spin:function(a){this.stop();var b=this,c=b.opts,d=b.el=l(g(0,{className:c.className}),{position:"relative",zIndex:c.zIndex}),e=c.radius+c.length+c.width,h,i;a&&(a.insertBefore(d,a.firstChild||null),i=n(a),h=n(d),l(d,{left:(c.left=="auto"?i.x-h.x+(a.offsetWidth>>1):c.left+e)+"px",top:(c.top=="auto"?i.y-h.y+(a.offsetHeight>>1):c.top+e)+"px"})),d.setAttribute("aria-role","progressbar"),b.lines(d,b.opts);if(!f){var j=0,k=c.fps,m=k/c.speed,o=(1-c.opacity)/(m*c.trail/100),p=m/c.lines;!function q(){j++;for(var a=c.lines;a;a--){var e=Math.max(1-(j+a*p)%m*o,c.opacity);b.opacity(d,c.lines-a,e,c)}b.timeout=b.el&&setTimeout(q,~~(1e3/k))}()}return b},stop:function(){var a=this.el;return a&&(clearTimeout(this.timeout),a.parentNode&&a.parentNode.removeChild(a),this.el=c),this},lines:function(a,b){function e(a,d){return l(g(),{position:"absolute",width:b.length+b.width+"px",height:b.width+"px",background:a,boxShadow:d,transformOrigin:"left",transform:"rotate("+~~(360/b.lines*c+b.rotate)+"deg) translate("+b.radius+"px"+",0)",borderRadius:(b.width>>1)+"px"})}var c=0,d;for(;c<b.lines;c++)d=l(g(),{position:"absolute",top:1+~(b.width/2)+"px",transform:b.hwaccel?"translate3d(0,0,0)":"",opacity:b.opacity,animation:f&&j(b.opacity,b.trail,c,b.lines)+" "+1/b.speed+"s linear infinite"}),b.shadow&&h(d,l(e("#000","0 0 4px #000"),{top:"2px"})),h(a,h(d,e(b.color,"0 0 1px rgba(0,0,0,.1)")));return a},opacity:function(a,b,c){b<a.childNodes.length&&(a.childNodes[b].style.opacity=c)}}),!function(){function a(a,b){return g("<"+a+' xmlns="urn:schemas-microsoft.com:vml" class="spin-vml">',b)}var b=l(g("group"),{behavior:"url(#default#VML)"});!k(b,"transform")&&b.adj?(i.addRule(".spin-vml","behavior:url(#default#VML)"),p.prototype.lines=function(b,c){function f(){return l(a("group",{coordsize:e+" "+e,coordorigin:-d+" "+ -d}),{width:e,height:e})}function k(b,e,g){h(i,h(l(f(),{rotation:360/c.lines*b+"deg",left:~~e}),h(l(a("roundrect",{arcsize:1}),{width:d,height:c.width,left:c.radius,top:-c.width>>1,filter:g}),a("fill",{color:c.color,opacity:c.opacity}),a("stroke",{opacity:0}))))}var d=c.length+c.width,e=2*d,g=-(c.width+c.length)*2+"px",i=l(f(),{position:"absolute",top:g,left:g}),j;if(c.shadow)for(j=1;j<=c.lines;j++)k(j,-2,"progid:DXImageTransform.Microsoft.Blur(pixelradius=2,makeshadow=1,shadowopacity=.3)");for(j=1;j<=c.lines;j++)k(j);return h(b,i)},p.prototype.opacity=function(a,b,c,d){var e=a.firstChild;d=d.shadow&&d.lines||0,e&&b+d<e.childNodes.length&&(e=e.childNodes[b+d],e=e&&e.firstChild,e=e&&e.firstChild,e&&(e.opacity=c))}):f=k(b,"animation")}(),a.Spinner=p})(window,document);


/*

You can now create a spinner using any of the variants below:

$("#el").spin(); // Produces default Spinner using the text color of #el.
$("#el").spin("small"); // Produces a 'small' Spinner using the text color of #el.
$("#el").spin("large", "white"); // Produces a 'large' Spinner in white (or any valid CSS color).
$("#el").spin({ ... }); // Produces a Spinner using your custom settings.

$("#el").spin(false); // Kills the spinner.

*/
(function($) {
	$.fn.spin = function(opts, color) {
		var presets = {
			"tiny": { lines: 8, length: 2, width: 2, radius: 3 },
			"small": { lines: 8, length: 2, width: 2, radius: 2, left: -13, top: 2 },
			"large": { lines: 10, length: 8, width: 4, radius: 8 }
		};
		if (Spinner) {
			return this.each(function() {
				var $this = $(this),
					data = $this.data();
				
				if (data.spinner) {
					data.spinner.stop();
					delete data.spinner;
				}
				if (opts !== false) {
					if (typeof opts === "string") {
						if (opts in presets) {
							opts = presets[opts];
						} else {
							opts = {};
						}
						if (color) {
							opts.color = color;
						}
					}
					data.spinner = new Spinner($.extend({color: $this.css('color')}, opts)).spin(this);
				}
			});
		} else {
			throw "Spinner class not available.";
		}
	};
})(jQuery);

/**
 * jQuery.ScrollTo - Easy element scrolling using jQuery.
 * Copyright (c) 2007-2009 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 5/25/2009
 * @author Ariel Flesler
 * @version 1.4.2
 *
 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html
 */
;(function(d){var k=d.scrollTo=function(a,i,e){d(window).scrollTo(a,i,e)};k.defaults={axis:'xy',duration:parseFloat(d.fn.jquery)>=1.3?0:1};k.window=function(a){return d(window)._scrollable()};d.fn._scrollable=function(){return this.map(function(){var a=this,i=!a.nodeName||d.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!i)return a;var e=(a.contentWindow||a).document||a.ownerDocument||a;return d.browser.safari||e.compatMode=='BackCompat'?e.body:e.documentElement})};d.fn.scrollTo=function(n,j,b){if(typeof j=='object'){b=j;j=0}if(typeof b=='function')b={onAfter:b};if(n=='max')n=9e9;b=d.extend({},k.defaults,b);j=j||b.speed||b.duration;b.queue=b.queue&&b.axis.length>1;if(b.queue)j/=2;b.offset=p(b.offset);b.over=p(b.over);return this._scrollable().each(function(){var q=this,r=d(q),f=n,s,g={},u=r.is('html,body');switch(typeof f){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(f)){f=p(f);break}f=d(f,this);case'object':if(f.is||f.style)s=(f=d(f)).offset()}d.each(b.axis.split(''),function(a,i){var e=i=='x'?'Left':'Top',h=e.toLowerCase(),c='scroll'+e,l=q[c],m=k.max(q,i);if(s){g[c]=s[h]+(u?0:l-r.offset()[h]);if(b.margin){g[c]-=parseInt(f.css('margin'+e))||0;g[c]-=parseInt(f.css('border'+e+'Width'))||0}g[c]+=b.offset[h]||0;if(b.over[h])g[c]+=f[i=='x'?'width':'height']()*b.over[h]}else{var o=f[h];g[c]=o.slice&&o.slice(-1)=='%'?parseFloat(o)/100*m:o}if(/^\d+$/.test(g[c]))g[c]=g[c]<=0?0:Math.min(g[c],m);if(!a&&b.queue){if(l!=g[c])t(b.onAfterFirst);delete g[c]}});t(b.onAfter);function t(a){r.animate(g,j,b.easing,a&&function(){a.call(this,n,b)})}}).end()};k.max=function(a,i){var e=i=='x'?'Width':'Height',h='scroll'+e;if(!d(a).is('html,body'))return a[h]-d(a)[e.toLowerCase()]();var c='client'+e,l=a.ownerDocument.documentElement,m=a.ownerDocument.body;return Math.max(l[h],m[h])-Math.min(l[c],m[c])};function p(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);

!function(c){var a;c(document).ready(function(){c.support.transition=(function(){var e=document.body||document.documentElement,f=e.style,d=f.transition!==undefined||f.WebkitTransition!==undefined||f.MozTransition!==undefined||f.MsTransition!==undefined||f.OTransition!==undefined;return d})();if(c.support.transition){a="TransitionEnd";if(c.browser.webkit){a="webkitTransitionEnd"}else{if(c.browser.mozilla){a="transitionend"}else{if(c.browser.opera){a="oTransitionEnd"}}}}});var b=function(e,d){this.settings=c.extend({},c.fn.alert.defaults,d);this.$element=c(e).delegate(this.settings.selector,"click",this.close)};b.prototype={close:function(g){var f=c(this).parent(".alert-message");g&&g.preventDefault();f.removeClass("in");function d(){f.remove()}c.support.transition&&f.hasClass("fade")?f.bind(a,d):d()}};c.fn.alert=function(d){if(d===true){return this.data("alert")}return this.each(function(){var e=c(this);if(typeof d=="string"){return e.data("alert")[d]()}c(this).data("alert",new b(this,d))})};c.fn.alert.defaults={selector:".close"};c(document).ready(function(){new b(c("body"),{selector:".alert-message[data-alert] .close"})})}(window.jQuery||window.ender);!function(b){function c(f,h){var i="disabled",e=b(f),g=e.data();h=h+"Text";g.resetText||e.data("resetText",e.html());e.html(g[h]||b.fn.button.defaults[h]);h=="loadingText"?e.addClass(i).attr(i,i):e.removeClass(i).removeAttr(i)}function a(d){b(d).toggleClass("active")}b.fn.button=function(d){return this.each(function(){if(d=="toggle"){return a(this)}d&&c(this,d)})};b.fn.button.defaults={loadingText:"loading..."};b(function(){b("body").delegate(".btn[data-toggle]","click",function(){b(this).button("toggle")})})}(window.jQuery||window.ender);!function(f){var b;f(document).ready(function(){f.support.transition=(function(){var j=document.body||document.documentElement,k=j.style,i=k.transition!==undefined||k.WebkitTransition!==undefined||k.MozTransition!==undefined||k.MsTransition!==undefined||k.OTransition!==undefined;return i})();if(f.support.transition){b="TransitionEnd";if(f.browser.webkit){b="webkitTransitionEnd"}else{if(f.browser.mozilla){b="transitionend"}else{if(f.browser.opera){b="oTransitionEnd"}}}}});var a=function(j,i){this.settings=f.extend({},f.fn.modal.defaults,i);this.$element=f(j).delegate(".close","click.modal",f.proxy(this.hide,this));if(this.settings.show){this.show()}return this};a.prototype={toggle:function(){return this[!this.isShown?"show":"hide"]()},show:function(){var i=this;this.isShown=true;this.$element.trigger("show");e.call(this);d.call(this,function(){var j=f.support.transition&&i.$element.hasClass("fade");i.$element.appendTo(document.body).show();if(j){i.$element[0].offsetWidth}i.$element.addClass("in");j?i.$element.one(b,function(){i.$element.trigger("shown")}):i.$element.trigger("shown")});return this},hide:function(j){j&&j.preventDefault();if(!this.isShown){return this}var i=this;this.isShown=false;e.call(this);this.$element.trigger("hide").removeClass("in");f.support.transition&&this.$element.hasClass("fade")?h.call(this):g.call(this);return this}};function h(){var i=this,j=setTimeout(function(){i.$element.unbind(b);g.call(i)},500);this.$element.one(b,function(){clearTimeout(j);g.call(i)})}function g(i){this.$element.hide().trigger("hidden");d.call(this)}function d(l){var k=this,j=this.$element.hasClass("fade")?"fade":"";if(this.isShown&&this.settings.backdrop){var i=f.support.transition&&j;this.$backdrop=f('<div class="modal-backdrop '+j+'" />').appendTo(document.body);if(this.settings.backdrop!="static"){this.$backdrop.click(f.proxy(this.hide,this))}if(i){this.$backdrop[0].offsetWidth}this.$backdrop.addClass("in");i?this.$backdrop.one(b,l):l()}else{if(!this.isShown&&this.$backdrop){this.$backdrop.removeClass("in");f.support.transition&&this.$element.hasClass("fade")?this.$backdrop.one(b,f.proxy(c,this)):c.call(this)}else{if(l){l()}}}}function c(){this.$backdrop.remove();this.$backdrop=null}function e(){var i=this;if(this.isShown&&this.settings.keyboard){f(document).bind("keyup.modal",function(j){if(j.which==27){i.hide()}})}else{if(!this.isShown){f(document).unbind("keyup.modal")}}}f.fn.modal=function(i){var j=this.data("modal");if(!j){if(typeof i=="string"){i={show:/show|toggle/.test(i)}}return this.each(function(){f(this).data("modal",new a(this,i))})}if(i===true){return j}if(typeof i=="string"){j[i]()}else{if(j){j.toggle()}}return this};f.fn.modal.Modal=a;f.fn.modal.defaults={backdrop:false,keyboard:false,show:false};f(document).ready(function(){f("body").delegate("[data-controls-modal]","click",function(j){j.preventDefault();var i=f(this).data("show",true);f("#"+i.attr("data-controls-modal")).modal(i.data())})})}(window.jQuery||window.ender);!function(b){var c=b(window);function a(e,d){var f=b.proxy(this.processScroll,this);this.$topbar=b(e);this.selector=d||"li > a";this.refresh();this.$topbar.delegate(this.selector,"click",f);c.scroll(f);this.processScroll()}a.prototype={refresh:function(){this.targets=this.$topbar.find(this.selector).map(function(){var d=b(this).attr("href");return/^#\w/.test(d)&&b(d).length?d:null});this.offsets=b.map(this.targets,function(d){return b(d).offset().top})},processScroll:function(){var g=c.scrollTop()+10,f=this.offsets,d=this.targets,h=this.activeTarget,e;for(e=f.length;e--;){h!=d[e]&&g>=f[e]&&(!f[e+1]||g<=f[e+1])&&this.activateButton(d[e])}},activateButton:function(d){this.activeTarget=d;this.$topbar.find(this.selector).parent(".active").removeClass("active");this.$topbar.find(this.selector+'[href="'+d+'"]').parent("li").addClass("active")}};b.fn.scrollSpy=function(d){var e=this.data("scrollspy");if(!e){return this.each(function(){b(this).data("scrollspy",new a(this,d))})}if(d===true){return e}if(typeof d=="string"){e[d]()}return this};b(document).ready(function(){b("body").scrollSpy("[data-scrollspy] li > a")})}(window.jQuery||window.ender);!function(c){function b(e,d){d.find("> .active").removeClass("active").find("> .dropdown-menu > .active").removeClass("active");e.addClass("active");if(e.parent(".dropdown-menu")){e.closest("li.dropdown").addClass("active")}}function a(i){var h=c(this),f=h.closest("ul:not(.dropdown-menu)"),d=h.attr("href"),g,j;if(/^#\w+/.test(d)){i.preventDefault();if(h.parent("li").hasClass("active")){return}g=f.find(".active a").last()[0];j=c(d);b(h.parent("li"),f);b(j,j.parent());h.trigger({type:"change",relatedTarget:g})}}c.fn.tabs=c.fn.pills=function(d){return this.each(function(){c(this).delegate(d||".tabs li > a, .pills > li > a","click",a)})};c(document).ready(function(){c("body").tabs("ul[data-tabs] li > a, ul[data-pills] > li > a")})}(window.jQuery||window.ender);!function(c){var a;c(document).ready(function(){c.support.transition=(function(){var f=document.body||document.documentElement,g=f.style,e=g.transition!==undefined||g.WebkitTransition!==undefined||g.MozTransition!==undefined||g.MsTransition!==undefined||g.OTransition!==undefined;return e})();if(c.support.transition){a="TransitionEnd";if(c.browser.webkit){a="webkitTransitionEnd"}else{if(c.browser.mozilla){a="transitionend"}else{if(c.browser.opera){a="oTransitionEnd"}}}}});var d=function(f,e){this.$element=c(f);this.options=e;this.enabled=true;this.fixTitle()};d.prototype={show:function(){var j,f,i,e,h,g;if(this.hasContent()&&this.enabled){h=this.tip();this.setContent();if(this.options.animate){h.addClass("fade")}h.remove().css({top:0,left:0,display:"block"}).prependTo(document.body);j=c.extend({},this.$element.offset(),{width:this.$element[0].offsetWidth,height:this.$element[0].offsetHeight});f=h[0].offsetWidth;i=h[0].offsetHeight;e=b(this.options.placement,this,[h[0],this.$element[0]]);switch(e){case"below":g={top:j.top+j.height+this.options.offset,left:j.left+j.width/2-f/2};break;case"above":g={top:j.top-i-this.options.offset,left:j.left+j.width/2-f/2};break;case"left":g={top:j.top+j.height/2-i/2,left:j.left-f-this.options.offset};break;case"right":g={top:j.top+j.height/2-i/2,left:j.left+j.width+this.options.offset};break}h.css(g).addClass(e).addClass("in")}},setContent:function(){var e=this.tip();e.find(".twipsy-inner")[this.options.html?"html":"text"](this.getTitle());e[0].className="twipsy"},hide:function(){var f=this,g=this.tip();g.removeClass("in");function e(){g.remove()}c.support.transition&&this.$tip.hasClass("fade")?g.bind(a,e):e()},fixTitle:function(){var e=this.$element;if(e.attr("title")||typeof(e.attr("data-original-title"))!="string"){e.attr("data-original-title",e.attr("title")||"").removeAttr("title")}},hasContent:function(){return this.getTitle()},getTitle:function(){var g,e=this.$element,f=this.options;this.fixTitle();if(typeof f.title=="string"){g=e.attr(f.title=="title"?"data-original-title":f.title)}else{if(typeof f.title=="function"){g=f.title.call(e[0])}}g=(""+g).replace(/(^\s*|\s*$)/,"");return g||f.fallback},tip:function(){return this.$tip=this.$tip||c('<div class="twipsy" />').html(this.options.template)},validate:function(){if(!this.$element[0].parentNode){this.hide();this.$element=null;this.options=null}},enable:function(){this.enabled=true},disable:function(){this.enabled=false},toggleEnabled:function(){this.enabled=!this.enabled},toggle:function(){this[this.tip().hasClass("in")?"hide":"show"]()}};function b(g,e,f){return typeof g=="function"?g.apply(e,f):g}c.fn.twipsy=function(e){c.fn.twipsy.initWith.call(this,e,d,"twipsy");return this};c.fn.twipsy.initWith=function(n,j,e){var m,l,h,g;if(n===true){return this.data(e)}else{if(typeof n=="string"){m=this.data(e);if(m){m[n]()}return this}}n=c.extend({},c.fn[e].defaults,n);function f(p){var o=c.data(p,e);if(!o){o=new j(p,c.fn.twipsy.elementOptions(p,n));c.data(p,e,o)}return o}function i(){var o=f(this);o.hoverState="in";if(n.delayIn==0){o.show()}else{o.fixTitle();setTimeout(function(){if(o.hoverState=="in"){o.show()}},n.delayIn)}}function k(){var o=f(this);o.hoverState="out";if(n.delayOut==0){o.hide()}else{setTimeout(function(){if(o.hoverState=="out"){o.hide()}},n.delayOut)}}if(!n.live){this.each(function(){f(this)})}if(n.trigger!="manual"){l=n.live?"live":"bind";h=n.trigger=="hover"?"mouseenter":"focus";g=n.trigger=="hover"?"mouseleave":"blur";this[l](h,i)[l](g,k)}return this};c.fn.twipsy.Twipsy=d;c.fn.twipsy.defaults={animate:true,delayIn:0,delayOut:0,fallback:"",placement:"above",html:false,live:false,offset:0,title:"title",trigger:"hover",template:'<div class="twipsy-arrow"></div><div class="twipsy-inner"></div>'};c.fn.twipsy.rejectAttrOptions=["title"];c.fn.twipsy.elementOptions=function(j,f){var h=c(j).data(),e=c.fn.twipsy.rejectAttrOptions,g=e.length;while(g--){delete h[e[g]]}return c.extend({},f,h)}}(window.jQuery||window.ender);!function(b){var a=function(d,c){this.$element=b(d);this.options=c;this.enabled=true;this.fixTitle()};a.prototype=b.extend({},b.fn.twipsy.Twipsy.prototype,{setContent:function(){var c=this.tip();c.find(this.options.titleSelector)[this.options.html?"html":"text"](this.getTitle());c.find(this.options.contentSelector)[this.options.html?"html":"text"](this.getContent());c[0].className="popover"},hasContent:function(){return this.getTitle()||this.getContent()},getContent:function(){var d,c=this.$element,e=this.options;if(typeof this.options.content=="string"){d=c.attr(this.options.content)}else{if(typeof this.options.content=="function"){d=this.options.content.call(this.$element[0])}}return d},tip:function(){if(!this.$tip){this.$tip=b('<div class="popover" />').html(this.options.template)}return this.$tip}});b.fn.popover=function(c){if(typeof c=="object"){c=b.extend({},b.fn.popover.defaults,c)}b.fn.twipsy.initWith.call(this,c,a,"popover");return this};b.fn.popover.defaults=b.extend({},b.fn.twipsy.defaults,{placement:"right",content:"data-content",template:'<div class="arrow"></div><div class="inner"><h3 class="title"></h3><div class="content"><p></p></div></div>',titleSelector:".title",contentSelector:".content p"});b.fn.twipsy.rejectAttrOptions.push("content")}(window.jQuery||window.ender);

(function(e){var a="0.7.2",h=e.extend,d=/^(\d{4})-(\d\d)-(\d\d)T(\d\d):(\d\d)(?::(\d\d)(?:[.](\d+))?)?(?:([-+]\d\d):(\d\d)|Z)$/,b=function(j,i,k){if(k){j.setHours(c.H(j)-i,c.M(j)+(i>0?-k:+k))}return j},f=function(i,j){return(i+1000+"").substr(4-(j||2))},c={yy:function(i){return f(c.yyyy(i)%100)},yyyy:function(i){return i.getFullYear()},m:function(i){return i.getMonth()+1},mm:function(i){return f(c.m(i))},mmm:function(i){return g.abbrMonths[c.m(i)-1]},mmmm:function(i){return g.fullMonths[c.m(i)-1]},d:function(i){return i.getDate()},dd:function(i){return f(c.d(i))},ddd:function(i){return g.abbrDays[i.getDay()]},dddd:function(i){return g.fullDays[i.getDay()]},o:function(i){return g.ordinals(c.d(i))},h:function(i){return c.H(i)%12||12},hh:function(i){return f(c.h(i))},H:function(i){return i.getHours()},HH:function(i){return f(c.H(i))},M:function(i){return i.getMinutes()},MM:function(i){return f(c.M(i))},s:function(i){return i.getSeconds()},ss:function(i){return f(c.s(i))},S:function(i){return c.s(i)+"."+f(i%1000,3)},SS:function(i){return c.ss(i)+"."+f(i%1000,3)},a:function(i){return g.periods[+(c.H(i)>11)]},Z:function(i){var k=-i.getTimezoneOffset(),j=Math.abs(k);return(k<0?"-":"+")+f(j/60>>0)+":"+f(j%60)}},g=function(j,p){if(!(j instanceof Date)){p=j;j=new Date}p=p||g.format;if(typeof p==="function"){return p(j)}var o=p.replace("~","~T").replace("%%","~P")+"%",m,k="",q=0,n=o.length,i="",l;while(q<n){m=o.charAt(q++);if(k){if(m===l||k==="%"){k+=m}else{k=k.substr(1);i+=c.hasOwnProperty(k)?g.escaped?e("<b>").text(c[k](j)).html():c[k](j):k}}if(!/%/.test(k)){if(/%/.test(p)){if(m==="%"){k="%"}else{k="";i+=m}}else{k="%"+m}}l=m}return i.replace("~P","%").replace("~T","~")};h(g,{abbrDays:"Sun,Mon,Tue,Wed,Thu,Fri,Sat".split(","),abbrMonths:"Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec".split(","),format:"d mmmm yyyy",fullDays:"Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday".split(","),fullMonths:"January,February,March,April,May,June,July,August,September,October,November,December".split(","),ordinals:function(i){return i+["th","st","nd","rd"][i>10&&i<14||(i%=10)>3?0:i]},periods:["AM","PM"]});e.localize=g;e.localize.version=a;e.fn.localize=function(k){var i=h({},g),j,l;if(typeof k==="object"){h(i,k);k=i.format}k=k||g.format;j=typeof k==="function";l=i.escaped?"html":"text";return this.each(function(){var p=e(this),o,n;if(/^time$/i.test(this.nodeName)){n=d.exec(p.attr("datetime")||g(new Date,"yyyy-mm-ddTHH:MM:ssZ"))}if(!n){return}o=b(new Date(Date.UTC(+n[1],n[2]-1,+n[3],+n[4],+n[5],+n[6]||0,+((n[7]||0)+"00").substr(0,3))),+n[8],n[9]);p.attr("datetime",g(o,"yyyy-mm-ddTHH:MM"+(n[7]?":SS":n[6]?":ss":"")+"Z"))[l](j?k.call(p,o):g(o,k))})}}(jQuery));

/*
 * Crypto-JS v2.5.3
 * http://code.google.com/p/crypto-js/
 * (c) 2009-2012 by Jeff Mott. All rights reserved.
 * http://code.google.com/p/crypto-js/wiki/License
 */
(typeof Crypto=="undefined"||!Crypto.util)&&function(){var m=window.Crypto={},o=m.util={rotl:function(h,g){return h<<g|h>>>32-g},rotr:function(h,g){return h<<32-g|h>>>g},endian:function(h){if(h.constructor==Number)return o.rotl(h,8)&16711935|o.rotl(h,24)&4278255360;for(var g=0;g<h.length;g++)h[g]=o.endian(h[g]);return h},randomBytes:function(h){for(var g=[];h>0;h--)g.push(Math.floor(Math.random()*256));return g},bytesToWords:function(h){for(var g=[],i=0,a=0;i<h.length;i++,a+=8)g[a>>>5]|=(h[i]&255)<<
24-a%32;return g},wordsToBytes:function(h){for(var g=[],i=0;i<h.length*32;i+=8)g.push(h[i>>>5]>>>24-i%32&255);return g},bytesToHex:function(h){for(var g=[],i=0;i<h.length;i++)g.push((h[i]>>>4).toString(16)),g.push((h[i]&15).toString(16));return g.join("")},hexToBytes:function(h){for(var g=[],i=0;i<h.length;i+=2)g.push(parseInt(h.substr(i,2),16));return g},bytesToBase64:function(h){if(typeof btoa=="function")return btoa(n.bytesToString(h));for(var g=[],i=0;i<h.length;i+=3)for(var a=h[i]<<16|h[i+1]<<
8|h[i+2],b=0;b<4;b++)i*8+b*6<=h.length*8?g.push("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/".charAt(a>>>6*(3-b)&63)):g.push("=");return g.join("")},base64ToBytes:function(h){if(typeof atob=="function")return n.stringToBytes(atob(h));for(var h=h.replace(/[^A-Z0-9+\/]/ig,""),g=[],i=0,a=0;i<h.length;a=++i%4)a!=0&&g.push(("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/".indexOf(h.charAt(i-1))&Math.pow(2,-2*a+8)-1)<<a*2|"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/".indexOf(h.charAt(i))>>>
6-a*2);return g}},m=m.charenc={};m.UTF8={stringToBytes:function(h){return n.stringToBytes(unescape(encodeURIComponent(h)))},bytesToString:function(h){return decodeURIComponent(escape(n.bytesToString(h)))}};var n=m.Binary={stringToBytes:function(h){for(var g=[],i=0;i<h.length;i++)g.push(h.charCodeAt(i)&255);return g},bytesToString:function(h){for(var g=[],i=0;i<h.length;i++)g.push(String.fromCharCode(h[i]));return g.join("")}}}();
(function(){var m=Crypto,o=m.util,n=m.charenc,h=n.UTF8,g=n.Binary,i=m.MD5=function(a,b){var h=o.wordsToBytes(i._md5(a));return b&&b.asBytes?h:b&&b.asString?g.bytesToString(h):o.bytesToHex(h)};i._md5=function(a){a.constructor==String&&(a=h.stringToBytes(a));for(var b=o.bytesToWords(a),g=a.length*8,a=1732584193,d=-271733879,e=-1732584194,c=271733878,f=0;f<b.length;f++)b[f]=(b[f]<<8|b[f]>>>24)&16711935|(b[f]<<24|b[f]>>>8)&4278255360;b[g>>>5]|=128<<g%32;b[(g+64>>>9<<4)+14]=g;for(var g=i._ff,j=i._gg,k=
i._hh,l=i._ii,f=0;f<b.length;f+=16)var m=a,n=d,p=e,q=c,a=g(a,d,e,c,b[f+0],7,-680876936),c=g(c,a,d,e,b[f+1],12,-389564586),e=g(e,c,a,d,b[f+2],17,606105819),d=g(d,e,c,a,b[f+3],22,-1044525330),a=g(a,d,e,c,b[f+4],7,-176418897),c=g(c,a,d,e,b[f+5],12,1200080426),e=g(e,c,a,d,b[f+6],17,-1473231341),d=g(d,e,c,a,b[f+7],22,-45705983),a=g(a,d,e,c,b[f+8],7,1770035416),c=g(c,a,d,e,b[f+9],12,-1958414417),e=g(e,c,a,d,b[f+10],17,-42063),d=g(d,e,c,a,b[f+11],22,-1990404162),a=g(a,d,e,c,b[f+12],7,1804603682),c=g(c,a,
d,e,b[f+13],12,-40341101),e=g(e,c,a,d,b[f+14],17,-1502002290),d=g(d,e,c,a,b[f+15],22,1236535329),a=j(a,d,e,c,b[f+1],5,-165796510),c=j(c,a,d,e,b[f+6],9,-1069501632),e=j(e,c,a,d,b[f+11],14,643717713),d=j(d,e,c,a,b[f+0],20,-373897302),a=j(a,d,e,c,b[f+5],5,-701558691),c=j(c,a,d,e,b[f+10],9,38016083),e=j(e,c,a,d,b[f+15],14,-660478335),d=j(d,e,c,a,b[f+4],20,-405537848),a=j(a,d,e,c,b[f+9],5,568446438),c=j(c,a,d,e,b[f+14],9,-1019803690),e=j(e,c,a,d,b[f+3],14,-187363961),d=j(d,e,c,a,b[f+8],20,1163531501),
a=j(a,d,e,c,b[f+13],5,-1444681467),c=j(c,a,d,e,b[f+2],9,-51403784),e=j(e,c,a,d,b[f+7],14,1735328473),d=j(d,e,c,a,b[f+12],20,-1926607734),a=k(a,d,e,c,b[f+5],4,-378558),c=k(c,a,d,e,b[f+8],11,-2022574463),e=k(e,c,a,d,b[f+11],16,1839030562),d=k(d,e,c,a,b[f+14],23,-35309556),a=k(a,d,e,c,b[f+1],4,-1530992060),c=k(c,a,d,e,b[f+4],11,1272893353),e=k(e,c,a,d,b[f+7],16,-155497632),d=k(d,e,c,a,b[f+10],23,-1094730640),a=k(a,d,e,c,b[f+13],4,681279174),c=k(c,a,d,e,b[f+0],11,-358537222),e=k(e,c,a,d,b[f+3],16,-722521979),
d=k(d,e,c,a,b[f+6],23,76029189),a=k(a,d,e,c,b[f+9],4,-640364487),c=k(c,a,d,e,b[f+12],11,-421815835),e=k(e,c,a,d,b[f+15],16,530742520),d=k(d,e,c,a,b[f+2],23,-995338651),a=l(a,d,e,c,b[f+0],6,-198630844),c=l(c,a,d,e,b[f+7],10,1126891415),e=l(e,c,a,d,b[f+14],15,-1416354905),d=l(d,e,c,a,b[f+5],21,-57434055),a=l(a,d,e,c,b[f+12],6,1700485571),c=l(c,a,d,e,b[f+3],10,-1894986606),e=l(e,c,a,d,b[f+10],15,-1051523),d=l(d,e,c,a,b[f+1],21,-2054922799),a=l(a,d,e,c,b[f+8],6,1873313359),c=l(c,a,d,e,b[f+15],10,-30611744),
e=l(e,c,a,d,b[f+6],15,-1560198380),d=l(d,e,c,a,b[f+13],21,1309151649),a=l(a,d,e,c,b[f+4],6,-145523070),c=l(c,a,d,e,b[f+11],10,-1120210379),e=l(e,c,a,d,b[f+2],15,718787259),d=l(d,e,c,a,b[f+9],21,-343485551),a=a+m>>>0,d=d+n>>>0,e=e+p>>>0,c=c+q>>>0;return o.endian([a,d,e,c])};i._ff=function(a,b,g,d,e,c,f){a=a+(b&g|~b&d)+(e>>>0)+f;return(a<<c|a>>>32-c)+b};i._gg=function(a,b,g,d,e,c,f){a=a+(b&d|g&~d)+(e>>>0)+f;return(a<<c|a>>>32-c)+b};i._hh=function(a,b,g,d,e,c,f){a=a+(b^g^d)+(e>>>0)+f;return(a<<c|a>>>
32-c)+b};i._ii=function(a,b,g,d,e,c,f){a=a+(g^(b|~d))+(e>>>0)+f;return(a<<c|a>>>32-c)+b};i._blocksize=16;i._digestsize=16})();

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