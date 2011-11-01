<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class License extends DataMapper
{

	// in this case the cache is about comic_id results
	static $cached = array();
	var $has_one = array('comic');
	var $has_many = array();
	var $validation = array(
		'comic_id' => array(
			'rules' => array(),
			'label' => 'Comic ID',
		),
		'nation' => array(
			'rules' => array(),
			'label' => 'nation',
		)
	);

	function __construct($id = NULL)
	{
		parent::__construct($id);
	}


	function post_model_init($from_cache = FALSE)
	{
		
	}


	/**
	 * Returns the licenses that have been already called before
	 * 
	 * @author Woxxy
	 * @param int $id comic_id
	 */
	public function get_cached($id)
	{
		if (isset(self::$cached[$id]))
		{
			return self::$cached[$id];
		}
		return FALSE;
	}


	/**
	 * Gets all the series' licensed nations and puts the country codes in an array
	 * 
	 * @param int $id comic_id
	 * @return array country codes
	 */
	function get_by_comic($id)
	{
		// return the cached result
		if (is_array($result = $this->get_cached($id)))
			return $result;

		$this->where('comic_id', $id)->get();
		$result = array();

		foreach ($this->all as $item)
		{
			$result[] = $item->nation;
		}

		if (count(self::$cached) > 15)
			array_shift(self::$cached);
		self::$cached[$id] = $result;

		return $result;
	}


	function update($id, $new)
	{
		$this->where('comic_id', $id)->get();
		if ($this->result_count() > 0)
		{
			$previous = $this->get_by_comic($id);
			$partial = array_diff($previous, $new);
			if (empty($partial) && (count($previous) == count($new)))
			{
				return true;
			}
			$this->clear();
			$this->where('comic_id', $id)->get();
			$this->delete_all();
		}


		foreach ($new as $item)
		{
			if ($item == FALSE)
				continue;
			$this->clear();
			$this->comic_id = $id;
			$this->nation = $item;
			$this->save();
		}
		return true;
	}


}

/* End of file license.php */
/* Location: ./application/models/license.php */
