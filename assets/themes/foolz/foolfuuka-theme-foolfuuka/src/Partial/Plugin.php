<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Partial;

class Plugin extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        echo $this->getParamManager()->getParam('content');
    }
}
