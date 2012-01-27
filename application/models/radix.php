<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Radix extends CI_Model
{

	var $preloaded_radixes = null;
	var $preloaded_radixes_array = null;
	var $loaded_radixes_archive = null;
	var $loaded_radixes_archive_array = null;
	var $loaded_radixes_board = null;
	var $loaded_radixes_board_array = null;

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
		
		foreach($object as $item)
		{
			$result_object[$item->id] = $item;
		}
		
		foreach($array as $item)
		{
			$result_array[$item['id']] = $item;
		}
		
		$this->preloaded_radixes = $result_object;
		$this->preloaded_radixes_array = $result_array;
	}


	/**
	 * Returns all the radixes as array of objects
	 * 
	 * @return array
	 */
	function get_all()
	{
		return $preloaded_radixes;
	}


	/**
	 * Returns all the radixes as array of arrays
	 *
	 * @return array
	 */
	function get_all_array()
	{
		return $preloaded_radixes_array;
	}


	/**
	 * Returns the single radix
	 */
	function get_by_id($radix_id)
	{
		$object = get_all();
		if(isset($object[$radix_id]))
			return $object[$radix_id];
		
		return FALSE;
	}
	
	/**
	 * Returns the single radix as array
	 */
	function get_by_id_array($radix_id)
	{
		$array = get_all_array();
		if(isset($array[$radix_id]))
			return $array[$radix_id];
		
		return FALSE;
	}
	
	/**
	 * Returns only the archive radixes
	 */
	function get_archives()
	{
		if(!is_null($this->$loaded_radixes_archive))
			return $loaded_radixes_archive;
		
		// just make a copy and get rid of the non-archive ones
		$all = get_all();
	}
}