<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\Config;
use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Context;
use Foolz\Foolframe\Model\Preferences;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class Radix extends Model
{
    /*
     * Set of database fields
     */
    public $id = 0;

    public $name = '';

    public $shortname = '';

    public $archive = 0;

    public $sphinx = 0;

    public $hidden = 0;

    public $hide_thumbnails = 0;

    public $directory = '';

    public $max_indexed_id = 0;

    public $max_ancient_id = 0;

    /**
     * Array of key => value for radix configurations
     *
     * @var array
     */
    protected $values = [];

    /**
     * @var RadixCollection
     */
    protected $collection;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var Preferences
     */
    protected $preferences;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(Context $context, RadixCollection $collection)
    {
        parent::__construct($context);

        $this->collection = $collection;
        $this->dc = $context->getService('doctrine');
        $this->preferences = $context->getService('preferences');
        $this->config = $context->getService('config');
    }

    /**
     * Removes the board and renames its dir with a _removed suffix and with a number
     * in case of collision
     */
    public function remove()
    {
        // always remove the triggers first
        $this->dc->getConnection()->beginTransaction();
        $this->dc->qb()
            ->delete($this->dc->p('boards_preferences'))
            ->where('board_id = :id')
            ->setParameter(':id', $this->id)
            ->execute();

        $this->dc->qb()
            ->delete($this->dc->p('boards'))
            ->where('id = :id')
            ->setParameter(':id', $this->id)
            ->execute();

        // rename the directory and prevent directory collision
        $base = $this->preferences->get('foolfuuka.boards.directory') . '/' . $this->shortname;
        if (file_exists($base . '_removed')) {
            $incremented = \Str::increment('_removed');
            while (file_exists($base . $incremented)) {
                $incremented = \Str::increment($incremented);
            }

            $rename_to = $base . $incremented;
        } else {
            $rename_to = $this->preferences->get('foolfuuka.boards.directory') . '/' . $this->shortname . '_removed';
        }

        rename($base, $rename_to);

        // for huge boards, this may time out with PHP, while MySQL will keep going
        $this->removeTables();
        $this->dc->getConnection()->commit();
        $this->collection->clearCache();
    }

    /**
     * Get the config parameter of the radix by key
     *
     * @param  string $key  The key associated to the key
     *
     * @return  mixed  The value associated to the key
     * @throws  \OutOfBoundsException If the key doesn't exist (this should be a typo, as default values are always set on preload)
     */
    public function getValue($key)
    {
        if (!isset($this->values[$key])) {
            throw new \OutOfBoundsException;
        }

        return $this->values[$key];
    }

    /**
     * Return all the values for the radix as an associative array
     *
     * @return  array  Associative array of key => value
     */
    public function getAllValues()
    {
        return $this->values;
    }

    /**
     * Bind a radix config value to a key
     *
     * @param  string $key    The key to bind to
     * @param  mixed $value  The value to bind
     */
    public function setValue($key, $value)
    {
        $this->values[$key] = $value;
    }

    /**
     * Returns the database prefix for the boards
     *
     * @return string
     */
    public function getPrefix()
    {
        // if the value really doesn't exist in the db
        if ($this->preferences->get('foolfuuka.boards.prefix', null, true) === null) {
            return $this->dc->getPrefix() . 'board_';
        }

        return $this->preferences->get('foolfuuka.boards.prefix');
    }

    /**
     * Get the board table name with protexted identifiers
     *
     * @param   string $suffix  board suffix like _images
     *
     * @return  string  the table name with protected identifiers
     */
    public function getTable($suffix = '')
    {
        if ($this->preferences->get('foolfuuka.boards.db')) {
            return $this->dc->getConnection()->quoteIdentifier($this->preferences->get('foolfuuka.boards.db'))
            . '.' . $this->dc->getConnection()->quoteIdentifier($this->getPrefix() . $this->shortname . $suffix);
        } else {
            return $this->dc->getConnection()->quoteIdentifier($this->getPrefix() . $this->shortname . $suffix);
        }
    }

    /**
     * Get the board index name for index creation (doesn't redirect to foreign database! must "use"!)
     *
     * @param   string $suffix  board suffix like _images
     * @param   string $index   board index like op_index
     *
     * @return  string  the table name with protected identifiers
     */
    public function getIndex($suffix = '', $index = '')
    {
        if ($this->dc->getConnection()->getDriver()->getName() == 'pdo_pgsql') {
            return $this->getPrefix() . $this->shortname . $suffix . '_' . $index;
        } else {
            return $index;
        }
    }

    /**
     * Creates the tables for the board
     */
    public function createTables()
    {
        $config = $this->config->get('foolz/foolframe', 'db', 'default');
        $config['dbname'] = $this->preferences->get('foolfuuka.boards.db') ?: $config['dbname'];
        $config['prefix'] = $this->preferences->get("foolfuuka.boards.prefix");
        $conn = new DoctrineConnection($this->getContext(), $config);

        $charset = 'utf8mb4';
        $collate = 'utf8mb4_general_ci';

        $sm = $conn->getConnection()->getSchemaManager();
        $schema = $sm->createSchema();

        // create the main table also in _deleted flavour
        foreach (['', '_deleted'] as $key) {
            if (!$schema->hasTable($this->getPrefix() . $this->shortname . $key)) {
                $table = $schema->createTable($this->getPrefix() . $this->shortname . $key);
                if ($conn->getConnection()->getDriver()->getName() == 'pdo_mysql') {
                    $table->addOption('charset', $charset);
                    $table->addOption('collate', $collate);
                }
                $table->addColumn('doc_id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
                $table->addColumn('media_id', 'integer', ['unsigned' => true, 'default' => 0]);
                $table->addColumn('poster_ip', 'decimal', ['unsigned' => true, 'precision' => 39, 'scale' => 0, 'default' => 0]);
                $table->addColumn('num', 'integer', ['unsigned' => true]);
                $table->addColumn('subnum', 'integer', ['unsigned' => true]);
                $table->addColumn('thread_num', 'integer', ['unsigned' => true, 'default' => 0]);
                $table->addColumn('op', 'boolean', ['default' => 0]);
                $table->addColumn('timestamp', 'integer', ['unsigned' => true]);
                $table->addColumn('timestamp_expired', 'integer', ['unsigned' => true]);
                $table->addColumn('preview_orig', 'string', ['length' => 20, 'notnull' => false]);
                $table->addColumn('preview_w', 'smallint', ['unsigned' => true, 'default' => 0]);
                $table->addColumn('preview_h', 'smallint', ['unsigned' => true, 'default' => 0]);
                $table->addColumn('media_filename', 'text', ['length' => 65532, 'notnull' => false]);
                $table->addColumn('media_w', 'smallint', ['unsigned' => true, 'default' => 0]);
                $table->addColumn('media_h', 'smallint', ['unsigned' => true, 'default' => 0]);
                $table->addColumn('media_size', 'integer', ['unsigned' => true, 'default' => 0]);
                $table->addColumn('media_hash', 'string', ['length' => 25, 'notnull' => false]);
                $table->addColumn('media_orig', 'string', ['length' => 20, 'notnull' => false]);
                $table->addColumn('spoiler', 'boolean', ['default' => 0]);
                $table->addColumn('deleted', 'boolean', ['default' => 0]);
                $table->addColumn('capcode', 'string', ['length' => 1, 'default' => 'N']);
                $table->addColumn('email', 'string', ['length' => 100, 'notnull' => false]);
                $table->addColumn('name', 'string', ['length' => 100, 'notnull' => false]);
                $table->addColumn('trip', 'string', ['length' => 25, 'notnull' => false]);
                $table->addColumn('title', 'string', ['length' => 100, 'notnull' => false]);
                $table->addColumn('comment', 'text', ['length' => 65532, 'notnull' => false]);
                $table->addColumn('delpass', 'text', ['length' => 255, 'notnull' => false]);
                $table->addColumn('sticky', 'boolean', ['default' => 0]);
                $table->addColumn('poster_hash', 'string', ['length' => 8, 'notnull' => false]);
                $table->addColumn('poster_country', 'string', ['length' => 2, 'notnull' => false]);
                $table->addColumn('exif', 'text', ['length' => 65532, 'notnull' => false]);
                $table->setPrimaryKey(['doc_id']);
                $table->addUniqueIndex(['num', 'subnum'], $this->getIndex($key, 'num_subnum_index'));
                $table->addIndex(['thread_num', 'num', 'subnum'], $this->getIndex($key, 'thread_num_subnum_index'));
                $table->addIndex(['subnum'], $this->getIndex($key, 'subnum_index'));
                $table->addIndex(['op'], $this->getIndex($key, 'op_index'));
                $table->addIndex(['media_id'], $this->getIndex($key, 'media_id_index'));
                $table->addIndex(['media_hash'], $this->getIndex($key, 'media_hash_index'));
                $table->addIndex(['media_orig'], $this->getIndex($key, 'media_orig_index'));
                $table->addIndex(['name', 'trip'], $this->getIndex($key, 'name_trip_index'));
                $table->addIndex(['trip'], $this->getIndex($key, 'trip_index'));
                $table->addIndex(['email'], $this->getIndex($key, 'email_index'));
                $table->addIndex(['poster_ip'], $this->getIndex($key, 'poster_ip_index'));
                $table->addIndex(['timestamp'], $this->getIndex($key, 'timestamp_index'));
            }
        }

        if (!$schema->hasTable($this->getPrefix() . $this->shortname . '_threads')) {
            $table_threads = $schema->createTable($this->getPrefix() . $this->shortname . '_threads');
            if ($conn->getConnection()->getDriver()->getName() == 'pdo_mysql') {
                $table_threads->addOption('charset', $charset);
                $table_threads->addOption('collate', $collate);
            }
            $table_threads->addColumn('thread_num', 'integer', ['unsigned' => true]);
            $table_threads->addColumn('time_op', 'integer', ['unsigned' => true]);
            $table_threads->addColumn('time_last', 'integer', ['unsigned' => true]);
            $table_threads->addColumn('time_bump', 'integer', ['unsigned' => true]);
            $table_threads->addColumn('time_ghost', 'integer', ['unsigned' => true, 'notnull' => false, 'default' => 'null']);
            $table_threads->addColumn('time_ghost_bump', 'integer', ['unsigned' => true, 'notnull' => false, 'default' => 'null']);
            $table_threads->addColumn('time_last_modified', 'integer', ['unsigned' => true]);
            $table_threads->addColumn('nreplies', 'integer', ['unsigned' => true, 'default' => 0]);
            $table_threads->addColumn('nimages', 'integer', ['unsigned' => true, 'default' => 0]);
            $table_threads->addColumn('sticky', 'boolean', ['default' => 0]);
            $table_threads->addColumn('locked', 'boolean', ['default' => 0]);
            $table_threads->setPrimaryKey(['thread_num']);
            $table_threads->addIndex(['time_op'], $this->getIndex('_threads', 'time_op_index'));
            $table_threads->addIndex(['time_bump'], $this->getIndex('_threads', 'time_bump_index'));
            $table_threads->addIndex(['time_ghost_bump'], $this->getIndex('_threads', 'time_ghost_bump_index'));
            $table_threads->addIndex(['sticky'], $this->getIndex('_threads', 'sticky_index'));
            $table_threads->addIndex(['locked'], $this->getIndex('_threads', 'locked_index'));
        }

        if (!$schema->hasTable($this->getPrefix() . $this->shortname . '_users')) {
            $table_users = $schema->createTable($this->getPrefix() . $this->shortname . '_users');
            if ($conn->getConnection()->getDriver()->getName() == 'pdo_mysql') {
                $table_users->addOption('charset', $charset);
                $table_users->addOption('collate', $collate);
            }
            $table_users->addColumn('user_id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
            $table_users->addColumn('name', 'string', ['length' => 100, 'default' => '']);
            $table_users->addColumn('trip', 'string', ['length' => 25, 'default' => '']);
            $table_users->addColumn('firstseen', 'integer', ['unsigned' => true]);
            $table_users->addColumn('postcount', 'integer', ['unsigned' => true]);
            $table_users->setPrimaryKey(['user_id']);
            $table_users->addUniqueIndex(['name', 'trip'], $this->getIndex('_users', 'name_trip_index'));
            $table_users->addIndex(['firstseen'], $this->getIndex('_users', 'firstseen_index'));
            $table_users->addIndex(['postcount'], $this->getIndex('_users', 'postcount_index'));
        }

        if (!$schema->hasTable($this->getPrefix() . $this->shortname . '_images')) {
            $table_images = $schema->createTable($this->getPrefix() . $this->shortname . '_images');
            if ($conn->getConnection()->getDriver()->getName() == 'pdo_mysql') {
                $table_images->addOption('charset', 'utf8');
                $table_images->addOption('collate', 'utf8_general_ci');
            }
            $table_images->addColumn('media_id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
            $table_images->addColumn('media_hash', 'string', ['length' => 25]);
            $table_images->addColumn('media', 'string', ['length' => 20, 'notnull' => false]);
            $table_images->addColumn('preview_op', 'string', ['length' => 20, 'notnull' => false]);
            $table_images->addColumn('preview_reply', 'string', ['length' => 20, 'notnull' => false]);
            $table_images->addColumn('total', 'integer', ['unsigned' => true, 'default' => 0]);
            $table_images->addColumn('banned', 'smallint', ['unsigned' => true, 'default' => 0]);
            $table_images->setPrimaryKey(['media_id']);
            $table_images->addUniqueIndex(['media_hash'], $this->getIndex('_images', 'media_hash_index'));
            $table_images->addIndex(['total'], $this->getIndex('_images', 'total_index'));
            $table_images->addIndex(['banned'], $this->getIndex('_images', 'banned_index'));
        }

        if (!$schema->hasTable($this->getPrefix() . $this->shortname . '_daily')) {
            $table_daily = $schema->createTable($this->getPrefix() . $this->shortname . '_daily');
            if ($conn->getConnection()->getDriver()->getName() == 'pdo_mysql') {
                $table_daily->addOption('charset', 'utf8');
                $table_daily->addOption('collate', 'utf8_general_ci');
            }
            $table_daily->addColumn('day', 'integer', ['unsigned' => true]);
            $table_daily->addColumn('posts', 'integer', ['unsigned' => true]);
            $table_daily->addColumn('images', 'integer', ['unsigned' => true]);
            $table_daily->addColumn('sage', 'integer', ['unsigned' => true]);
            $table_daily->addColumn('anons', 'integer', ['unsigned' => true]);
            $table_daily->addColumn('trips', 'integer', ['unsigned' => true]);
            $table_daily->addColumn('names', 'integer', ['unsigned' => true]);
            $table_daily->setPrimaryKey(['day']);
        }

        if (!$schema->hasTable($this->getPrefix() . $this->shortname . '_extra')) {
            $table_extra = $schema->createTable($this->getPrefix() . $this->shortname . '_extra');
            if ($conn->getConnection()->getDriver()->getName() == 'pdo_mysql') {
                $table_extra->addOption('charset', $charset);
                $table_extra->addOption('collate', $collate);
            }
            $table_extra->addColumn('extra_id', 'integer', ['unsigned' => true]);
            $table_extra->addColumn('json', 'text', ['length' => 65532, 'notnull' => false]);
            $table_extra->setPrimaryKey(['extra_id']);
        }

        $conn->getConnection()->beginTransaction();

        foreach ($schema->getMigrateFromSql($sm->createSchema(), $sm->getDatabasePlatform()) as $query) {
            $conn->getConnection()->query($query);
        }

        $md5_array = $this->dc->qb()
            ->select('md5')
            ->from($this->dc->p('banned_md5'), 'm')
            ->execute()
            ->fetchAll();

        // in a transaction multiple inserts are almost like a single one
        foreach ($md5_array as $item) {
            $conn->getConnection()->insert($this->getTable('_images'), ['md5' => $item['md5'], 'banned' => 1]);
        }

        $conn->getConnection()->commit();
    }

    /**
     * Remove the tables associated to the Radix
     */
    public function removeTables()
    {
        $tables = [
            '',
            '_deleted',
            '_images',
            '_threads',
            '_users',
            '_daily',
            '_extra'
        ];

        $sm = $this->dc->getConnection()->getSchemaManager();
        $schema = $sm->createSchema();

        foreach ($tables as $table) {
            $schema->dropTable($this->getTable($table));
        }

        foreach ($schema->getMigrateFromSql($sm->createSchema(), $sm->getDatabasePlatform()) as $query) {
            $this->dc->getConnection()->query($query);
        }
    }
}
