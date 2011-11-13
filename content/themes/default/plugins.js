jQuery(document).ready(function(){
	jQuery("a[rel=twipsy]").twipsy({
		live: true
	});
	jQuery("[rel^='popover']").each(function() {
		var direction = $(this).attr('rel').replace("popover-", "");
		jQuery(this).popover({
			offset: 10, 
			placement: direction, 
			html: true
		});
	});
});

function getSearch(searchForm)
{
	
}

function getPost(postForm)
{
	if (postForm.post.value == "") {
		alert('Sorry, you must insert a valid post number.');
		return false;
	}
	window.location = postForm.action + postForm.post.value + '/';
}

function getPage(pageForm)
{
	if (pageForm.page.value == "") {
		alert('Sorry, you must insert a valid page number.');
		return false;
	}
	window.location = pageForm.action + pageForm.page.value + '/';
}