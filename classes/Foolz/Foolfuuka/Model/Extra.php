<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Context;
use Foolz\Plugin\Hook;

class ExtraException extends \Exception {}

/**
 * Manages Extra data appended to Comments
 */
class Extra extends Model
{
    use \Foolz\Plugin\PlugSuit;

    /**
     * The comment's doc_id
     *
     * @var  int
     */
    public $extra_id = 0;

    /**
     * The json string in the json column
     *
     * @var null|string
     */
    public $json = null;

    /**
     * The array stored in the json
     *
     * @var  null|array
     */
    public $json_array = null;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * The fields of the Extra object
     *
     * @var  array
     */
    public static $fields = [
        'extra_id',
        'json', // gets automatically converted to associative array
        'json_array'
    ];

    /**
     * The Radix this Extra refers to
     *
     * @var  \Foolz\Foolfuuka\Model\Radix
     */
    public $radix = null;

    public function __construct(Context $context, $comment, $radix)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');

        $this->radix = $radix;

        foreach ($comment as $key => $item) {
            if (in_array($key, $this->getFields())) {
                if ($key === 'json') {
                    $this->json_array = json_decode($item, true);
                } else {
                    $this->$key = $item;
                }
            }
        }

        unset($this->json);
    }

    /**
     * When called it refreshes the fields with the additions by plugins
     */
    public function setFields()
    {
        static::$fields = Hook::forge('Foolz\Foolfuuka\Model\Extra::forge.call.before.add_column')
            ->setParam('fields', static::$fields)
            ->execute()
            ->get(static::$fields);
    }

    /**
     * Returns the fields of the extra table
     *
     * @return  array  The fields
     */
    public static function getFields()
    {
        return static::$fields;
    }

    /**
     * Inserts the Extra in the database only if there's any data in it
     */
    public function insert()
    {
        if (!empty($this->json_array)) {
            $this->dc->getConnection()->insert($this->radix->getTable('_extra'), [
                'extra_id' => $this->extra_id,
                'json' => ! empty($this->json_array) ? json_encode($this->json_array) : null
            ]);
        }
    }
}
