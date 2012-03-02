<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Plugins extends CI_Model
{

	var $_controller_functions;


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
		$slugs = directory_map(FOOLSLIDE_PLUGIN_DIR, 1);

		return $slugs;
	}


	function get_info_by_slug($slug)
	{
		include(FOOLSLIDE_PLUGIN_DIR . $slug . '/' . $slug . '_info.php');
		return (object) $info;
	}


	/**
	 * Retrieve all the available plugins and their status 
	 */
	function get_all()
	{
		$slugs = $this->lookup_plugins();

		// we don't care if the database doesn't contain an entry for a plugin
		// in that case, it means it was never installed
		$slugs_to_sql = $slugs;
		foreach ($slugs_to_sql as $key => $slug_to_sql)
		{
			$slugs_to_sql[$key] = $this->db->escape($slug_to_sql);
		}
		$sql = implode(' OR slug = ', $slugs_to_sql);
		$query = $this->db->query('
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

			$slugs_with_data[$slug]->info = $this->get_info_by_slug($slug);

			if (!$done)
			{
				$slugs_with_data[$slug]->enabled = FALSE;
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


	function get_by_slug($slug)
	{
		$query = $query->db->query('
			SELECT *
			FROM ' . $this->db->dbprefix('plugins') . '
			WHERE slug = ?
		',
			array($slug));

		$plugin = $query->result();
		$plugin->info = $this->get_info_by_slug($slug);

		return $plugin;
	}


	function load_plugins()
	{
		$plugins = $this->get_enabled();

		foreach ($plugins as $plugin)
		{
			$slug = $plugin->slug;
			if (file_exists('content/plugins/' . $slug . '/' . $slug . '.php'))
			{
				require_once 'content/plugins/' . $slug . '/' . $slug . '.php';
				$this->$slug = new $slug();
			}
			else
			{
				log_message('error', 'Plugin to be loaded couldn\'t be found: ' . $slug);
			}
		}
	}


	function enable($slug)
	{
		$query->db->query('
			INSERT INTO ' . $this->db->dbprefix('plugins') . '
			(slug, enabled)
			VALUES (?, 1)
			ON DUPLICATE KEY UPDATE enabled = 1
		',
			array($slug));

		$this->update($slug);

		return $this->get_by_slug($slug);
	}


	function disable($slug)
	{
		$query = $query->db->query('
			UPDATE ' . $this->db->dbprefix('plugins') . '
			SET enabled = 0
			WHERE slug = ?
		',
			array($slug));

		return $this->get_by_slug($slug);
	}


	function remove($slug)
	{
		$this->disable($slug);

		delete_files('content/plugins/' . $slug, TRUE);
	}


	function update($slug)
	{
		$plugin = $this->get_by_slug($slug);

		if (file_exists('content/plugins/' . $slug . '/' . $slug . '.php'))
		{
			require_once 'content/plugins/' . $slug . '/' . $slug . '.php';
		}
		else
		{
			log_message('error', 'Plugin to be loaded couldn\'t be found: ' . $slug);
			return array('error', 'file_not_found');
		}


		$class = new $slug();
		// NULL revision means that the plugin isn't installed
		if (is_null($plugin->revision))
		{

			$class->install();

			$query = $query->db->query('
				UPDATE ' . $this->db->dbprefix('plugins') . '
				SET revision = 0
				WHERE slug = ?
			',
				array($slug));
		}

		$done = FALSE;
		while (!$done)
		{
			// let's get an updated entry
			$plugin = $this->get_by_slug($slug);

			if ($plugin->revision < $plugin->info->revision)
			{
				$update_method = 'upgrade_' . str_pad($plugin->revision + 1, 3, '0',
						STR_PAD_LEFT);
				if (method_exists($class, $update_method))
				{
					$class->$update_method();
				}
				else
				{
					log_message('error', 'Couldn\'t find upgrade method in plugin: ' . $update_method);
					return array('error', 'upgrade_method_not_found');
					$done = TRUE;
					break;
				}

				$query = $query->db->query('
					UPDATE ' . $this->db->dbprefix('plugins') . '
					SET revision = ?
					WHERE slug = ?
				',
					array($plugin->revision + 1, $slug));
			}
			else
			{
				$done = TRUE;
			}
		}
		
		return TRUE;
	}


	function get_controller_functions()
	{
		return $this->_controller_functions;
	}


	function is_controller_function($controller_name, $function_name = array())
	{
		return FALSE;
	}


	function add_controller_function($controller_name, $function_name)
	{
		$this->_controller_functions[$controller_name][] = $function_name;
	}

}