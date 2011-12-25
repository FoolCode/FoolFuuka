jQuery(document).ready(function() {
	jQuery("[data-rel=popover-below]").popover({
		offset: 10,
		html: true
	});
	jQuery("[data-rel=popover]").popover({
		offset: 10,
		html: true
	});

	post = location.href.split(/#/);
	if (post[1]) {
		if (post[1].match(/^q\d+(_\d+)?$/)) {
			post[1] = post[1].replace('q', '').replace('_', ',');
			jQuery("#reply_comment").append(">>" + post[1] + "\n");
			post[1] = post[1].replace(',', '_')
		}
		replyHighlight(post[1]);
	}

	if (typeof thread_id != "undefined")
	{
		jQuery('.js_hook_realtimethread').html('This thread is being displayed in real time. <a class="btnr" href="#" onClick="realtimethread(); return false;">Update now</a>');
		backlinkify();
		realtimethread();
	}
	bindFunctions();
	timify();
});

var timelapse = 10;
var currentlapse = 0;
var realtimethread = function(){
	clearTimeout(currentlapse);
	jQuery.ajax({
		url: site_url + 'api/chan/thread/' ,
		async: false,
		dataType: 'json',
		type: 'GET',
		cache: false,
		data: {
			num : thread_id,
			board: board_shortname,
			timestamp: thread_latest_timestamp
		},
		success: function(data){
			if(data[thread_id].posts instanceof Array) {
				jQuery.each(data[thread_id].posts, function(idx, value){
					if(typeof thread_json[value.num] != undefined)
					{
						thread_json[thread_id].posts.push(value);
						jQuery('article.thread aside').append(value.formatted);
					}
				});
				thread_latest_timestamp = data[thread_id].posts[data[thread_id].posts.length-1].timestamp;
				timelapse = 10;
				realtime_callback();
			}
			else
			{
				if(timelapse < 30)
				{
					timelapse += 10;
				}
			}
			currentlapse = setTimeout(realtimethread, timelapse*1000);
		},
		error: function(jqXHR, textStatus, errorThrown) {
		},
		complete: function() {
		}
	});

	return false;
}

var realtime_callback = function(){
	backlinkify();
	bindFunctions();
	timify();
}

var timify = function() {
	jQuery("time").localize('ddd mmm dd HH:MM:ss yyyy');
}

var backlinkify = function()
{
	var backlinks = new Object;
	jQuery("article").each(function() {
		var that = jQuery(this);
		var post_id = that.attr('id');

		backlinks[post_id] = [];
		if (post_id != thread_id)
		{
			jQuery.each(that.find("[data-backlink=true]"), function(idx, post) {
				p_id = jQuery(post).text().replace('>>', '')

				if (typeof backlinks[p_id] == "undefined")
				{
					backlinks[p_id] = [];
				}

				backlinks[p_id].push('<a href="' + post.baseURI + '#' + post_id + '" data-function="highlight" data-backlink="true" data-post="' + post_id + '">&gt;&gt;' + post_id + '</a>');
				backlinks[p_id] = eliminateDuplicates(backlinks[p_id]);
			});
		}
	});


	jQuery(".post_backlink").each(function() {
		var that = jQuery(this);
		if(backlinks[that.data('post')].length > 0)
		{
			that.html(backlinks[that.data('post')].join(" "));
			that.parent().show();
		}
	});

	// how could we make it working well on cellphones?
	if( navigator.userAgent.match(/Android/i) ||
		navigator.userAgent.match(/webOS/i) ||
		navigator.userAgent.match(/iPhone/i) ||
		navigator.userAgent.match(/iPad/i) ||
		navigator.userAgent.match(/iPod/i) ||
		navigator.userAgent.match(/BlackBerry/)
		){
		return false;
	}

	jQuery("[data-backlink=true]").hover(
		function() {
			var backlink = jQuery("#backlink");
			var that = jQuery(this);

			var pos = that.offset();
			var height = that.height();

			backlink.css({
				left: (pos.left + 5) + 'px',
				top: (pos.top + height + 3) + 'px'
			});

			if(thread_id == that.data('post'))
			{
				quote = thread_json[thread_id].op;
				backlink.css('display', 'block').html(quote.formatted);
			}
			else
			{
				jQuery.each(thread_json[thread_id].posts, function(idx, quote) {
					if ((that.data('post') == quote.num + '_' + quote.subnum) || (that.data('post') == quote.num && quote.subnum == 0 ) || (that.data('post') == quote.parent))
					{
						backlink.css('display', 'block').html(quote.formatted);
					}
				});
			}

			backlink.find("article").removeAttr("id").find(".post_controls").remove();
			backlink.find(".post_file_controls").remove();
		},
		function () {
			jQuery("#backlink").css('display', 'none').html('');
		}
	);
}

var bindFunctions = function()
{
	jQuery("[data-function]").click(function() {
		var el = jQuery(this);
		var post = el.data("post");
		var modal = jQuery("#post_tools_modal");
		switch (el.data("function")) {
			case 'highlight':
				if (post) replyHighlight(post);
				break;

			case 'quote':
				jQuery("#reply_comment").append(">>" + post + "\n");
				break;

			case 'comment':
				jQuery.ajax({
					url: site_url + board_shortname + '/sending' ,
					async: false,
					dataType: 'json',
					type: 'POST',
					cache: false,
					data: {
						reply_numero: post,
						reply_bokunonome: jQuery("#reply_name").val(),
						reply_elittera: jQuery("#reply_email").val(),
						reply_talkingde: jQuery("#reply_subject").val(),
						reply_chennodiscursus: jQuery("#reply_comment").val(),
						reply_nymphassword: jQuery("#reply_password").val(),
						reply_action: 'Submit'
					},
					success: function(data){
						var reply_alert = jQuery("#reply_alert");
						if (data.error != "")
						{
							reply_alert.html(data.error);
							return false;
						}
						reply_alert.html(data.success);
						setTimeout(function() { reply_alert.html('') }, 5000);
						jQuery("#reply_comment").val('')
						realtimethread();
					},
					error: function(jqXHR, textStatus, errorThrown) {
					},
					complete: function() {
					}
				});
				return false;
				break;

			case 'delete':
				var foolfuuka_reply_password = getCookie('foolfuuka_reply_password');
				modal.find(".title").html('Delete - Post No. ' + el.data("post-id"));
				modal.find(".modal-loading").hide();
				modal.find(".modal-information").html('\
					<span class="modal-label">Password</span>\n\
					<input type="password" class="modal-password" />\n\
					<input type="hidden" class="modal-post-id" value="' + post + '" />');
				modal.find(".submitModal").data("action", 'delete');
				if(foolfuuka_reply_password != null)
				{
					modal.find(".modal-password").val(foolfuuka_reply_password);
				}
				break;

			case 'report':
				modal.find(".title").html('Report - Post No.' + el.data("post-id"));
				modal.find(".modal-loading").hide();
				modal.find(".modal-information").html('\
					<span class="modal-label">Post ID</span>\n\
					<input type="text" class="modal-post" value="' + el.data("post-id") + '" readonly="readonly" />\n\
					<input type="hidden" class="modal-post-id" value="' + post + '" />\n\
					<span class="modal-field">Comment</span>\n\
					<textarea class="modal-comment"></textarea>');
				modal.find(".submitModal").data("action", 'report');
				break;

			case 'closeModal':
				el.closest(".modal").modal('hide');
				return false;
				break;

			case 'submitModal':
				var loading = modal.find(".modal-loading");
				var action = $(this).data("action");
				var _post = modal.find(".modal-post-id").val();
				var _href = $(this).data(action) + '/' + _post + '/';

				if (action == 'report') {
					var _data = {
						post: _post,
						reason: modal.find(".modal-comment").val()
					};
				}
				else if (action == 'delete') {
					var _data = {
						post: _post,
						password: modal.find(".modal-password").val()
					};
				}
				else {
					// Stop It! Unable to determine what action to use.
					return false;
				}

				jQuery.post(_href, _data, function(result) {
					loading.hide();
					if (result.status == 'failed') {
						modal.find(".modal-error").html('<div class="alert-message error fade in" data-alert="alert"><a class="close" href="#">&times;</a><p>' + result.reason + '</p></div>');
						return false;
					}
					modal.modal('hide');

					if (action == 'report') {
						toggleHighlight(modal.find(".modal-post").val().replace(',', '_'), 'reported', false);
					}
					else if (action == 'delete') {
						jQuery('.doc_id_' + post).hide();
					}
				}, 'json');
				return false;
				break;

			default:
				break;
		}
	});

	/*
	jQuery("[data-expand=true]").click(function() {
		var that = jQuery(this).children();

		// Update Dimensions
		that.attr("width", that.data('width'))
		that.attr("height", that.data('height'))

		// Update Image
		that.attr("src", this.href);

		return false;
	});
	*/
}

var toggleSearch = function(mode)
{
	var search;
	if (!(search = document.getElementById('search_' + mode))) return;
	search.style.display = search.style.display ? "" : "none";
}

var getSearch = function(type, searchForm)
{
	var location = searchForm.action;

	if (searchForm.text.value != "")
		location += 'text/' + encodeURIComponent(searchForm.text.value) + '/';

	if (type == 'advanced')
	{
		if (searchForm.username.value != "")
			location += 'username/' + encodeURIComponent(searchForm.username.value) + '/';

		if (searchForm.tripcode.value != "")
			location += 'tripcode/' + encodeURIComponent(searchForm.tripcode.value) + '/';

		if (getRadioValue(searchForm.deleted) != "")
			location += 'deleted/' + getRadioValue(searchForm.deleted) + '/';

		if (getRadioValue(searchForm.ghost) != "")
			location += 'ghost/' + getRadioValue(searchForm.ghost) + '/';

		location += 'order/' + getRadioValue(searchForm.order) + '/';
	}

	window.location = location;
}

var getPost = function(postForm)
{
	if (postForm.post.value == "") {
		alert('Sorry, you must insert a valid post number.');
		return false;
	}
	window.location = postForm.action + encodeURIComponent(postForm.post.value) + '/';
}

var getPage = function(pageForm)
{
	if (pageForm.page.value == "") {
		alert('Sorry, you must insert a valid page number.');
		return false;
	}
	window.location = pageForm.action + encodeURIComponent(pageForm.page.value) + '/';
}

var getRadioValue = function(group)
{
	for (index = 0; index < group.length; index++)
	{
		if (group[index].checked == true)
			return encodeURIComponent(group[index].value);
	}
}

function toggleHighlight(id, classn, single)
{
	jQuery("article").each(function() {
		var post = jQuery(this);

		if (post.hasClass(classn) && single)
		{
			post.removeClass(classn);
		}

		if (post.attr("id") == id)
		{
			post.addClass(classn);
		}
	})
}

function replyHighlight(id)
{
	toggleHighlight(id, 'highlight', true);
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


function eliminateDuplicates(arr) {
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
