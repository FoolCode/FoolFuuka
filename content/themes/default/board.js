var bindFunctions = function()
{
	var search_dropdown = jQuery('.search-dropdown');
		
	if(isEventSupported('dragstart') && isEventSupported('drop') && !!window.FileReader)
	{
		search_dropdown.on('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			e.originalEvent.dataTransfer.dropEffect = 'copy';
		});
		search_dropdown.on('dragenter', function(e) {
			e.preventDefault();
			e.stopPropagation();
		});
	
		search_dropdown.on('drop', function(event) {
			if(event.originalEvent.dataTransfer){
				if(event.originalEvent.dataTransfer.files.length) {
					event.preventDefault();
					event.stopPropagation();
                
					findSameImageFromFile(event.originalEvent.dataTransfer);
				}   
			}
		});
	}

	if(!!window.FileReader)
	{
		search_dropdown.find('input[name=image]').on('change', function(event) {
			findSameImageFromFile(event.originalEvent.target);
		});
	}
	
	jQuery("body").on("click", 
		"a.[data-function], button.[data-function], input.[data-function]", 
		function(event) {
			var el = jQuery(this);
			var post = el.data("post");
			var modal = jQuery("#post_tools_modal");
			switch (el.data("function")) {
				case 'highlight':
					if (post) replyHighlight(post);
					break;

				case 'quote':
					jQuery("#reply_chennodiscursus").val(jQuery("#reply_chennodiscursus").val() + ">>" + post + "\n");
					break;

				case 'comment':
					if(jQuery("#file_image").val())
						return true;
					var reply_alert = jQuery('#reply_ajax_notices');
					reply_alert.removeClass('error').removeClass('success');
					jQuery.ajax({
						url: backend_vars.site_url + board_shortname + '/submit/' ,
						dataType: 'json',
						type: 'POST',
						cache: false,
						data: {
							reply_numero: post,
							reply_bokunonome: jQuery("#reply_bokunonome").val(),
							reply_elitterae: jQuery("#reply_elitterae").val(),
							reply_talkingde: jQuery("#reply_talkingde").val(),
							reply_chennodiscursus: jQuery("#reply_chennodiscursus").val(),
							reply_nymphassword: jQuery("#reply_nymphassword").val(),
							reply_postas: jQuery("#reply_postas").val(),
							reply_gattai: 'Submit',
							csrf_fool: backend_vars.csrf_hash
						},
						success: function(data){
							if (data.error != "")
							{
								reply_alert.html(data.error);
								reply_alert.addClass('error');
								reply_alert.show();
								return false;
							}
							reply_alert.html(data.success);
							reply_alert.addClass('success');
							reply_alert.show();
							jQuery("#reply_chennodiscursus").val("");
							realtimethread();
						},
						error: function(jqXHR, textStatus, errorThrown) {
							reply_alert.html('Connection error.');
							reply_alert.addClass('error');
							reply_alert.show();
						},
						complete: function() {
						}
					});
					event.preventDefault();
					break;

				case 'realtimethread':
					realtimethread();
					event.preventDefault();
					break

				case 'delete':
					var foolfuuka_reply_password = getCookie('foolfuuka_reply_password');
					modal.find(".title").html('Delete &raquo; Post No. ' + el.data("post-id"));
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
					modal.find(".title").html('Report &raquo; Post No.' + el.data("post-id"));
					modal.find(".modal-loading").hide();
					modal.find(".modal-information").html('\
					<span class="modal-label">Post ID</span>\n\
					<input type="text" class="modal-post" value="' + el.data("post-id") + '" readonly="readonly" />\n\
					<input type="hidden" class="modal-post-id" value="' + post + '" />\n\
					<span class="modal-field">Comment</span>\n\
					<textarea class="modal-comment"></textarea>');
					modal.find(".submitModal").data("action", 'report');
					break;
					
				case 'mod':
					jQuery.ajax({
						url: backend_vars.api_url + 'api/chan/mod_post_actions/',
						dataType: 'json',
						type: 'POST',
						cache: false,
						data: {
							board: el.data('board'),
							doc_id: el.data('id'),
							actions: [el.data('action')],
							csrf_fool: backend_vars.csrf_hash
						},
						success: function(data){
							if (typeof data.error !== "undefined")
							{
								alert(data.error);
								return false;
							}
							
							// might need to be upgraded to array support
							switch(el.data('action'))
							{
								case 'remove_post':
									jQuery('.doc_id_' + el.data('id')).remove();
									break;
								case 'remove_image':
									break;
								case 'remove_report':
									jQuery('.doc_id_' + el.data('id')).removeClass('reported')
									break;
								case 'ban_user':
									jQuery('.doc_id_' + el.data('id')).find('[data-action=ban_user]').text('Banned');
									break;
								case 'ban_md5':
									break;
							}
						},
						error: function(jqXHR, textStatus, errorThrown) {
							
						},
						complete: function() {
						}
					});
					return false;
					break;

				case 'closeModal':
					el.closest(".modal").modal('hide');
					return false;
					break;

				case 'submitModal':
					var loading = modal.find(".modal-loading");
					var action = $(this).data("action");
					var _post = modal.find(".modal-post-id").val();
					var _href = $(this).data(action) + _post + '/';

					if (action == 'report') {
						var _data = {
							post: _post,
							reason: modal.find(".modal-comment").val(),
							csrf_fool: backend_vars.csrf_hash
						};
					}
					else if (action == 'delete') {
						var _data = {
							post: _post,
							password: modal.find(".modal-password").val(),
							csrf_fool: backend_vars.csrf_hash
						};
					}
					else {
						// Stop It! Unable to determine what action to use.
						return false;
					}

					jQuery.post(_href, _data, function(result) {
						loading.hide();
						if (result.status == 'failed') {
							modal.find(".modal-error").html('<div class="alert alert-error" data-alert="alert"><a class="close" href="#">&times;</a><p>' + result.reason + '</p></div>');
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
				
				case 'searchShow':
					el.parent().find('.search-dropdown-menu').show();
					el.parent().parent().addClass('active');
					break;
				
				case 'searchHide':
					el.parent().parent().hide();
					el.parent().parent().parent().parent().removeClass('active');
					break;

				default:
					break;
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

	jQuery("#main").on("mouseover mouseout", "article a.[data-backlink]", function(event) {
		if(event.type == "mouseover")
		{
			var backlink = jQuery("#backlink");
			var that = jQuery(this);

			var pos = that.offset();
			var height = that.height();
			var width = that.width();


			if(that.attr('data-backlink') != 'true')
			{
				// gallery
				thread_id = that.attr('data-backlink');
				quote = thread_json[thread_id];
				backlink.css('display', 'block');
				backlink.html(quote.formatted);
			}
			else if(thread_id == that.data('post'))
			{
				// OP
				quote = thread_op_json;
				backlink.css('display', 'block');
				backlink.html(quote.formatted);
			}
			else
			{
				// normal posts
				var toClone = jQuery('#' + that.data('post'));
				if (toClone.length == 0)
					return false;
				backlink.css('display', 'block');
				backlink.html(toClone.clone());
			}

			if(jQuery(window).width()/2 < pos.left + width/2)
			{
				backlink.css({
					right: (jQuery(window).width() - pos.left -	 width) + 'px',
					top: (pos.top + height + 3) + 'px',
					left: 'auto'
				});
			}
			else
			{
				backlink.css({
					left: (pos.left) + 'px',
					top: (pos.top + height + 3) + 'px',
					right: 'auto'
				});
			}

			backlink.find("article").removeAttr("id").find(".post_controls").remove();
			backlink.find(".post_file_controls").remove();
			if(typeof page_function !== 'undefined' && page_function == "gallery")
			{
				backlink.find(".thread_image_box").remove();
			}
			var swap_image = backlink.find('[data-original]');
			if(swap_image.length > 0)
			{
				swap_image.attr('src', swap_image.attr('data-original'));
			}
		}
		else
		{
			jQuery("#backlink").css('display', 'none').html('');
		}
	});
}

var backlinkify = function(elem, post_id, subnum)
{
	var backlinks = {};
	if(subnum > 0)
		post_id += "_" + subnum;

	elem.find("a.[data-backlink=true]").each(function(idx, post) {
		p_id = jQuery(post).text().replace('>>', '').replace(',', '_');

		if (typeof backlinks[p_id] === "undefined")
		{
			backlinks[p_id] = [];
		}

		backlinks[p_id].push('<a href="' + site_url + board_shortname + '/thread/' + thread_id + '/#' + post_id + '" data-function="highlight" data-backlink="true" data-post="' + post_id + '">&gt;&gt;' + post_id.replace('_', ',') + '</a>');
		backlinks[p_id] = eliminateDuplicates(backlinks[p_id]);
	});

	jQuery.each(backlinks, function(key, val){
		var post = jQuery("#" + key);
		if(post.length == 0)
			return false;

		var post_backlink = post.find(".post_backlink:eq(0)");
		var already_backlinked = post_backlink.text().replace('>>', '').split(' ');
		jQuery.each(already_backlinked, function(i,v){
			if(typeof val[v] !== "undefined")
			{
				delete val[v];
			}
		});

		post_backlink.html(post_backlink.html() + ((post_backlink.html().length > 0)?" ":"") + val.join(" "));
		post_backlink.parent().show();
	});
}

var timelapse = 10;
var currentlapse = 0;
var realtimethread = function(){
	clearTimeout(currentlapse);
	jQuery.ajax({
		url: site_url + 'api/chan/thread/',
		dataType: 'json',
		type: 'GET',
		cache: false,
		data: {
			num : thread_id,
			board: board_shortname,
			latest_doc_id: latest_doc_id
		},
		success: function(data){
			var w_height = jQuery(document).height();
			var found_posts = false;
			if(typeof data[thread_id] !== "undefined" && typeof data[thread_id].posts !== "undefined") {
				jQuery.each(data[thread_id].posts, function(idx, value){
					found_posts = true;
					post = jQuery(value.formatted)
					post.find("time").localize('ddd mmm dd HH:MM:ss yyyy');
					post.find('[rel=tooltip]').tooltip({
						placement: 'bottom', 
						delay: 200
					});
					post.find('[rel=tooltip_right]').tooltip({
						placement: 'right', 
						delay: 200
					});
					backlinkify(jQuery('<div>' + value.comment_processed + '</div>'), value.num, value.subnum);
					jQuery('article.thread aside').append(post);
					if(latest_doc_id < value.doc_id)
						latest_doc_id = value.doc_id;
				});
			}

			if(found_posts)
			{
				if(jQuery('#reply_form :focus').length > 0)
				{
					window.scrollBy(0, jQuery(document).height() - w_height);
				}

				timelapse = 10;
			}
			else
			{
				if(timelapse < 30)
				{
					timelapse += 5;
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

var toggleSearch = function(mode)
{
	var search;
	if (!(search = document.getElementById('search_' + mode))) return;
	search.style.display = search.style.display ? "" : "none";
}


var findSameImageFromFile = function(obj)
{
	var reader = new FileReader();
	reader.onloadend = function(evt){
		if (evt.target.readyState == FileReader.DONE) {
			var fileContents = evt.target.result;
			var digestBytes = Crypto.MD5(Crypto.charenc.Binary.stringToBytes(fileContents), {
				asBytes: true
			});
			var digestBase64 = Crypto.util.bytesToBase64(digestBytes);
			var digestBase64URL = digestBase64.replace('==', '').replace(/\//g, '_').replace(/\+/g, '-');
			document.location = backend_vars.site_url + backend_vars.shortname + '/image/' + digestBase64URL;
		}
	}
	
	reader.readAsBinaryString(obj.files[0]);
}

var getSearch = function(type, searchForm)
{
	var location = searchForm.action;

	if (searchForm.text.value != "")
		location += 'text/' + encodeURIComponent(searchForm.text.value) + '/';

	if (type == 'advanced')
	{
		if (searchForm.subject.value != "")
			location += 'subject/' + encodeURIComponent(searchForm.subject.value) + '/';

		if (searchForm.username.value != "")
			location += 'username/' + encodeURIComponent(searchForm.username.value) + '/';

		if (searchForm.tripcode.value != "")
			location += 'tripcode/' + encodeURIComponent(searchForm.tripcode.value) + '/';

		if (getRadioValue(searchForm.capcode) != "")
			location += 'capcode/' + getRadioValue(searchForm.capcode) + '/';

		if (getRadioValue(searchForm.deleted) != "")
			location += 'deleted/' + getRadioValue(searchForm.deleted) + '/';

		if (getRadioValue(searchForm.ghost) != "")
			location += 'ghost/' + getRadioValue(searchForm.ghost) + '/';

		if (getRadioValue(searchForm.type) != "")
			location += 'type/' + getRadioValue(searchForm.type) + '/';

		if (getRadioValue(searchForm.filter) != "")
			location += 'filter/' + getRadioValue(searchForm.filter) + '/';

		if (searchForm.date_start.value != "")
		{
			var validate_date = /^\d{4}-\d{2}-\d{2}$/;
			if (validate_date.test(searchForm.date_start.value))
			{
				location += 'start/' + encodeURIComponent(searchForm.date_start.value) + '/';
			}
			else
			{
				alert('Sorry, you have entered an invalid date format. (Ex: YYYY-MM-DD)');
				return false;
			}
		}

		if (searchForm.date_end.value != "")
		{
			var validate_date = /^\d{4}-\d{2}-\d{2}$/;
			if (validate_date.test(searchForm.date_end.value))
			{
				location += 'end/' + encodeURIComponent(searchForm.date_end.value) + '/';
			}
			else
			{
				alert('Sorry, you have entered an invalid date format. (Ex: YYYY-MM-DD)');
				return false;
			}
		}

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
	var post = postForm.post.value.match(/(?:^|\/)(\d+)(?:[_,]([0-9]*))?/);
	window.location = postForm.action + encodeURIComponent(((typeof post[1] != 'undefined') ? post[1] : '') + ((typeof post[2] != 'undefined') ? '_' + post[2] : '')) + '/';
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
	if (typeof group == "undefined")
		return '';

	for (index = 0; index < group.length; index++)
	{
		if (group[index].checked == true)
			return encodeURIComponent(group[index].value);
	}
}

var getCheckValue = function(group)
{
	if (typeof group == "undefined")
		return '';

	var values = new Array();
	for (index = 0; index < group.length; index++)
	{
		if (group[index].checked == true)
			values.push(group[index].value);
	}

	return encodeURIComponent(values.join("-"));
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

var changeTheme = function(theme)
{
	setCookie('foolfuuka_theme', theme, 30, '/');
	window.location.reload();
}


function setCookie( name, value, expires, path, domain, secure )
{
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


function getCookie( check_name ) {
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

jQuery(document).ready(function() {

	var lazyloaded = jQuery('img.lazyload');
	if(lazyloaded.length > 149)
	{
		lazyloaded.lazyload({
			threshold: 1000,
			event: 'scroll'
		});
	}

	// spin it fast
	var brand = jQuery('.brand:eq(0)');
	jQuery('.letters').show();
	var brand_offset = brand.offset().top;
	if(jQuery(window).scrollTop() <= brand_offset)
		jQuery.scrollTo(brand_offset);
	
	
	var post = location.href.split(/#/);
	if (post[1]) {
		if (post[1].match(/^q\d+(_\d+)?$/)) {
			post[1] = post[1].replace('q', '').replace('_', ',');
			jQuery("#reply_chennodiscursus").append(">>" + post[1] + "\n");
			post[1] = post[1].replace(',', '_')
		}
		replyHighlight(post[1]);
	}
	

	if (typeof thread_id !== "undefined")
	{
		jQuery('.js_hook_realtimethread').html('This thread is being displayed in real time. <a class="btnr" href="#" onClick="realtimethread(); return false;">Update now</a>');
		setTimeout(realtimethread, 10000);
	}

	bindFunctions();
	jQuery("article time").localize('ddd mmm dd HH:MM:ss yyyy');
	jQuery('[rel=tooltip]').tooltip({
		placement: 'bottom', 
		delay: 200
	});
	jQuery('[rel=tooltip_right]').tooltip({
		placement: 'right', 
		delay: 200
	});
});