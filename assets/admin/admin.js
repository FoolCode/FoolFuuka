jQuery(document).ready(function() {
	
	jQuery('[rel=popover-right]').popover();
	
	jQuery('body').on('change', '[data-function]', function(event){
		var el = jQuery(this);
		switch(el.data('function'))
		{
			case 'hasSubForm':
				if(el.is('input[type=checkbox]'))
				{
					if(el.is(':checked'))
					{
						jQuery('[data-form-parent=' + el.attr('name') + ']').show();
						jQuery('[data-form-parent=' + el.attr('name') + '_inverse]').hide();
					}
					else
					{
						jQuery('[data-form-parent=' + el.attr('name') + ']').hide();
						jQuery('[data-form-parent=' + el.attr('name') + '_inverse]').show();
					}
				}
				break;
		}
	});
	
});