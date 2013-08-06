<?php

namespace Foolz\Foolfuuka\Theme\Foolfuuka\Partial;

class Plugin extends \Foolz\Foolfuuka\View\View
{
    public function toString()
    {
        echo $this->getParamManager()->getParam('content');
    }
}
