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



function setCookie(c_name,value,exdays)
{
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
	document.cookie=c_name + "=" + c_value;
}


function getCookie( check_name ) {
	// first we'll split this cookie up into name/value pairs
	// note: document.cookie only returns name=value, not the other components
	var a_all_cookies = document.cookie.split( ';' );
	var a_temp_cookie = '';
	var cookie_name = '';
	var cookie_value = '';
	var b_cookie_found = false; // set boolean t/f default f

	for ( i = 0; i < a_all_cookies.length; i++ )
	{
		// now we'll split apart each name=value pair
		a_temp_cookie = a_all_cookies[i].split( '=' );


		// and trim left/right whitespace while we're at it
		cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');

		// if the extracted name matches passed check_name
		if ( cookie_name == check_name )
		{
			b_cookie_found = true;
			// we need to handle case where cookie has no value but exists (no = sign, that is):
			if ( a_temp_cookie.length > 1 )
			{
				cookie_value = unescape( a_temp_cookie[1].replace(/^\s+|\s+$/g, '') );
			}
			// note that in cases where cookie is initialized but no value, null is returned
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


var changeTheme = function(theme)
{
	setCookie('foolfuuka_theme', theme, 30);
	window.location.reload();
}