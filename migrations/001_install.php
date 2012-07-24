<?php

namespace Foolfuuka\Migrations;

class Install
{

    function up()
    {
		\DBUtil::create_table('banned_md5', array(
            'md5' => array('type' => 'varchar', 'constraint' => 24),
		), array('md5'), true, 'innodb', 'utf8_unicode_ci');

		\DBUtil::create_table('banned_posters', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'autoincrement' => true),
			'banned_ip' => array('type' => 'decimal', 'constraint' => '39,0'),
			'banned' => array('type' => 'smallint', 'constraint' => 2, 'unsigned' => true),
			'banned_reason' => array('type' => 'text'),
			'banned_start' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0),
			'banned_length' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0),
			'board_ids' => array('type' => 'text', 'default' => null, 'null' => true)
		), array('id'), true, 'innodb', 'utf8_unicode_ci');

		\DBUtil::create_index('banned_posters', 'banned_ip', 'banned_ip_index', 'unique');

		\DBUtil::create_table('boards', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'autoincrement' => true),
			'shortname' => array('type' => 'varchar', 'constraint' => 32),
			'name' => array('type' => 'varchar', 'constraint' => 256),
			'archive' => array('type' => 'smallint', 'constraint' => 2, 'unsigned' => true, 'default' => 0),
			'sphinx' => array('type' => 'smallint', 'constraint' => 2, 'unsigned' => true, 'default' => 0),
			'hidden' => array('type' => 'smallint', 'constraint' => 2, 'unsigned' => true, 'default' => 0),
			'hide_thumbnails' => array('type' => 'smallint', 'constraint' => 2, 'unsigned' => true, 'default' => 0),
			'directory' => array('type' => 'text'),
			'max_indexed_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0),
			'max_ancient_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0)
		), array('name'), true, 'innodb', 'utf8mb4_unicode_ci');

		\DBUtil::create_index('boards', 'shortname', 'shortname_index', 'unique');
		\DBUtil::create_index('boards', 'hide_thumbnails', 'hide_thumbnails_index');

		\DBUtil::create_table('boards_preferences', array(
			'board_preference_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'autoincrement' => true),
			'board_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true),
			'name' => array('type' => 'varchar', 'constraint' => 64),
			'value' => array('type' => 'text', 'null' => true),
		), array('board_preference_id'), true, 'innodb', 'utf8mb4_unicode_ci');

		\DBUtil::create_index('boards', array('board_id', 'name'), 'board_id_name_index');

		\DBUtil::create_table('reports', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true, 'autoincrement' => true),
			'board_id' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true),
			'doc_id' => array('type' => 'varchar', 'constraint' => 64),
			'reason' => array('type' => 'text'),
			'ip_reporter' => array('type' => 'decimal', 'constraint' => '39,0'),
			'status' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true),
			'created' => array('type' => 'int', 'constraint' => 11, 'unsigned' => true)
		), array('id'), true, 'innodb', 'utf8mb4_unicode_ci');

		\DBUtil::create_index('reports', array('board_id', 'doc_id'), 'board_id_doc_id_index');
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