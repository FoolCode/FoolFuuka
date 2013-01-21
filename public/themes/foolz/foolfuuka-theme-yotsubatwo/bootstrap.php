<?php

if (! defined('DOCROOT'))
	exit('No direct script access allowed');

\Autoloader::add_classes([
	'Foolz\Foolfuuka\Themes\Yotsubatwo\Controller\Chan' => __DIR__.'/controller.php'
]);

\Foolz\Plugin\Event::forge('Fuel\Core\Router.parse_match.intercept')
	->setCall(function($result)
	{
		if ($result->getParam('controller') === 'Foolz\Foolfuuka\Controller\Chan')
		{
			// reroute everything that goes to Chan through the custom Chan controller
			$result->setParam('controller', 'Foolz\Foolfuuka\Themes\Yotsubatwo\Controller\Chan');
			$result->set(true);
		}
	});