<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

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
		<a href="#" class="btn primary submitModal" data-function="submitModal" data-report="<?php echo site_url($this->fu_board . '/report/') ?>" data-delete="<?php echo site_url($this->fu_board . '/delete/') ?>">Submit</a>
	</div>
</div>