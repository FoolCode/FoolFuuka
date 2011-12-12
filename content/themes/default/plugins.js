
jQuery(document).ready(function(){
	// Bind on REL
	jQuery("[data-rel=popover]").popover({
		offset: 10, 
		html: true
	});
	
	jQuery("a[data-rel=highlight]").click(function() {
		var post = jQuery(this).attr("data-id");
		if (post) replyHighlight(post);
	})
	
	jQuery("a[data-rel=quote]").click(function() {
		var post = jQuery(this).attr("data-id");
		jQuery("#reply_comment").append(">>" + post + "\n");
	})
	
	jQuery("a[data-rel=report]").click(function() {
		var post = jQuery(this).attr("data-id");
		var modalReport = jQuery("#post_tools_report");
		modalReport.find("#modal-loading").hide();
		modalReport.find("#report_post").val(jQuery(this).attr("data-alt"));
		modalReport.find("#report_postid").val(post);
	});
	
	jQuery("a[data-rel=delete]").click(function() {
		var post = jQuery(this).attr("data-id");
		var modalDelete = jQuery("#post_tools_delete");
		modalDelete.find("#modal-loading").hide();
		modalDelete.find("#delete_post").html(jQuery(this).attr("data-alt"));
		modalDelete.find("#delete_postid").val(post);
	});
	
	jQuery("a.closeModal").click(function() {
		jQuery(this).closest(".modal").modal("hide");
	});
	
	jQuery("time").localize('ddd mmm dd HH:MM:ss yyyy');
	
	var modalReport = jQuery("#post_tools_report");
	modalReport.find(".modal-submit").click(function() {
		var loading = modalReport.find(".modal-loading");
		var post = modalReport.find("#report_postid").val();
		var href = this.href + post + '/';
		loading.show();
		jQuery.post(href, {
			post: post, 
			reason: modalReport.find("#report_comment").val()
		}, function(result) {
			loading.hide();
			if (result.status == 'failed')
			{
				modalReport.find(".modal-error").html('<div class="alert-message error fade in" data-alert="alert"><a class="close" href="#">&times;</a><p>' + result.reason + '</p></div>');
				return false;
			}
			toggleHighlight(modalReport.find("#report_post").val().replace(',', '_'), 'reported', false);
			modalReport.modal('hide');
		}, 'json');
		return false;
	});
	
	var modalDelete = jQuery("#post_tools_delete");
	modalDelete.find(".modal-submit").click(function() {
		var loading = modalDelete.find(".modal-loading");
		var post = modalDelete.find("#delete_postid").val();
		var href = this.href + post + '/';
		loading.show();
		jQuery.post(href, {
			post: post, 
			password: modalDelete.find("#delete_passwd").val()
		}, function(result) {
			loading.hide();
			if (result.status == 'failed')
			{
				modalDelete.find(".modal-error").html('<div class="alert-message error fade in" data-alert="alert"><a class="close" href="#">&times;</a><p>' + result.reason + '</p></div>');
				return false;
			}
			modalDelete.modal('hide');
			jQuery('.doc_id_' + post).remove();
		}, 'json');
		return false;
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
	
	if(thread_id != undefined)
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
		data: {
			num : thread_id,
			board: board_shortname,
			timestamp: thread_latest_timestamp
		},
		success: function(data){
			if(data.posts != undefined) {
				jQuery.each(data[thread_id].posts, function(idx, value){
					jQuery('article.thread aside').append(value.formatted);
				});
				thread_latest_timestamp = 	data.posts[data.posts.length-1].timestamp;
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
