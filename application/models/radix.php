<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Radix extends CI_Model
{

	// caching results
	var $preloaded_radixes = null;
	var $preloaded_radixes_array = null;
	var $loaded_radixes_archive = null;
	var $loaded_radixes_archive_array = null;
	var $loaded_radixes_board = null;
	var $loaded_radixes_board_array = null;
	var $selected_radix = null; // readily available if set

	function __construct($id = NULL)
	{
		parent::__construct();
		$this->preload();
	}


	/**
	 * Puts the table in readily available variables
	 */
	function preload()
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('boards', TRUE) . '
		');

		$object = $query->result();
		$array = $query->result_array();
		$result = array();

		foreach ($object as $item)
		{
			$result_object[$item->id] = $item;
		}

		foreach ($array as $item)
		{
			$result_array[$item['id']] = $item;
		}

		$this->preloaded_radixes = $result_object;
		$this->preloaded_radixes_array = $result_array;
	}


	function set_selected_radix_by_shortname($radix)
	{
		
	}


	/**
	 * Returns all the radixes as array of objects
	 * 
	 * @return array
	 */
	function get_all()
	{
		return $this->preloaded_radixes;
	}


	/**
	 * Returns all the radixes as array of arrays
	 *
	 * @return array
	 */
	function get_all_array()
	{
		return $this->preloaded_radixes_array;
	}


	/**
	 * Returns the single radix
	 */
	function get_by_id($radix_id, $array = FALSE)
	{
		if ($array)
			$items = $this->get_all_array();
		else
			$items = $this->get_all();

		if (isset($items[$radix_id]))
			return $items[$radix_id];

		return FALSE;
	}


	/**
	 * Returns the single radix as array
	 */
	function get_by_id_array($radix_id)
	{
		return $this->get_by_id($radix_id, TRUE);
	}


	/**
	 * Returns the single radix by type selected
	 *
	 * @param type $value
	 * @param type $type
	 * @param type $switch
	 * @param type $array
	 * @return type 
	 */
	function get_by_type($value, $type, $switch = TRUE)
	{
		$items = $this->get_all();

		foreach($items as $item)
		{
			if($switch == ($item->$type === $value))
			{
				return $item;
			}
		}

		return FALSE;
	}

	/**
	 * Returns the single radix by type selected as array
	 *
	 * @param type $value
	 * @param type $type
	 * @param type $switch
	 * @param type $array
	 * @return type 
	 */
	function get_by_type_array($value, $type, $switch = TRUE)
	{
		$items = $this->get_all();

		foreach($items as $item)
		{
			if($switch === ($item[$type] === $value))
			{
				return $item;
			}
		}

		return FALSE;
	}

	/**
	 * Returns the single radix by shortname
	 */
	function get_by_shortname($shortname)
	{
		return $this->get_by_type($shortname, 'shortname');
	}
	
	/**
	 * Returns the single radix as array by shortname
	 */
	function get_by_shortname($shortname)
	{
		return $this->get_by_type_array($shortname, 'shortname');
	}


	/**
	 * Returns only the type specified (exam)
	 *
	 * @param string $type 'archive'
	 * @param boolean $switch 'archive'
	 * @param type $array
	 * @return type 
	 */
	function get_by_type($type, $switch, $array = FALSE)
	{
		if ($array)
			$items = $this->get_all_array();
		else
			$items = $this->get_all();

		foreach ($all as $key => $item)
		{
			if ($array)
				if ($item[$type] == !$switch)
					unset($all[$key]);
				else
				if ($item->$type == !$switch)
					unset($all[$key]);
		}

		return $all;
	}


	/**
	 *  Returns an array of objects that are archives
	 */
	function get_archives()
	{
		if (!is_null($this->loaded_radixes_archive))
			return $this->loaded_radixes_archive;

		return $this->loaded_radixes_archive = $this->get_by_type('archive', TRUE);
	}


	/**
	 *  Returns an array of objects that are boards (not archives)
	 */
	function get_boards()
	{
		if (!is_null($this->loaded_radixes_board))
			return $this->loaded_radixes_board;

		return $this->loaded_radixes_boards = $this->get_by_type('archive', FALSE);
	}


	/**
	 *  Returns an array of arrays that are archives
	 */
	function get_archives_array()
	{
		if (!is_null($this->loaded_radixes_archive_array))
			return $this->loaded_radixes_archive_array;

		return $this->loaded_radixes_archive_array = $this->get_by_type('archive', TRUE, TRUE);
	}


	/**
	 *  Returns an array of arrays that are boards (not archives)
	 */
	function get_boards_array()
	{
		if (!is_null($this->loaded_radixes_board_array))
			return $this->loaded_radixes_board_array;

		return $this->loaded_radixes_board_array = $this->get_by_type('archive', FALSE, TRUE);
	}


}