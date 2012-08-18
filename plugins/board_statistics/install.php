<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

$charset = \Config::get('db.default.charset');

if (!\DBUtil::table_exists('plugin_fu-board-statistics'))
{
	\DBUtil::create_table('plugin_fu-board-statistics', array(
		'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
		'board_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true),
		'name' => array('type' => 'varchar', 'constraint' => 32),
		'timestamp' => array('type' => 'timestamp', 'default' => \DB::expr('CURRENT_TIMESTAMP'), 'on_update' => \DB::expr('CURRENT_TIMESTAMP')),
		'data' => array('type' => 'longtext'),
	), array('id'), true, 'innodb', $charset.'_general_ci');

	\DBUtil::create_index('plugin_fu-board-statistics', array('board_id', 'name'), 'board_id_name_index', 'unique');
	\DBUtil::create_index('plugin_fu-board-statistics', 'timestamp', 'timestamp_index');
}