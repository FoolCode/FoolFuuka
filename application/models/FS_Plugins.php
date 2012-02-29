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

		// we don't care if the database doesn't contain an entry for a plugin
		// in that case, it means it was never installed
		$sql = implode(' OR slug = ', $this->db->escape($slugs));
		$query = $query->db->query('
			SELECT *
			FROM ' . $this->db->dbprefix('plugin_fs-articles') . '
			WHERE slug = ' . $sql . '
		');

		$result = $query->result();
		
		$slugs_with_data = array();
		foreach($slugs as $slug)
		{
			$done = FALSE;
			foreach($result as $r)
			{
				if($slug == $r->slug)
				{
					$slugs_with_data[$slug] = $r;
					$done = TRUE;
				}
			}
			if(!$done)
			{
				$slugs_with_data[$slug] = array();
			}
		}
		
		return $slugs_with_data;
	}
	
	
	/**
	 * Refreshes the list of plugins in database 
	 */
	function refresh_plugins()
	{
		$slugs = $this->lookup_plugins();
		
		$query = $query->db->query('
			SELECT *
			FROM ' . $this->db->dbprefix('plugin_fs-articles') . '
			WHERE slug = ' . $sql . '
		');
	}
		
	function load_plugins()
	{
		
	}
	
	function enable_plugin($slug)
	{
		
	}
	
	function disable_plugin($slug)
	{
		
	}
	
	function remove_plugin($slug)
	{
		$this->disable_plugin($slug);
		
	}
	
	function add_admin_controller()
	{}
	
	function add_admin_controller_function()
	{}
	
	function add_radix_function()
	{}
}