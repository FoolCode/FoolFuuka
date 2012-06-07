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

		if (hit && hit.length > 2)
			return decodeURIComponent(hit[2]);
		else
			return '';
	}
};

function toggle(id) {
	var elem;

	if (!(elem = document.getElementById(id))) return;
	elem.style.display = elem.style.display ? "" : "none";
}

function setCookie( name, value, expires, path, domain, secure )
{
		// set time, it's in milliseconds
		var today = new Date();
		today.setTime( today.getTime() );

		/*
		if the expires variable is set, make the correct
		expires time, the current script below will set
		it for x number of days, to make it for hours,
		delete * 24, for minutes, delete * 60 * 24
		*/
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

var getPost = function(postForm)
{
	if (postForm.post.value == "") {
		alert('Sorry, you must insert a valid post number.');
		return false;
	}
	var post = postForm.post.value.match(/(?:^|\/)(\d+)(?:[_,]([0-9]*))?/);
	window.location = postForm.action + encodeURIComponent(((typeof post[1] != 'undefined') ? post[1] : '') + ((typeof post[2] != 'undefined') ? '_' + post[2] : '')) + '/';
}

var changeTheme = function(theme)
{
	setCookie('foolfuuka_theme', theme, 30, '/');
	window.location.reload();
}

window.onload = function() {
		arr = location.href.split(/#/);
		if (arr[1]) {
				if (arr[1].charAt(0) != 'p')
						replyhighlight('p' + arr[1]);
				else
						replyhighlight(arr[1]);
		}
}