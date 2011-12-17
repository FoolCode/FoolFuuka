
jQuery(document).ready(function() {
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
				
			case 'delete':
				modal.find(".title").html('Delete - Post No. ' + el.data("post-id"));
				modal.find(".modal-loading").hide();
				modal.find(".modal-information").html('\
					<span class="modal-label">Password</span>\n\
					<input type="password" class="modal-password" />\n\
					<input type="hidden" class="modal-post-id" value="' + post + '" />');
				modal.find(".submitModal").data("action", 'delete');
				break;
			
			case 'closeModal':
				el.closest(".modal").modal('hide');
				return false;
				break;
				
			case 'submitModal':
				var loading = modal.find(".modal-loading");
				var action = $(this).data("action");
				var _post = modal.find(".modal-post-id").val();
				var _href = action + '/' + _post + '/';
				
				if (action == 'report') {
					var _data = { post: _post, reason: modal.find(".modal-comment").val() };
				}
				else if (action == 'delete') {
					var _data = { post: _post, password: modal.find(".modal-password").val() };
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
						jQuery('.doc_id_' + post).remove();
					}
					return false;
				}, 'json');
				break;
			
			default:
				break;
		}
	});
	
	jQuery("[rel=popover]").popover({offset: 10, html: true});
	jQuery("time").localize('ddd mmm dd HH:MM:ss yyyy');
	
	post = location.href.split(/#/);
	if (post[1]) {
		if (post[1].match(/^q\d+(_\d+)?$/)) {
			post[1] = post[1].replace('q', '').replace('_', ',');
			jQuery("#reply_comment").append(">>" + post[1] + "\n");
			post[1] = post[1].replace(',', '_')
		}
		replyHighlight(post[1]);
	}
	
	if (thread_id != undefined)
	{
		jQuery('.js_hook_realtimethread').html('This thread is being displayed in real time. <a class="btn success" href="#" onClick="realtimethread(); return false;">Update now</a>');
		realtimethread();
	}
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
					jQuery('article.thread aside').append(value.formatted);
				});
				thread_latest_timestamp = data[thread_id].posts[data[thread_id].posts.length-1].timestamp;
				timelapse = 10;
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
