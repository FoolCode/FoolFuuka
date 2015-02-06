<?php

namespace Foolz\FoolFuuka\Model;

use Foolz\FoolFrame\Model\DoctrineConnection;
use Foolz\FoolFrame\Model\SchemaManager;

class Schema
{
    use \Foolz\Plugin\PlugSuit;

    public static function load(\Foolz\FoolFrame\Model\Context $context, SchemaManager $sm)
    {
        /** @var DoctrineConnection $dc */
        $dc = $context->getService('doctrine');

        $charset = 'utf8mb4';
        $collate = 'utf8mb4_unicode_ci';

        $schema = $sm->getCodedSchema();

        $audit_log = $schema->createTable($dc->p('audit_log'));
        if ($dc->getConnection()->getDriver()->getName() == 'pdo_mysql') {
            $audit_log->addOption('charset', $charset);
            $audit_log->addOption('collate', $collate);
        }
        $audit_log->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $audit_log->addColumn('timestamp', 'integer', ['unsigned' => true]);
        $audit_log->addColumn('type', 'integer', ['unsigned' => true, 'default' => 0]);
        $audit_log->addColumn('user', 'integer', ['unsigned' => true, 'default' => 0]);
        $audit_log->addColumn('data', 'text', ['length' => 4294967295]);
        $audit_log->setPrimaryKey(['id']);

        $banned_md5 = $schema->createTable($dc->p('banned_md5'));
        $banned_md5->addColumn('md5', 'string', ['length' => 24]);
        $banned_md5->setPrimaryKey(['md5']);

        $banned_posters = $schema->createTable($dc->p('banned_posters'));
        if ($dc->getConnection()->getDriver()->getName() == 'pdo_mysql') {
            $banned_posters->addOption('charset', $charset);
            $banned_posters->addOption('collate', $collate);
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

        $boards = $schema->createTable($dc->p('boards'));
        if ($dc->getConnection()->getDriver()->getName() == 'pdo_mysql') {
            $boards->addOption('charset', $charset);
            $boards->addOption('collate', $collate);
        }
        $boards->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $boards->addColumn('shortname', 'string', ['length' => 32]);
        $boards->addColumn('name', 'string', ['length' => 256]);
        $boards->addColumn('archive', 'smallint', ['unsigned' => true, 'default' => 0]);
        $boards->addColumn('sphinx', 'smallint', ['unsigned' => true, 'default' => 0]);
        $boards->addColumn('hidden', 'smallint', ['unsigned' => true, 'default' => 0]);
        $boards->addColumn('hide_thumbnails', 'smallint', ['unsigned' => true, 'default' => 0]);
        $boards->addColumn('directory', 'text', ['length' => 65532, 'notnull' => false]);
        $boards->addColumn('max_indexed_id', 'integer', ['unsigned' => true, 'default' => 0]);
        $boards->addColumn('max_ancient_id', 'integer', ['unsigned' => true, 'default' => 0]);
        $boards->setPrimaryKey(['id']);
        $boards->addUniqueIndex(['shortname'], 'shortname_index');

        $boards_preferences = $schema->createTable($dc->p('boards_preferences'));
        if ($dc->getConnection()->getDriver()->getName() == 'pdo_mysql') {
            $boards_preferences->addOption('charset', $charset);
            $boards_preferences->addOption('collate', $collate);
        }
        $boards_preferences->addColumn('board_preference_id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $boards_preferences->addColumn('board_id', 'integer', ['unsigned' => true]);
        $boards_preferences->addColumn('name', 'string', ['length' => 64]);
        $boards_preferences->addColumn('value', 'text', ['notnull' => false, 'length' => 65532]);
        $boards_preferences->setPrimaryKey(['board_preference_id']);
        $boards_preferences->addIndex(['board_id', 'name'], 'board_id_name_index');

        $reports = $schema->createTable($dc->p('reports'));
        if ($dc->getConnection()->getDriver()->getName() == 'pdo_mysql') {
            $reports->addOption('charset', $charset);
            $reports->addOption('collate', $collate);
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
