function quote(text) {
    if (document.selection) {
        document.post.com.focus();
        var sel = document.selection.createRange();
        sel.text = ">>" + text + "\n";
    } else if (document.post.com.selectionStart || document.post.com.selectionStart == "0") {
        var startPos = document.post.com.selectionStart;
        var endPos = document.post.com.selectionEnd;
        document.post.com.value = document.post.com.value.substring(0, startPos) + ">>" + text + "\n" + document.post.com.value.substring(endPos, document.post.com.value.length);
    } else {
        document.post.com.value += ">>" + text + "\n";
    }
}

function replyhl(id) {
    var tdtags = document.getElementsByTagName("td");
    for (i = 0; i < tdtags.length; i++) {
        if (tdtags[i].className == "replyhl") tdtags[i].className = "reply";
        if (tdtags[i].id == id) tdtags[i].className = "replyhl";
    }
}

function repquote(rep) {
    if (rep.match(/q([0-9]+)/)) {
        rep = rep.replace(/q/, "");
        if (document.post.com.value == "") {
            quote(rep);
        }
    }
}

function reppop(url) {
    var day = new Date();
    var id = day.getTime();
    window.open(url, id, 'toolbar=0,scrollbars=0,location=0,status=1,menubar=0,resizable=1,width=660,height=192');
    return false;
}

function https_rewrite1(t,attr) {
    var a = document.getElementsByTagName(t);
    for (e in a) {
        try {
            var s = a[e].getAttribute(attr);
            a[e].setAttribute(attr, s.replace("http:","https:").replace(/[0-9]+\.thumbs/, "thumbs"));
        }
        catch(ee)
        {}
    }
}

function page_postload() {
    if (window.location.protocol=="https:") {
        https_rewrite1("form","action");
        https_rewrite1("a","href");
        https_rewrite1("img","src");
        https_rewrite1("link","href");
    }
}

function https_rewrite() {
    page_postload();
}

function recaptcha_load() {
    var d = document.getElementById("recaptcha_div");
    if (!d) return;

    Recaptcha.create("6Ldp2bsSAAAAAAJ5uyx_lx34lJeEpTLVkP5k04qc", "recaptcha_div",{theme: "clean"});
}

function init() {
    var arr = location.href.split(/#/);
    if (arr[1]) {
        if (arr[1].match(/(q)?([0-9]+)/)) {
            var rep = arr[1];
            var re = arr[1].replace(/q/, "");
            replyhl(re);
            repquote(rep);
        }
    }
    if (typeof style_group != "undefined" && style_group) {
        var cookie = readCookie(style_group);
        var title = cookie ? cookie : getPreferredStyleSheet();
        setActiveStyleSheet(title);
    }

    if (typeof jsMath != "undefined" && typeof jsMath.Easy.onload != "undefined" && !jsMath.Easy.loaded) jsMath.Easy.onload();

    /* if (typeof Recaptcha != "undefined") recaptcha_load(); */
}

function uninit() {
    var title = getActiveStyleSheet();
    if (!title) return;
    createCookie(style_group, title, 365, ".4chan.org");
}

function setActiveStyleSheet(title) {
    var a;
    var link;
    var href = '';
    for (var i = 0; (a = document.getElementsByTagName("link")[i]); i++) {
          if (a.getAttribute("title") == "switch")
               link = a;
          if (a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title")) {
                if (a.getAttribute("title") == title) {
                    href = a.href;
                    }
               }
    }
    link.setAttribute("href", href);
}

function getActiveStyleSheet() {
    var i, a;
    var link;
    for (i = 0; (a = document.getElementsByTagName("link")[i]); i++) {
        if (a.getAttribute("title") == "switch")
               link = a;
        else if (a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title") && a.href==link.href) return a.getAttribute("title");
    }
    return null;
}

function getPreferredStyleSheet() {
    return (style_group == "ws_style") ? "Yotsuba B" : "Yotsuba";
}

function createCookie(name, value, days, domain) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else expires = "";
    if (domain) domain = "; domain=" + domain;
    else domain = "";
    document.cookie = name + "=" + value + expires + "; path=/" + domain;
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function setCookie(name, value, expires, path, domain, secure) {

	var today = new Date();
	today.setTime( today.getTime() );

	if (expires) {
		expires = expires * 1000 * 60 * 60 * 24;
	}
	var expires_date = new Date( today.getTime() + (expires) );

	document.cookie = name + "=" +escape( value ) +
		( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) +
		( ( path ) ? ";path=" + path : "" ) +
		( ( domain ) ? ";domain=" + domain : "" ) +
		( ( secure ) ? ";secure" : "" );
}

var changeTheme = function(theme) {
	setCookie('foolfuuka_theme', theme, 30, '/');
	window.location.reload();
}

window.onload = init;
window.onunload = uninit;