<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Partial;

class Markdown extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        $content = $this->getParamManager()->getParam('content');
        ?>

        <style type="text/css">
            .markdown {
                margin:30px auto;
                max-width:900px;
                background: #FFF;
                color: #444;
                padding: 10px 40px;
                border: 2px solid #6A836F;
                font-family: "Helvetica Neue", "Helvetica", "Arial", sans-serif;
                font-size:14px;
            }
            .markdown h1 { margin: 18px 0 15px; padding: 10px 0; border-bottom: 2px solid #888; }
            .markdown h2 { margin: 18px 0 8px; padding: 3px; border-bottom: 1px solid #AAA; }
            .markdown h3 { margin: 12px 0; }
            .markdown h4 { margin: 8px 0; }
            .markdown p { margin-bottom: 12px; font-size:1.1em; line-height:150%; }
            .markdown li { margin-bottom: 6px; line-height:150%; }
            .markdown code { font-weight: normal; color: #555; }
            .markdown pre { font-weight: normal; color: #555; max-width: 100%; word-wrap: break-word; }
            .markdown pre code { font-weight: normal; color: #555; width: 100%; }
            .markdown a { color: #0480be !important }
        </style>

        <div class="markdown">
            <?= \Foolz\FoolFrame\Model\Markdown::parse($content); ?>
        </div>
            <?php
    }
}
