<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Fuel::load(__DIR__.'/functions.php');

\Autoloader::add_classes(array(
	'Foolfuuka\\Themes\\Fuuka\\Controller_Theme_Fu_Fuuka_Chan' => __DIR__.'/classes/controller.php',
	'Foolfuuka\\Themes\\Fuuka\\Theme_Fu_Fuuka' => __DIR__.'/classes/model.php'
));

//\Router::add('(?!(admin|api|content|assets|search))(\w+)', 'theme/fu/fuuka/chan/$2/page');
\Router::add('(?!(admin|api|content|assets|search))(\w+)/(:any)', 'theme/fu/fuuka/chan/$2/$3');

// use hooks for manipulating comments
\Plugins::register_hook('fu.comment_model.process_comment.greentext_result',
	'\\Foolfuuka\\Themes\\Fuuka\\Model_Theme_Fu_Fuuka::greentext', 8);
\Plugins::register_hook('fu.comment_model.process_internal_links.html_result',
	'\\Foolfuuka\\Themes\\Fuuka\\Model_Theme_Fu_Fuuka::process_internal_links_html', 8);
\Plugins::register_hook('fu.comment_model.process_crossboard_links.html_result',
	'\\Foolfuuka\\Themes\\Fuuka\\Model_Theme_Fu_Fuuka::process_crossboard_links_html', 8);

\Router::delete('(?!(admin|api|content|assets|search))(\w+)/gallery');
\Router::delete('(?!(admin|api|content|assets|search))(\w+)/submit');


// if we have to outright change the name of the function, we need to register a new controller function
//\Plugins::register_controller_function($this, array('chan', '(:any)', 'sending'), 'sending');