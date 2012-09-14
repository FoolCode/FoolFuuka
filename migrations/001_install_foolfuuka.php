<?php

namespace Fuel\Migrations;

class Install_Foolfuuka
{

    function up()
    {
		$charset = \Config::get('db.default.charset');

		if ( ! \DBUtil::table_exists('banned_md5'))
		{
			\DBUtil::create_table('banned_md5', array(
				'md5' => array('type' => 'varchar', 'constraint' => 24),
			), array('md5'), true, 'innodb', 'utf8_general_ci');
		}

		if ( ! \DBUtil::table_exists('banned_posters'))
		{
			\DBUtil::create_table('banned_posters', array(
				'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
				'ip' => array('type' => 'decimal', 'constraint' => '39,0'),
				'reason' => array('type' => 'text'),
				'start' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0),
				'length' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0),
				'board_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0)
			), array('id'), true, 'innodb', $charset.'_general_ci');

			\DBUtil::create_index('banned_posters', 'ip', 'ip_index');
		}

		if ( ! \DBUtil::table_exists('boards'))
		{
			\DBUtil::create_table('boards', array(
				'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
				'shortname' => array('type' => 'varchar', 'constraint' => 32),
				'name' => array('type' => 'varchar', 'constraint' => 256),
				'archive' => array('type' => 'smallint', 'constraint' => 2, 'unsigned' => true, 'default' => 0),
				'sphinx' => array('type' => 'smallint', 'constraint' => 2, 'unsigned' => true, 'default' => 0),
				'hidden' => array('type' => 'smallint', 'constraint' => 2, 'unsigned' => true, 'default' => 0),
				'hide_thumbnails' => array('type' => 'smallint', 'constraint' => 2, 'unsigned' => true, 'default' => 0),
				'directory' => array('type' => 'text'),
				'max_indexed_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0),
				'max_ancient_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0)
			), array('id'), true, 'innodb', $charset.'_general_ci');

			\DBUtil::create_index('boards', 'shortname', 'shortname_index', 'unique');
			\DBUtil::create_index('boards', 'hide_thumbnails', 'hide_thumbnails_index');
		}

		if ( ! \DBUtil::table_exists('boards_preferences'))
		{
			\DBUtil::create_table('boards_preferences', array(
				'board_preference_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
				'board_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true),
				'name' => array('type' => 'varchar', 'constraint' => 64),
				'value' => array('type' => 'text', 'null' => true),
			), array('board_preference_id'), true, 'innodb', $charset.'_general_ci');

			\DBUtil::create_index('boards_preferences', array('board_id', 'name'), 'board_id_name_index');
		}

		if ( ! \DBUtil::table_exists('reports'))
		{
			\DBUtil::create_table('reports', array(
				'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
				'board_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true),
				'doc_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null),
				'media_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null),
				'reason' => array('type' => 'text'),
				'ip_reporter' => array('type' => 'decimal', 'constraint' => '39,0'),
				'created' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true)
			), array('id'), true, 'innodb', $charset.'_general_ci');

			\DBUtil::create_index('reports', array('board_id', 'doc_id'), 'board_id_doc_id_index');
			\DBUtil::create_index('reports', array('board_id', 'media_id'), 'board_id_media_id_index');
		}
    }

    function down()
    {
       \DBUtil::drop_table('banned_md5');
       \DBUtil::drop_table('banned_posters');
       \DBUtil::drop_table('boards');
       \DBUtil::drop_table('boards_preferences');
       \DBUtil::drop_table('reports');
    }
}