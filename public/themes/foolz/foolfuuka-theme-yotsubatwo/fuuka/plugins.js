var selected_style;

function fuel_set_csrf_token(form)
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

function setCookie(name, value, expires, path, domain, secure)
{
	var today = new Date();

	if (expires)
	{
		expires = expires * 1000 * 60 * 60 * 24;
	}

	var expires_date = new Date(today.getTime() + (expires));
	document.cookie = name + "=" + escape(value) +
		( expires ? ";expires=" + expires_date.toGMTString() : "" ) +
		( path ? ";path=" + path : "" ) +
		( domain ? ";domain=" + domain : "" ) +
		( secure ? ";secure" : "" );
}

var toggle = function(element)
{
	var el;

	if (!(el = document.getElementById(element))) return;
	el.style.display = el.style.display ? "" : "none";
}

var replyHighlight = function(post)
{
	var new_selected_style = "reply";
	var posts = document.getElementsByTagName("td");

	for (p = 0; p < posts.length; p++)
	{
		if (posts[p].className == "highlight")
		{
			posts[p].className = selected_style;
		}

		if (posts[p].id == post)
		{
			new_selected_style = posts[p].className;
			posts[p].className = "highlight";
		}
	}

	selected_style = new_selected_style;
	window.location.hash = '#' + post;
}

var replyQuote = function(text)
{
	var replybox = document.forms.postform.form_KOMENTO;
	if (!replybox) return;

	if (replybox.createTextRage && replybox.caretPos)
	{
		var caretPos = replybox.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == " " ? text + " " : text;
	}
	else if (replybox.setSelectionRange)
	{
		var start = replybox.selectionStart;
		var end = replybox.selectionEnd;

		replybox.value = replybox.value.substr(0, start) + text + replybox.value.substr(end);
		replybox.setSelectionRange(start + text.length, start + text.length);
	}
	else
	{
		replybox.value += text + " ";
	}
}

var viewPost = function(postForm)
{
	if (postForm.post.value == "")
	{
		alert('Sorry, you must enter a valid post number.');
		return;
	}

	var post = postForm.post.value.match(/(?:^|\/)(\d+)(?:[_,]([0-9]*))?/);
	window.location = postForm.action + encodeURIComponent(((typeof post[1] != 'undefined') ? post[1] : '') + ((typeof post[2] != 'undefined') ? '_' + post[2] : '')) + '/';
}

var backlinkify = function()
{
	var p, b, backlinks = document.getElementsByClassName('backlink');
	for (p = 0, b = backlinks.length; p < b; ++p)
	{
		backlinks[p].addEventListener('mouseover', doBacklink, false);
		backlinks[p].addEventListener('mouseout', rmBacklink, false);
	}
}

var backlinkjqXHR = null;
var backlinkFetched = {};

var doBacklink = function(el)
{
	var parent, doc, clr, src, blk, x, y, w, maxWidth = 500;
	el = el.target || window.event.srcElement;

	blk = document.createElement('div');
	blk.id = 'quote-preview';

	if ((src = document.getElementById(el.getAttribute('href').split('#')[1])))
	{
		w = src.offsetWidth;
		if (w > maxWidth)
		{
			w = maxWidth;
		}

		src = src.cloneNode(true);
		src.id = 'quote-preview-s';
		if (src.tagName == 'DIV')
		{
			src.setAttribute('class', 'quote-preview-op');
			clr = document.createElement('div');
			clr.setAttribute('class', 'newthr');
			src.appendChild(clr);
		}

		x = 0;
		y = el.offsetHeight + 1;
		parent = el;
		do {
			x += parent.offsetLeft;
			y += parent.offsetTop;
		} while (parent = parent.offsetParent);

		if ((doc = document.body.offsetWidth - x - w) < 0)
		{
			x += doc;
		}

		blk.setAttribute('style', 'left:' + x + 'px; top:' + y + 'px;');
		blk.appendChild(src);
		document.body.appendChild(blk);
	}
	else
	{
		var post = el.href.match(/\/(\w+)\/post\/(.*?)\/$/);

		if (typeof backlinkFetched[post[1] + '_' + post[2]] !== 'undefined')
		{
			src = document.createElement('div');
			src.innerHTML = backlinkFetched[post[1] + '_' + post[2]].formatted;

			w = maxWidth;
			x = 0;
			y = el.offsetHeight + 1;
			parent = el;
			do {
				x += parent.offsetLeft;
				y += parent.offsetTop;
			} while (parent = parent.offsetParent);

			if ((doc = document.body.offsetWidth - x - w) < 0)
			{
				x += doc;
			}

			blk.setAttribute('style', 'left:' + x + 'px; top:' + y + 'px;');
			blk.appendChild(src);
			document.body.appendChild(blk);
			return false;
		}

		backlinkjqXHR = jQuery.ajax({
			url: backend_vars.api_url + '_/api/chan/post/',
			dataType: 'json',
			type: 'GET',
			data: {
				board: post[1],
				num: post[2],
				theme: backend_vars.selected_theme
			},
			success: function(data) {
				if (typeof data.error !== "undefined")
				{
					// post not found
					return false;
				}
				
				backlinkFetched[post[1] + '_' + post[2]] = data;
				backlinkjqXHR = null;
				src = document.createElement('div');
				src.innerHTML = data.formatted;

				w = maxWidth;
				x = 0;
				y = el.offsetHeight + 1;
				parent = el;
				do {
					x += parent.offsetLeft;
					y += parent.offsetTop;
				} while (parent = parent.offsetParent);

				if ((doc = document.body.offsetWidth - x - w) < 0)
				{
					x += doc;
				}

				blk.setAttribute('style', 'left:' + x + 'px; top:' + y + 'px;');
				blk.appendChild(src);
				document.body.appendChild(blk);
			}
		});
	}
}

var rmBacklink = function(el)
{
	var blk;
	if ((blk = document.getElementById('quote-preview')))
	{
		document.body.removeChild(blk);
	}

	if (backlinkjqXHR !== null)
	{
		backlinkjqXHR.abort();
	}
}

var run = function()
{
	var post = location.href.split(/#/);

	if (post[1])
	{
		replyHighlight(post[1]);
	}

	backlinkify();
}

if (window.addEventListener)
{
	window.addEventListener('DOMContentLoaded', run, false);
}
else
{
	window.onload = run;
}