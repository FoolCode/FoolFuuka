<?php

namespace Foolfuuka\Model;

class ExtraException extends \FuelException {}

class Extra
{
	public $doc_id = 0;
	
	public $json_array = null;
	
	public static $_fields = array(
		'extra_id', 
		'json', // gets automatically converted to associative array
		'json_array'
	);
	
	public $_radix = null;
	
	
	public static function set_fields()
	{
		static::$_fields = \Plugin::run_hook('foolfuuka.model.extra.forge.add_columns', array(static::$_fields), 'simple');
	}
	
	
	public static function get_fields()
	{
		return static::$_fields;
	}
	
	
	public static function forge($comment, $board)
	{
		$new = new static();
		
		$new->_radix = $board;
		
		foreach ($comment as $key => $item)
		{
			if (in_array($key, static::get_fields()))
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
	
	public function insert()
	{
		if ( ! empty($this->json_array))
		{
			\DB::insert(\DB::expr(Radix::get_table($this->_radix, '_extra')))
				->set(array(
					'extra_id' => $this->extra_id,
					'json' => ! empty($this->json_array) ? json_encode($this->json_array) : null
				))->execute();
		}
	}
}