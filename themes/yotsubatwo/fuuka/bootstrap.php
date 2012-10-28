<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Fuel::load(__DIR__.'/functions.php');

\Autoloader::add_classes(array(
	'Foolfuuka\\Themes\\Fuuka\\Controller_Theme_Fu_Fuuka_Chan' => __DIR__.'/classes/controller.php',
	'Foolfuuka\\Themes\\Fuuka\\Theme_Fu_Fuuka' => __DIR__.'/classes/model.php'
));

\Router::add('(?!(admin|_))(\w+)', 'theme/fu/fuuka/chan/$2/page/1', true);
\Router::add('(?!(admin|_))(\w+)/(:any)', 'theme/fu/fuuka/chan/$2/$3', true);

// use hooks for manipulating comments
\Foolz\Plugin\Event::forge('fu.comment_model.processComment.greentext_result')
	->setCall('\\Foolfuuka\\Themes\\Fuuka\\Theme_Fu_Fuuka::greentext')
	->setPriority(8);

\Foolz\Plugin\Event::forge('fu.comment_model.processInternalLinks.html_result')
	->setCall('\\Foolfuuka\\Themes\\Fuuka\\Theme_Fu_Fuuka::processInternalLinksHtml')
	->setPriority(8);

\Foolz\Plugin\Event::forge('fu.comment_model.processExternalLinks.html_result')
	->setCall('\\Foolfuuka\\Themes\\Fuuka\\Theme_Fu_Fuuka::processExternalLinksHtml')
	->setPriority(8);