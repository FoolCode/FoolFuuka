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

	\Plugins::register_hook('foolframe.task.fool.run.sections.alter', function($array){
		$array[] = 'board_statistics';
		return array('return' => $array);
	}, 5);
	
	\Plugins::register_hook('foolframe.task.fool.run.sections.call_help.board_statistics', 
		'Foolfuuka\\Plugins\\Board_Statistics\\Task::cli_board_statistics_help', 5);
	
	\Plugins::register_hook('foolframe.task.fool.run.sections.call.board_statistics', 
		'Foolfuuka\\Plugins\\Board_Statistics\\Task::cli_board_statistics', 5);

}

\Plugins::register_hook('ff.themes.generic_top_nav_buttons', function($top_nav){
	if(\Radix::get_selected())
		$top_nav[] = array('href' => Uri::create(array(Radix::get_selected()->shortname, 'statistics')), 'text' => __('Stats'));
		return array('return' => $top_nav);
}, 3);

\Router::add('(?!(admin|_))(\w+)/statistics', 'plugin/fu/board_statistics/chan/$2/statistics', true);
\Router::add('(?!(admin|_))(\w+)/statistics/(:any)', 'plugin/fu/board_statistics/chan/$2/statistics/$3', true);