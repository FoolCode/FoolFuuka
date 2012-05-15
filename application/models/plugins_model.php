<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Plugins_model extends CI_Model
{

	var $_controller_uris = array();
	var $_hooks = array();

	
	function __construct()
	{
		parent::__construct();
	}


	/**
	 * We're dealing with making plugins that play well with controllers
	 * and we need Arrays to work properly. Without this $this->var[] = $data
	 * wouldn't work if $this->var is set in the controller
	 * Example: $this->viewdata['controller_title'] = $title; would fail  
	 *
	 * 
	 * @param string $key
	 */
	function &__get($key)
	{
		return get_instance()->$key;
	}


	/**
	 * The plugin slugs are the folder names 
	 */
	function lookup_plugins()
	{
		$this->load->helper('directory');
		$slugs = directory_map(FOOL_PLUGIN_DIR, 1);

		return $slugs;
	}


	function get_info_by_slug($slug)
	{
		include(FOOL_PLUGIN_DIR . $slug . '/' . $slug . '_info.php');
		return (object) $info;
	}


	/**
	 * Retrieve all the available plugins and their status 
	 */
	function get_all()
	{
		$slugs = $this->lookup_plugins();

		if (count($slugs) > 0)
		{

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
		}
		else
		{
			$result = array();
		}

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

		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->dbprefix('plugins') . '
		');
	}


	function get_enabled()
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->dbprefix('plugins') . '
			WHERE enabled = 1
		');

		return $query->result();
	}


	function get_by_slug($slug)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->dbprefix('plugins') . '
			WHERE slug = ?
		',
			array($slug));

		$plugin = $query->row();
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

				if (method_exists($this->$slug, 'initialize_plugin'))
				{
					$this->$slug->initialize_plugin();
				}
			}
			else
			{
				log_message('error', 'Plugin to be loaded couldn\'t be found: ' . $slug);
			}
		}
	}


	function enable($slug)
	{
		$this->db->query('
			INSERT INTO ' . $this->db->dbprefix('plugins') . '
			(slug, enabled)
			VALUES (?, 1)
			ON DUPLICATE KEY UPDATE enabled = 1
		',
			array($slug));

		$this->upgrade($slug);

		return $this->get_by_slug($slug);
	}


	function disable($slug)
	{
		$query = $this->db->query('
			UPDATE ' . $this->db->dbprefix('plugins') . '
			SET enabled = 0
			WHERE slug = ?
		',
			array($slug));

		return $this->get_by_slug($slug);
	}


	function remove($slug)
	{
		if(method_exists($this, 'disable_plugin'))
			$this->disable_plugin($slug);

		delete_files('content/plugins/' . $slug, TRUE);
	}


	function upgrade($slug)
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

			$class->plugin_install();

			$query = $this->db->query('
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
				$update_method = 'upgrade_' . str_pad($plugin->revision + 1, 3, '0', STR_PAD_LEFT);
				if (method_exists($class, $update_method))
				{
					$class->$update_method();
				}
				else
				{
					log_message('error',
						'Couldn\'t find upgrade method in plugin: ' . $update_method);
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


	function get_controller_function($uri)
	{
		return $this->is_controller_function($uri);
	}


	function is_controller_function($uri_array)
	{
		// codeigniter $this->uri->rsegment_uri sends weird indexes in the array with 1+ start
		// this reindexes the array
		$uri_array = array_values($uri_array);
		
	
		foreach ($this->_controller_uris as $item)
		{
			// it must be contained by the entire URI
			foreach ($item['uri_array'] as $key => $chunk)
			{
				if (($chunk != $uri_array[$key] && $chunk != '(:any)') ||
					(count($item['uri_array']) > count($uri_array))
				)
				{
					break;
				}

				// we've gone over the select URI, the plugin activates
				if ($key == count($uri_array) - 1)
				{
					return $item;
				}
			}
		}

		return FALSE;
	}


	/**
	 * Send an array, if shorter than the URI it will trigger the class method requested
	 * 
	 * @param type $controller_name
	 * @param type $method 
	 */
	function register_controller_function(&$class, $uri_array, $method)
	{
		$this->_controller_uris[] = array('uri_array' => $uri_array, 'plugin' => $class, 'method' => $method);
	}
	
	/**
	 * Adds a sidebar element when admin controller is accessed.
	 * 
	 * @param string $section under which controller/section of the sidebar must this sidebar element appear
	 * @param array $array the overriding array, comprehending only the additions and modifications to the sidebar
	 */
	function register_admin_sidebar_element($section, $array = null)
	{
		// the user can also send an array with the index inseted in $section
		if(!is_null($array))
		{
			$array2 = array();
			$array2[$section] = $array;
			$array = $array2;
		}
		
		$CI = & get_instance();
		if($CI instanceof Admin_Controller)
		{
			$CI->add_sidebar_element($array);
		}
	}
	
	
	function run_hook($target, $parameters = array())
	{
		if(!isset($this->_hooks[$target]))
			return FALSE;
		
		$hook_array = $this->_hooks[$target];
		
		usort($hook_array, function($a, $b){
			return $a['priority'] - $b['priority'];
		});
		
		$return = array();
		
		foreach($hook_array as $hook)
		{
			$return[] = call_user_func_array(array($hook['plugin'], $hook['method']), $parameters);
		}
		
		return $return;
	}
	
	
	function register_hook(&$class, $target, $priority, $method)
	{
		$this->_hooks[$target][] = array('plugin' => $class, 'priority' => $priority, 'method' => $method);
	}

}