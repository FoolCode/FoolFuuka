<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div id="post_tools_report" class="modal hide fade">
	<div class="modal-header">
		<a href="#" class="close">&times;</a>
		<h3>Submit Report</h3>
	</div>
	<div class="modal-body" style="text-align: center">
		<div class="modal-error"></div>
		<div class="modal-loading loading"><img src="<?php echo site_url() ?>assets/js/images/loader-18.gif"/></div>
		<span style="font-weight: bold; float: left; margin-left: 10px">Post ID</span>
		<input type="text" id="report_post" style="width: 500px; max-width: 500px" readonly />
		<input type="hidden" id="report_postid" />
		<span style="font-weight: bold; float: left; margin-left: 10px; padding-top: 10px;">Comment</span>
		<textarea id="report_comment" style="width: 500px; max-width: 500px; min-height: 100px;"></textarea>
	</div>
	<div class="modal-footer">
		<a href="#" class="btn secondary closeModal modal-cancel">Cancel</a>
		<a href="<?php echo site_url($this->fu_board . '/report/') ?>" class="btn primary modal-submit">Report</a>
	</div>
</div>

<div id="post_tools_delete" class="modal hide fade">
	<div class="modal-header">
		<a href="#" class="close">&times;</a>
		<h3>Delete - Post No. <span id="delete_post">0</span></h3>
	</div>
	<div class="modal-body" style="text-align: center">
		<div class="modal-error"></div>
		<div class="modal-loading loading"><img src="<?php echo site_url() ?>assets/js/images/loader-18.gif"/></div>
		<span style="font-weight: bold; float: left; margin-left: 10px">Password</span>
		<input type="password" id="delete_passwd" style="width: 500px; max-width: 500px" />
		<input type="hidden" id="delete_postid" />
	</div>
	<div class="modal-footer">
		<a href="#" class="btn secondary closeModal modal-cancel">Cancel</a>
		<a href="<?php echo site_url($this->fu_board . '/delete/') ?>" class="btn primary modal-submit">Delete</a>
	</div>
</div>