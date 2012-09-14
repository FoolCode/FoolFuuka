<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Autoloader::add_classes(array(
	'Foolfuuka\\Plugins\\Image_In_Html\\Controller_Plugin_Fu_Image_In_Html_Chan' 
		=> __DIR__.'/classes/controller/chan.php'
));

\Router::add('(?!(admin|_))(\w+)/image_html/(:any)', 'plugin/fu/image_in_html/chan/$2/image_html/$3', true);


\Plugins::register_hook('foolfuuka\\model\\media.get_link.call.replace', function($element, $thumbnail = false, $direct = false) {

	if ($direct === true || $thumbnail === true || ! is_object($element))
	{
		return array('return' => null);
	}
	
	$element->p_get_link($thumbnail);
	
	return array('return' => \Uri::create(array($element->board->shortname, 'image_html')).$element->media);
}, 4);
