<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Autoloader::add_classes(array(
	'Foolfuuka\\Plugins\\Board_Statistics\\Board_Statistics' => __DIR__.'/classes/model/board_statistics.php',
	'Foolfuuka\\Plugins\\Board_Statistics\\Controller_Plugin_Fu_Board_Statistics_Admin_Board_Statistics' 
		=> __DIR__.'/classes/controller/admin.php',
	'Foolfuuka\\Plugins\\Board_Statistics\\Controller_Plugin_Fu_Board_Statistics_Chan' 
		=> __DIR__.'/classes/controller/chan.php'
));

// don't add the admin panels if the user is not an admin
if (\Auth::has_access('maccess.admin'))
{
	\Router::add('admin/plugins/board_statistics', 'plugin/fu/board_statistics/admin/board_statistics/manage');

	\Plugins::register_sidebar_element('admin', 'plugins', array(
		"content" => array("board_statistics" => array("level" => "admin", "name" => __("Board Statistics"), "icon" => 'icon-bar-chart'))
	));
}

\Plugins::register_hook('ff.themes.generic_top_nav_buttons', function($top_nav){
	if(\Radix::get_selected())
		$top_nav[] = array('href' => Uri::create(array(Radix::get_selected()->shortname, 'statistics')), 'text' => __('Stats'));
		return array('return' => $top_nav);
}, 3);

/*
if ($this->auth->is_admin())
{

	Plugins::register_controller_function($this,
		array('cli', 'board_stats', 'help'), 'cli_help');

	Plugins::register_controller_function($this,
		array('cli', 'board_stats', 'cron'), 'cli_cron');

	Plugins::register_controller_function($this,
		array('cli', 'board_stats', 'cron', '(:any)'), 'cli_cron');
}

Plugins::register_controller_function($this,
	array('chan', '(:any)', 'statistics'), 'chan_statistics');

Plugins::register_controller_function($this,
	array('chan', '(:any)', 'statistics', '(:any)'), 'chan_statistics');

Plugins::register_hook($this, 'fu_cli_controller_after_help', 5, function(){ 
	cli_notice('notice', '    board_stats [help]   Run processes relative to the creation of statistics');
	return NULL;
});
*/