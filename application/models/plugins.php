<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Plugins extends CI_Model
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
			FROM ' . $this->db->dbprefix('plugins') . '
			WHERE slug = ' . $sql . '
		');

		$result = $query->result();

		$slugs_with_data = array();
		foreach ($slugs as $slug)
		{
			$done = FALSE;
			foreach ($result as $r)
			{
				if ($slug == $r->slug)
				{
					$slugs_with_data[$slug] = $r;
					$done = TRUE;
				}
			}
			if (!$done)
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
			FROM ' . $this->db->dbprefix('plugins') . '
		');
	}


	function get_enabled()
	{
		$query = $query->db->query('
			SELECT *
			FROM ' . $this->db->dbprefix('plugins') . '
			WHERE enabled = 1
		');

		return $query->result();
	}


	function load_plugins()
	{
		$plugins = $this->get_enabled();
		
		foreach($plugins as $plugin)
		{
			$slug = $plugin->slug;
			if(file_exists('content/plugins/'.$slug.'/'.$slug.'.php'))
			{
				require_once 'content/plugins/'.$slug.'/'.$slug.'.php';
				$this->$slug = new $slug();
			}
			else
			{
				log_message('error', 'Plugin to be loaded couldn\'t be found: ' . $slug);
			}
		}
	}


	function enable_plugin($slug)
	{
		$query = $query->db->query('
			UPDATE ' . $this->db->dbprefix('plugins') . '
			SET enabled = 1
			WHERE slug = ?
		', array($slug));
	}


	function disable_plugin($slug)
	{
		$query = $query->db->query('
			UPDATE ' . $this->db->dbprefix('plugins') . '
			SET enabled = 0
			WHERE slug = ?
		', array($slug));
	}


	function remove_plugin($slug)
	{
		$this->disable_plugin($slug);

		delete_files('content/plugins/' . $slug, TRUE);
	}


	function add_controller($controller_name)
	{
		
	}


	function add_controller_function($controller_name, $function_name, $parameters = array())
	{
		
	}


}