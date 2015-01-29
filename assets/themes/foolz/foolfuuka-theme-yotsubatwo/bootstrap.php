<?php

require_once __DIR__ . '/controller.php';

\Foolz\Plugin\Event::forge('Fuel\Core\Router::parse_match.intercept')
    ->setCall(function($result) {
        if ($result->getParam('controller') === 'Foolz\FoolFuuka\Controller\Chan') {
            // reroute everything that goes to Chan through the custom Chan controller
            $result->setParam('controller', 'Foolz\FoolFuuka\Themes\Yotsubatwo\Controller\Chan');
            $result->set(true);
        }
    });
