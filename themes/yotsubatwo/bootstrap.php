<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Autoloader::add_classes(array(
	'Foolfuuka\\Themes\\Yotsubatwo\\Controller_Theme_Fu_Yotsubatwo_Chan' => __DIR__.'/controller.php'
));


\Router::add('(?!(admin|api|content|assets|search))(\w+)', 'theme/fu/yotsubatwo/chan/$2/page');
\Router::add('(?!(admin|api|content|assets|search))(\w+)/(:any)', 'theme/fu/yotsubatwo/chan/$2/$3');