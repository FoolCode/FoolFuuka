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