<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Autoloader::add_classes(array(
	'Foolfuuka\\Themes\\Default_\\Views\\Board_comment' => __DIR__.'/../default/views/board_comment.php',
	'Foolfuuka\\Themes\\Yotsubatwo\\Controller_Theme_Fu_Yotsubatwo_Chan' => __DIR__.'/controller.php'
));


\Router::add('(?!(admin|api|_))(\w+)', 'theme/fu/yotsubatwo/chan/$2/page');
\Router::add('(?!(admin|api|_))(\w+)/(:any)', 'theme/fu/yotsubatwo/chan/$2/$3');