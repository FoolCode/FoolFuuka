<?php

namespace Foolz\Foolfuuka\Themes\Yotsubatwo\Controller;

class Chan extends \Foolz\Foolfuuka\Controller\Chan
{
    public function radix_page($page = 1)
    {
        $order = $this->getCookie('default_theme_page_mode_'. ($this->radix->archive ? 'archive' : 'board')) === 'by_thread'
            ? 'by_thread' : 'by_post';

        $options = [
            'per_page' => 24,
            'per_thread' => 5,
            'order' => $order
        ];

        return $this->latest($page, $options);
    }
}
