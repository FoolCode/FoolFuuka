<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Layout;

class Redirect extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        header('X-UA-Compatible: IE=edge,chrome=1');
        header('imagetoolbar: false');
        $url = $this->getParamManager()->getParam('url');

        ?><!DOCTYPE html>
<html>
    <head>
        <title><?= htmlspecialchars($this->getBuilder()->getProps()->getTitle()); ?></title>
        <meta http-equiv="Refresh" content="0; url=<?= $url ?>">
    </head>
    <body>
        <?= _i('You are being redirected to %s.', $url) ?>
    </body>
</html><?php
    }
}
