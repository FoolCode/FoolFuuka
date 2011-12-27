var selected_style;

function replyhighlight(id) {
  var tdtags = document.getElementsByTagName("td");
  var new_selected_style = "reply";
  for (i = 0; i < tdtags.length; i++) {
    if (tdtags[i].className == "highlight") {
      tdtags[i].className = selected_style;
    }
    if (tdtags[i].id == id) {
      new_selected_style = tdtags[i].className;
      tdtags[i].className = "highlight";
    }
  }
  selected_style = new_selected_style;
}

function insert(text) {
  var textarea = document.forms.postform.KOMENTO;
  if (!textarea) return;

  if (textarea.createTextRange && textarea.caretPos) {
    var caretPos = textarea.caretPos;
    caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == " " ? text + " " : text;
  } else if (textarea.setSelectionRange) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    textarea.value = textarea.value.substr(0, start) + text + textarea.value.substr(end);
    textarea.setSelectionRange(start + text.length, start + text.length);
  } else {
    textarea.value += text + " ";
  }
  textarea.focus();
}

function get_cookie(name) {
  with(document.cookie) {
    var regexp = new RegExp("(^|;\\s+)" + name + "=(.*?)(;|$)");
    var hit = regexp.exec(document.cookie);
    if (hit && hit.length > 2) return decodeURIComponent(hit[2]);
    else
    return '';
  }
};

function toggle(id) {
  var elem;

  if (!(elem = document.getElementById(id))) return;

  elem.style.display = elem.style.display ? "" : "none";
}

window.onload = function() {
  arr = location.href.split(/#/);
  if (arr[1]) replyhighlight(arr[1]);

  if (document.forms.postform && document.forms.postform.NAMAE) document.forms.postform.NAMAE.value = get_cookie("name");

  if (document.forms.postform && document.forms.postform.MERU) document.forms.postform.MERU.value = get_cookie("email");

  if (document.forms.postform && document.forms.postform.delpass) document.forms.postform.delpass.value = get_cookie("delpass");
}

function createCookie(name, value, days) {
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    var expires = "; expires=" + date.toGMTString();
  } else
  var expires = "";
  document.cookie = name + "=" + value + expires + "; path=/";
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

function eraseCookie(name) {
  createCookie(name, "", -1);
}

var displayedImages = new Array();

var showImages = function() {
  jQuery('.subreply blockquote').each(function(index, el) {
    var counter = 0;
    jQuery('a:not(:has(img))', $(el)).each(function(i, e) {
      var href = $(e).attr('href');
      var results = href.match(/http:\/\/\S+(\.png|\.jpg|\.gif)/g);
      if (results instanceof Array) {
        var stop = false;
        jQuery.each(displayedImages, function(idx, val) {
          if (results[0].indexOf(val) > -1 || results[0].indexOf('http://', 5) > -1) {
            displayedImages.push(results[0]);
            stop = true;
            jQuery(el).parent('.subreply').parent().hide();
            return false;
          }
        });
        if (stop) {
          return false;
        }
        displayedImages.push(results[0]);
        var isImgur = null;
        isImgur = href.match(/http:\/\/i\.imgur\S+(\.png|\.jpg|\.gif)/g);
        if (isImgur instanceof Array) {
          var idxof = isImgur[0].lastIndexOf('.');
          edited = results[0].substr(0, idxof) + 'm';
          edited += results[0].substr(idxof);
          $(e).replaceWith("<a target=\"_blank\" href=\"" + results[0] + "\"><img src=\"" + edited + "\" style=\"max-width:200px;max-height:200px\" /></a>");
        }
        $(e).replaceWith("<a target=\"_blank\" href=\"" + results[0] + "\"><img src=\"" + results[0] + "\" style=\"max-width:200px;max-height:200px\" /></a>");
        $(e).find('img').load(function() {

        });
        return false;
        counter++;
      }
      if (counter == 1) return false;
    });
  });
}