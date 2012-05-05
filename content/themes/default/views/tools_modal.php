<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if ($enabled_tools_modal) : ?>
<div id="post_tools_modal" class="modal hide fade">
	<div class="modal-header">
		<a href="#" class="close">&times;</a>
		<h3 class="title"></h3>
	</div>
	<div class="modal-body" style="text-align: center">
		<div class="modal-error"></div>
		<div class="modal-loading loading"><img src="<?php echo site_url() ?>assets/js/images/loader-18.gif"/></div>
		<div class="modal-information"></div>
	</div>
	<div class="modal-footer">
		<a href="#" class="btn secondary closeModal" data-function="closeModal">Cancel</a>
		<a href="#" class="btn btn-primary submitModal" data-function="submitModal" data-report="<?php echo site_url(get_selected_radix()->shortname . '/report/') ?>" data-delete="<?php echo site_url(get_selected_radix()->shortname . '/delete/') ?>">Submit</a>
	</div>
</div>
<?php endif; ?>
