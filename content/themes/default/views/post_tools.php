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
		<div id="modal-error"></div>
		<div id="modal-loading" class="loading"><img src="<?php echo site_url() ?>assets/js/images/loader-18.gif"/></div>
		<span style="font-weight: bold; float: left; margin-left: 10px">Post ID</span>
		<input type="text" id="report_post" style="width: 500px; max-width: 500px" readonly="readonly" />
		<input type="hidden" id="report_postid" readonly="readonly" />
		<span style="font-weight: bold; float: left; margin-left: 10px; padding-top: 10px;">Comment</span>
		<textarea id="report_comment" style="width: 500px; max-width: 500px; min-height: 100px; font-family: Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace !important"></textarea>
	</div>
	<div class="modal-footer">
		<a href="#" id="modal-cancel" class="btn secondary">Cancel</a>
		<a href="<?php echo site_url($this->fu_board . '/report/') ?>" id="modal-submit" class="btn primary">Report</a>
	</div>
</div>

<div id="post_tools_delete" class="modal hide fade">
	<div class="modal-header">
		<a href="#" class="close">&times;</a>
		<h3>Delete - Post No. <span id="delete_post">0</span></h3>
	</div>
	<div class="modal-body" style="text-align: center">
		<div id="modal-error"></div>
		<div id="modal-loading" class="loading"><img src="<?php echo site_url() ?>assets/js/images/loader-18.gif"/></div>
		<span style="font-weight: bold; float: left; margin-left: 10px">Password</span>
		<input type="password" id="delete_passwd" style="width: 500px; max-width: 500px" />
		<input type="hidden" id="delete_postid" readonly="readonly" />
	</div>
	<div class="modal-footer">
		<a href="#" id="modal-cancel" class="btn secondary">Cancel</a>
		<a href="<?php echo site_url($this->fu_board . '/delete/') ?>" id="modal-submit" class="btn primary">Delete</a>
	</div>
</div>