<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Partial;

class ToolsModal extends \Foolz\FoolFuuka\View\View
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
                <div class="modal-loading loading"><img src="<?= $this->getAssetManager()->getAssetLink('images/loader-18.gif') ?>"/></div>
                <div class="modal-information"></div>
            </div>
            <div class="modal-footer">
                <input type="button" value="<?= htmlspecialchars(_i('Submit')) ?>" href="#" class="btn btn-primary submitModal" data-function="submitModal" data-report="0" data-delete="0">
                <input type="button" value="<?= htmlspecialchars(_i('Cancel')) ?>" href="#" class="btn secondary closeModal" data-function="closeModal">
            </div>
        </div>
        <?php
    }

}
