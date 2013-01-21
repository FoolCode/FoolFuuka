<?php

namespace Foolz\Foolfuuka\Theme\Foolfuuka\Partial;

class ToolsModal extends \Foolz\Theme\View
{
	public function toString()
	{
		?>
		<div id="post_tools_modal" class="modal hide fade">
			<div class="modal-header">
				<a href="#" class="close">&times;</a>
				<h3 class="title"></h3>
			</div>
			<div class="modal-body" style="text-align: center">
				<div class="modal-error"></div>
				<div class="modal-loading loading"><img src="<?= \Uri::base() ?>assets/js/images/loader-18.gif"/></div>
				<div class="modal-information"></div>
			</div>
			<div class="modal-footer">
				<input type="button" value="<?= htmlspecialchars(__('Submit')) ?>" href="#" class="btn btn-primary submitModal" data-function="submitModal" data-report="" data-delete="">
				<input type="button" value="<?= htmlspecialchars(__('Cancel')) ?>" href="#" class="btn secondary closeModal" data-function="closeModal">
			</div>
		</div>
		<?php
	}

}


