<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');


\Autoloader::add_classes(array(
	'Foolfuuka\\Theme\\Controller_Plugin_Chan' => __DIR__.'/controller.php'
));

\Router::add('chan/page/(:any)', 'theme/fu/chan/page/$1', true);