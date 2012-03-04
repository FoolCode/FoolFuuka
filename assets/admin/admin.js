function slideDown(item) {
	jQuery(item).slideDown();
}
function slideUp(item) {
	jQuery(item).slideUp();
}
function slideToggle(item) {
	jQuery(item).slideToggle();
}
			
function confirmPlug(href, text, item, func)
{
	if (text != "") {
		var modalContainer = jQuery("#modal-container");
		modalContainer.modal({
			show: true, 
			closeOnEscape: true, 
			backdrop: 'static', 
			keyboard: true
		});
		modalContainer.find("#modal-text-desc").html(text);
		modalContainer.find("#modal-btn-no").click(function() {
			modalContainer.modal('hide');
			return false;
		});
		modalContainer.find("#modal-btn-yes").attr('href', href).click(function(e) {
			if(func instanceof Function)
			{
				e.preventDefault();
				modalContainer.modal('hide');
				func();
				return false;
			}
						
			modalContainer.find("#modal-loading").show();
			jQuery.post(href, function(result) {
				modalContainer.find("#modal-loading").hide();
				if (location.href == result.href) window.location.reload(true);
				location.href = result.href;
			}, 'json');
			return false;
		}).focus();
	}
	else {
		jQuery.post(href, function(result) {
			if (location.href == result.href) window.location.reload(true);
			location.href = result.href;
		}, 'json');
	}
}
			
function addField(e)
{
	if (jQuery(e).val().length > 0)
	{
		jQuery(e).clone().val('').insertAfter(e);
		jQuery(e).after('<br/>');
		jQuery(e).attr('onKeyUp', '');
		jQuery(e).attr('onChange', '');
	}
}
			
jQuery(document).ready(function() {
	jQuery(":input:first").focus();
				
	jQuery("a[rel=twipsy]").twipsy({
		live: true
	});
	jQuery("a[rel^='popover']").each(function() {
		var direction = $(this).attr('rel').replace("popover-", "");
		$(this).popover({
			offset: 10, 
			placement: direction, 
			html: true
		});
	});
});