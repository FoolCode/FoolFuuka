<?php

namespace Foolz\Foolfuuka\Theme\Fuuka\Layout;

class Redirect extends \Foolz\Foolfuuka\View\View
{
    public function toString()
    {
        $url = $this->getParamManager()->getParam('url');
        $fast_redirect = $this->getParamManager()->getParam('fast_redirect', false);

        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="generator" content="<?= $this->getConfig()->get('foolz/foolfuuka', 'package', 'main.name').' '.$this->getConfig()->get('foolz/foolfuuka', 'package', 'main.version') ?>"/>

    <title><?= htmlspecialchars($this->getBuilder()->getProps()->getTitle()); ?></title>
    <style type="text/css">
        .outer { text-align: center }
        .inner { margin: auto; display: table; display: inline-block; text-decoration: none; text-align: left; padding: 1em; border: thin dotted }
        .text { font-family: Mono, 'MS PGothic' !important }
        h1 { font-family: Georgia, serif; margin: 0 0 0.4em 0; font-size: 4em; text-align: center }
        p { margin-top: 2em; text-align: center; font-size: small }
        a { color: #34345C }
        a:visited { color: #34345C }
        a:hover { color: #DD0000 }
    </style>

    <meta http-equiv="Refresh" content="<?= ($fast_redirect) ? 0 : 2; ?>; url=<?= $url ?>" />
</head>
<body>
<?php if (!$fast_redirect) : ?>
    <h1><?= htmlspecialchars($this->getBuilder()->getProps()->getTitle()); ?></h1>
    <div class="outer">
        <div class="inner">
            <span class="text"><?= nl2br(fuuka_message()) ?></span>
        </div>
    </div>
    <p><a href="<?= $url ?>" rel="noreferrer"><?= $url ?></a><br/>All characters <acronym title="DO NOT STEAL MY ART">&#169;</acronym> Darkpa's party</p>
<?php else: ?>
    <script type="text/javascript">
        window.location.href = '<?= $url ?>';
    </script>
<?php endif; ?>
</body>
</html>
<?php
    }

}
