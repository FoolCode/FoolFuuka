<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-image-in-html')
    ->setCall(function($result) {
        \Autoloader::add_classes([
            'Foolfuuka\\Plugins\\Image_In_Html\\ControllerPluginFuImageInHtmlChan'
                => __DIR__.'/classes/controller/chan.php'
        ]);

        \Router::add('(?!(admin|_))(\w+)/image_html/(:any)', 'plugin/fu/image_in_html/chan/$2/image_html/$3', true);

        \Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Media::getLink.call.before.body')
            ->setCall(function($result) {

                $element = $result->getObject();
                list($thumbnail, $direct) = $result->getParam();

                if ($direct === true || $thumbnail === true) {
                    return;
                }

                // this function must NOT run for the radix_full_image function
                if (\Radix::getByShortname(\Uri::segment(1)) && \Uri::segment(2) === 'full_image') {
                    return;
                }

                $return = $element->p_getLink($thumbnail);
                if ($return === null) {
                    return;
                }

                $result->set(\Uri::create([$element->board->shortname, 'image_html']).$element->media);
            })->setPriority(4);
    });
