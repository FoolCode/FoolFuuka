<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Partial;

class Message extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        $level = $this->getParamManager()->getParam('level');
        $message = $this->getParamManager()->getParam('message');

        ?>
        <div class="alert alert-<?= $level ?>" style="margin:15%;">
            <h4 class="alert-heading"><?= _i('Message') ?></h4>
            <?= $message ?>
        </div>
        <?php
    }
}
