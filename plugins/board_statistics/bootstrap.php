<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Autoloader::add_classes(array(
	'Foolfuuka\\Plugins\\Board_Statistics\\Board_Statistics' => __DIR__.'/classes/model/board_statistics.php',
	'Foolfuuka\\Plugins\\Board_Statistics\\Controller_Plugin_Fu_Board_Statistics_Admin_Board_Statistics'
		=> __DIR__.'/classes/controller/admin.php',
	'Foolfuuka\\Plugins\\Board_Statistics\\Controller_Plugin_Fu_Board_Statistics_Chan'
		=> __DIR__.'/classes/controller/chan.php',
	'Foolfuuka\\Plugins\\Board_Statistics\\Task'
		=> __DIR__.'/classes/tasks/task.php'
));

// don't add the admin panels if the user is not an admin
if (\Auth::has_access('maccess.admin'))
{
	\Router::add('admin/plugins/board_statistics', 'plugin/fu/board_statistics/admin/board_statistics/manage');

	\Plugins::register_sidebar_element('admin', 'plugins', array(
		"content" => array("board_statistics" => array("level" => "admin", "name" => __("Board Statistics"), "icon" => 'icon-bar-chart'))
	));

	\Foolz\Plugin\Event::forge('ff.task.fool.run.sections.alter')
		->setCall(function($result){
			$array = $result->getParam('nav');
			$array[] = 'board_statistics';
			$result->set($array);
			$result->setParam('nav', $array);
		});

	\Foolz\Plugin\Event::forge('ff.task.fool.run.sections.call_help.board_statistics')
		->setCall('Foolfuuka\\Plugins\\Board_Statistics\\Task::cli_board_statistics_help');

	\Foolz\Plugin\Event::forge('ff.task.fool.run.sections.call.board_statistics')
		->setCall('Foolfuuka\\Plugins\\Board_Statistics\\Task::cli_board_statistics');
}

\Foolz\Plugin\Event::forge('ff.themes.generic_top_nav_buttons')
	->setCall(function($result){
		$top_nav = $result->getParam('nav');
		if(\Radix::get_selected())
		{
			$top_nav[] = array('href' => Uri::create(array(Radix::get_selected()->shortname, 'statistics')), 'text' => __('Stats'));
			$result->set($top_nav);
			$result->setParam('nav', $top_nav);
		}
	})->setPriority(3);

\Router::add('(?!(admin|_))(\w+)/statistics', 'plugin/fu/board_statistics/chan/$2/statistics', true);
\Router::add('(?!(admin|_))(\w+)/statistics/(:any)', 'plugin/fu/board_statistics/chan/$2/statistics/$3', true);