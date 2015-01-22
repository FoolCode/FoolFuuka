<?php

namespace Foolz\Foolfuuka\Theme\Foolfuuka\Partial;

class Message extends \Foolz\Foolfuuka\View\View
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
