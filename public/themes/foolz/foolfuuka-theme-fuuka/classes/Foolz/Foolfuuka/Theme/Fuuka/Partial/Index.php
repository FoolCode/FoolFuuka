<?php

namespace Foolz\Foolfuuka\Theme\Fuuka\Partial;

class Index extends \Foolz\Foolfuuka\View\View
{
    public function toString()
    {
        ?>
        <div id="content">
            <h1><?= $this->getPreferences()->get('foolframe.gen.website_title'); ?></h1>
            <h2><?= _i('Choose a Board:'); ?></h2>
            <p>
                <?php
                $board_urls = array();
                foreach ($this->getRadixColl()->getAll() as $key => $item) {
                    array_push($board_urls, '<a href="' . $this->getUri()->create($item->shortname) . '" title="' . $item->name . '">/' . $item->shortname . '/</a>');
                }
                echo implode(' ', $board_urls);
                ?>
            </p>
        </div>
    <?php
    }
}
