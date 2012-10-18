<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Foolz\Plugin\Event::forge('foolz\plugin\plugin.execute.foolz/image_in_html')
	->setCall(function($result) {
		\Autoloader::add_classes(array(
			'Foolfuuka\\Plugins\\Image_In_Html\\Controller_Plugin_Fu_Image_In_Html_Chan'
				=> __DIR__.'/classes/controller/chan.php'
		));

		\Router::add('(?!(admin|_))(\w+)/image_html/(:any)', 'plugin/fu/image_in_html/chan/$2/image_html/$3', true);


		\Foolz\Plugin\Event::forge('foolfuuka\\model\\media.get_link.call.before')
			->setCall(function($result) {

				$element = $result->getObject();
				list($thumbnail, $direct) = $result->getParam();

				if ($direct === true || $thumbnail === true)
				{
					return;
				}

				// this function must NOT run for the radix_full_image function
				if (\Radix::getByShortname(\Uri::segment(1)) && \Uri::segment(2) === 'full_image')
				{
					return;
				}

				try
				{
					$element->p_get_link($thumbnail);
				}
				catch (\Foolz\Foolfuuka\Model\MediaNotFoundException $e)
				{
					return;
				}

				$result->set(\Uri::create(array($element->board->shortname, 'image_html')).$element->media);
			})->setPriority(4);
	});


