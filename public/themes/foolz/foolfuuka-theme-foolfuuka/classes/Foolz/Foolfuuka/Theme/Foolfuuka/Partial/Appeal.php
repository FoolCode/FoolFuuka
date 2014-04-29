<?php

namespace Foolz\Foolfuuka\Theme\Foolfuuka\Partial;

class Appeal extends \Foolz\Foolfuuka\View\View
{
    public function toString()
    {
        $title = $this->getParamManager()->getParam('title');
        $form = $this->getForm();

        ?>
            <div class="alert alert-success" style="margin:15% 30%;">
                <h4 class="alert-heading"><?= e($title) ?></h4>
                <br/>
                <?= $form->open(); ?>
                <?= $form->hidden('csrf_token', $this->getSecurity()->getCsrfToken()); ?>
                <?= _i('Explain in short (max. 500 chars) why your ban should be lifted.') ?>
                <br/>
                <?= $form->textarea('appeal', null, array('style' => 'width: 100%; height: 100px; margin: 10px 0')) ?>
                <?= $form->submit(array('name' => 'submit', 'value' => _i('Submit'), 'class' => 'btn btn-inverse')) ?>
                <?= $form->close(); ?>
            </div>
        <?php
    }
}
