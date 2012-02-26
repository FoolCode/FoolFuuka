<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class FS_Plugins extends CI_Model
{

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * The plugin slugs are the folder names 
	 */
	function lookup_plugins()
	{
		$this->load->helper('directory');
		$slugs = directory_map(FOOLSLIDE_PLUGIN_DIR);

		return $slugs;
	}

	/**
	 * Retrieve all the available plugins and their status 
	 */
	function get_all()
	{
		$slugs = $this->lookup_plugins();

		$sql = implode(' OR slug = ', $this->db->escape($slugs));

		$query = $query->db->query('
			SELECT *
			FROM ' . $this->db->dbprefix('plugin_fs-articles') . '
			WHERE slug = ' . $sql . '
		');

		$result = $query->result();
		
		if(count($result) != count($slugs))
		{
			$this->refresh_plugins();
			return $this->get_all();
		}
	}
	
	/**
	 * Refreshes the list of plugins in database 
	 */

}