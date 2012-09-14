<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Autoloader::add_classes(array(
	'Foolfuuka\\Plugins\\Image_In_Html\\Controller_Plugin_Fu_Image_In_Html_Chan' 
		=> __DIR__.'/classes/controller/chan.php'
));

\Router::add('(?!(admin|_))(\w+)/image_html/(:any)', 'plugin/fu/image_in_html/chan/$2/image_html/$3', true);
