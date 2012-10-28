<?php

namespace Foolz\Foolfuuka\Model;

class ExtraException extends \Exception {}

/**
 * Manages Extra data appended to Comments
 */
class Extra
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

	/**
	 * When called it refreshes the fields with the additions by plugins
	 */
	public static function setFields()
	{
		static::$fields = \Foolz\Plugin\Hook::forge('ff.model.extra.forge.add_columns')
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
	 * Creates a new Extra object
	 *
	 * @param  object                        $comment  An object with the data for the Extra object
	 * @param  \Foolz\Foolfuuka\Model\Radix  $radix    The Radix the Comment resides on
	 *
	 * @return \Foolz\Foolfuuka\Model\Extra  The newly created object
	 */
	public static function forge($comment, $radix)
	{
		$new = new static();

		$new->radix = $radix;

		foreach ($comment as $key => $item)
		{
			if (in_array($key, static::getFields()))
			{
				if ($key === 'json')
				{
					$new->json_array = json_decode($item, true);
				}
				else
				{
					$new->$key = $item;
				}
			}
		}

		unset($new->json);
		return $new;
	}

	/**
	 * Inserts the Extra in the database only if there's any data in it
	 */
	public function insert()
	{
		if ( ! empty($this->json_array))
		{
			\DC::forge()->insert($this->_radix->getTable('_extra'), [
				'extra_id' => $this->extra_id,
				'json' => ! empty($this->json_array) ? json_encode($this->json_array) : null
			]);
		}
	}
}