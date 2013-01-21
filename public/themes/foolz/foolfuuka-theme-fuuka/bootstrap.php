<?php

if (! defined('DOCROOT'))
	exit('No direct script access allowed');

require __DIR__ . '/functions.php';

\Autoloader::add_classes([
	'Foolz\Foolfuuka\Themes\Fuuka\Controller\Chan' => __DIR__.'/classes/controller.php',
	'Foolz\Foolfuuka\Themes\Fuuka\Model\Fuuka' => __DIR__.'/classes/model.php'
]);

\Foolz\Plugin\Event::forge('Fuel\Core\Router.parse_match.intercept')
	->setCall(function($result)
	{
		if ($result->getParam('controller') === 'Foolz\Foolfuuka\Controller\Chan')
		{
			// reroute everything that goes to Chan through the custom Chan controller
			$result->setParam('controller', 'Foolz\Foolfuuka\Themes\Fuuka\Controller\Chan');
			$result->set(true);
		}
	});

// use hooks for manipulating comments
\Foolz\Plugin\Event::forge('fu.comment_model.processComment.greentext_result')
	->setCall('\Foolz\Foolfuuka\Themes\Fuuka\Model\Fuuka::greentext')
	->setPriority(8);

\Foolz\Plugin\Event::forge('fu.comment_model.processInternalLinks.html_result')
	->setCall('\Foolz\Foolfuuka\Themes\Fuuka\Model\Fuuka::processInternalLinksHtml')
	->setPriority(8);

\Foolz\Plugin\Event::forge('fu.comment_model.processExternalLinks.html_result')
	->setCall('\Foolz\Foolfuuka\Themes\Fuuka\Model\Fuuka::processExternalLinksHtml')
	->setPriority(8);