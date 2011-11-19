jQuery(document).ready(function(){
	jQuery("a[rel=twipsy]").twipsy({
		live: true
	});
	
	jQuery("[rel=popover]").popover({
		offset: 10,
		html: true
	});
	
	jQuery("a[rel=highlight]").click(function() {
		var post = jQuery(this).attr("id");
		if (post) replyHighlight(post);
	})
	
	jQuery("a[rel=quote]").click(function() {
		var post = jQuery(this).attr("id");
		jQuery("#reply_comment").append(">>" + post + "\n");
	})
	
	post = location.href.split(/#/);
	if (post[1]) {
		if (post[1].match(/^q\d+$/)) {
			post[1] = post[1].replace('q', '');
			jQuery("#reply_comment").append(">>" + post[1] + "\n");
		}
		replyHighlight(post[1]);
	}
});

function toggleSearch(mode)
{
	var search;
	if (!(search = document.getElementById('search_' + mode))) return;
	search.style.display = search.style.display ? "" : "none";
}

function getSearch(type, searchForm)
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

function getPost(postForm)
{
	if (postForm.post.value == "") {
		alert('Sorry, you must insert a valid post number.');
		return false;
	}
	window.location = postForm.action + encodeURIComponent(postForm.post.value) + '/';
}

function getPage(pageForm)
{
	if (pageForm.page.value == "") {
		alert('Sorry, you must insert a valid page number.');
		return false;
	}
	window.location = pageForm.action + encodeURIComponent(pageForm.page.value) + '/';
}

function getRadioValue(group)
{
	for (index = 0; index < group.length; index++)
	{
		if (group[index].checked == true)
			return encodeURIComponent(group[index].value);
	}
}

function replyHighlight(id)
{
	var articles = document.getElementsByTagName("article");
	jQuery("article").each(function() {
		var post = jQuery(this);
		
		if (post.hasClass("highlight"))
		{
			post.removeClass("highlight");
		}
		
		if (post.attr("id") == id)
		{
			post.addClass("highlight");
		}
	})
}