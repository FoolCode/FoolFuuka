<?php

namespace Foolz\Foolfuuka\Model;

class Schema
{
	use \Foolz\Plugin\PlugSuit;

	public static function load(\Foolz\Foolframe\Model\SchemaManager $sm)
	{
		$charset = 'utf8mb4';
		$collation = 'utf8mb4_unicode_ci';

		$schema = $sm->getCodedSchema();

		$banned_md5 = $schema->createTable(\DC::p('banned_md5'));
		$banned_md5->addColumn('md5', 'string', ['length' => 24]);
		$banned_md5->setPrimaryKey(['md5']);

		$banned_posters = $schema->createTable(\DC::p('banned_posters'));
		if (\DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$banned_posters->addOption('charset', $charset);
			$banned_posters->addOption('collate', $collation);
		}
		$banned_posters->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$banned_posters->addColumn('ip', 'decimal', ['unsigned' => true, 'precision' => 39, 'scale' => 0]);
		$banned_posters->addColumn('reason', 'text', ['length' => 65532]);
		$banned_posters->addColumn('start', 'integer', ['unsigned' => true, 'default' => 0]);
		$banned_posters->addColumn('length', 'integer', ['unsigned' => true, 'default' => 0]);
		$banned_posters->addColumn('board_id', 'integer', ['unsigned' => true, 'default' => 0]);
		$banned_posters->addColumn('creator_id', 'integer', ['unsigned' => true, 'default' => 0]);
		$banned_posters->addColumn('appeal', 'text', ['length' => 65532]);
		$banned_posters->addColumn('appeal_status', 'integer', ['unsigned' => true, 'default' => 0]);
		$banned_posters->setPrimaryKey(['id']);
		$banned_posters->addIndex(['ip'], 'ip_index');
		$banned_posters->addIndex(['creator_id'], 'creator_id_index');
		$banned_posters->addIndex(['appeal_status'], 'appeal_status_index');

		$boards = $schema->createTable(\DC::p('boards'));
		if (\DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$boards->addOption('charset', $charset);
			$boards->addOption('collate', $collation);
		}
		$boards->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$boards->addColumn('shortname', 'string', ['length' => 32]);
		$boards->addColumn('name', 'string', ['length' => 256]);
		$boards->addColumn('archive', 'smallint', ['unsigned' => true, 'default' => 0]);
		$boards->addColumn('sphinx', 'smallint', ['unsigned' => true, 'default' => 0]);
		$boards->addColumn('hidden', 'smallint', ['unsigned' => true, 'default' => 0]);
		$boards->addColumn('hide_thumbnails', 'smallint', ['unsigned' => true, 'default' => 0]);
		$boards->addColumn('directory', 'text', ['length' => 65532]);
		$boards->addColumn('max_indexed_id', 'integer', ['unsigned' => true, 'default' => 0]);
		$boards->addColumn('max_ancient_id', 'integer', ['unsigned' => true, 'default' => 0]);
		$boards->setPrimaryKey(['id']);
		$boards->addUniqueIndex(['shortname'], 'shortname_index');

		$boards_preferences = $schema->createTable(\DC::p('boards_preferences'));
		if (\DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$boards_preferences->addOption('charset', $charset);
			$boards_preferences->addOption('collate', $collation);
		}
		$boards_preferences->addColumn('board_preference_id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$boards_preferences->addColumn('board_id', 'integer', ['unsigned' => true]);
		$boards_preferences->addColumn('name', 'string', ['length' => 64]);
		$boards_preferences->addColumn('value', 'text', ['notnull' => false, 'length' => 65532]);
		$boards_preferences->setPrimaryKey(['board_preference_id']);
		$boards_preferences->addIndex(['board_id', 'name'], 'board_id_name_index');

		$reports = $schema->createTable(\DC::p('reports'));
		if (\DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$reports->addOption('charset', $charset);
			$reports->addOption('collate', $collation);
		}
		$reports->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$reports->addColumn('board_id', 'integer', ['unsigned' => true]);
		$reports->addColumn('doc_id', 'integer', ['unsigned' => true, 'notnull' => false, 'default' => null]);
		$reports->addColumn('media_id', 'integer', ['unsigned' => true, 'notnull' => false, 'default' => null]);
		$reports->addColumn('reason', 'text', ['length' => 65532]);
		$reports->addColumn('ip_reporter', 'decimal', ['unsigned' => true, 'precision' => 39, 'scale' => 0]);
		$reports->addColumn('created', 'integer', ['unsigned' => true]);
		$reports->setPrimaryKey(['id']);
		$reports->addIndex(['board_id', 'doc_id'], 'board_id_doc_id_index');
		$reports->addIndex(['board_id', 'media_id'], 'board_id_media_id_index');
	}
}