<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * FoOlFrame Plugins Model
 *
 * The Plugins Model allows running code wherever there are hooks,
 * change parameters passed to functions manipulate returns, and create
 * (ficticious) controllers, and controller functions.
 *
 * @package        	FoOlFrame
 * @subpackage    	Models
 * @category    	Models
 * @author        	Woxxy
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Plugins_model extends CI_Model
{

	/**
	 * List of routes and callbacks
	 *
	 * @var array 
	 */
	private $_controller_uris = array();
	
	/**
	 * List of hooks with their associated classes, priority and callback
	 * 
	 * @var array 
	 */
	private $_hooks = array();

	
	/**
	 * Find all the available plugins on load
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->lookup_plugins();
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
	public function &__get($key)
	{
		return get_instance()->$key;
	}


	/**
	 * The plugin slugs are the folder names 
	 * 
	 * @return array The directory names
	 */
	private function lookup_plugins()
	{
		$this->load->helper('directory');
		$slugs = directory_map(FOOL_PLUGIN_DIR, 1);

		return $slugs;
	}


	/**
	 * Grabs the info from the plugin _info.php file and returns it as object
	 * 
	 * @param string $slug the directory of the plugin
	 * @return object the config
	 */
	private function get_info_by_slug($slug)
	{
		include(FOOL_PLUGIN_DIR . $slug . '/' . $slug . '_info.php');
		return (object) $info;
	}


	/**
	 * Retrieve all the available plugins and their status 
	 * 
	 * @return array Array of objects where $this[$x]->info contains the plugin info
	 */
	public function get_all()
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
	 * Gets the enabled plugins from the database
	 * 
	 * @return array Array of objects, the rows or the active plugins 
	 */
	private function get_enabled()
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->dbprefix('plugins') . '
			WHERE enabled = 1
		');

		return $query->result();
	}

	
	/**
	 * Gets the data of the plugin by slug
	 * 
	 * @param string $slug the directory name of the plugin
	 * @return object The database row of the plugin with extra ->info
	 */
	public function get_by_slug($slug)
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


	/**
	 * Loads and eventually initializes (runs) the plugins
	 *
	 * @param null|string $select the slug of the plugin if you want to choose one
	 * @param bool $initialize choose if just load the file or effectively run the plugin
	 */
	function load_plugins($select = NULL, $initialize = TRUE)
	{
		$plugins = $this->get_enabled();

		foreach ($plugins as $plugin)
		{
			if(!is_null($select) && $plugin->slug != $select)
				continue;
			
			$slug = $plugin->slug;
			if (file_exists('content/plugins/' . $slug . '/' . $slug . '.php'))
			{
				require_once 'content/plugins/' . $slug . '/' . $slug . '.php';
				$this->$slug = new $slug();

				if ($initialize && method_exists($this->$slug, 'initialize_plugin'))
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
	
	
	/**
	 * Allows inserting a plugin by class name, and also include its file
	 * 
	 * @param type $slug the slug
	 * @param type $class_name the name of the class of the plugin
	 * @param type $initialize if the initialize_plugin() function should be run
	 * @param type $file eventual file to require_once
	 */
	public function inject_plugin($slug, $class_name, $initialize = TRUE, $file = NULL)
	{
		if(is_string($file))
		{
			// produce a fatal error if the file doesn't exist to help the plugin maker to debug
			require_once $file;
		}
		
		$this->$slug = new $class_name();
		
		if ($initialize && method_exists($this->$slug, 'initialize_plugin'))
		{
			$this->$slug->initialize_plugin();
		}
	}


	/**
	 * Enables the plugin after running the upgrade function
	 * 
	 * @param string $slug the directory name of the plugin
	 * @return object The database row of the plugin with extra ->info
	 */
	public function enable($slug)
	{
		$this->db->query('
			INSERT INTO ' . $this->db->dbprefix('plugins') . '
			(slug, enabled)
			VALUES (?, 1)
			ON DUPLICATE KEY UPDATE enabled = 1
		',
			array($slug));

		$this->upgrade($slug);
		
		$this->load_plugins($slug);
		
		if(method_exists($this->$slug, 'plugin_enable'))
			$this->$slug->plugin_enable();

		return $this->get_by_slug($slug);
	}


	/**
	 * Disables plugin and runs plugin_disable()
	 * 
	 * @param string $slug the directory name of the plugin
	 * @return object database row for the plugin with extra ->info 
	 */
	public function disable($slug)
	{
		$query = $this->db->query('
			UPDATE ' . $this->db->dbprefix('plugins') . '
			SET enabled = 0
			WHERE slug = ?
		',
			array($slug));

		if(method_exists($this->$slug, 'plugin_disable'))
			$this->$slug->plugin_disable();
		
		return $this->get_by_slug($slug);
	}


	/**
	 * Deletes the plugin directory after running plugin_remove()
	 * 
	 * @param string $slug the directory name of the plugin
	 */
	public function remove($slug)
	{
		if(method_exists($this->$slug, 'plugin_remove'))
			$this->$slug->plugin_remove();

		delete_files('content/plugins/' . $slug, TRUE);
	}


	/**
	 * Reads revision in the info files, uses the upgrade_xxx functions to upgrade files/db
	 * then updates the revision in the database
	 *
	 * @param string $slug the directory name of the plugin
	 * @return boolean TRUE on success, FALSE on failure
	 */
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
			if(method_exists($class, 'plugin_install'))
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


	/**
	 * Alias for is_controller_function
	 * 
	 * @param array $uri the uri_array, basically $this->uri->segment_array()
	 * @return bool|array FALSE if not found, else the item from $this->_controller_uris
	 */
	function get_controller_function($uri)
	{
		return $this->is_controller_function($uri);
	}


	/**
	 * Checks if there is a match with the segment_array() and eventually returns the data
	 * necessary to run the controller function:
	 * array('uri_array' => $uri_array, 'plugin' => $class, 'method' => $method)
	 * 
	 * @param array $uri the uri_array, basically $this->uri->segment_array()
	 * @return bool|array FALSE if not found, else the item from $this->_controller_uris
	 */
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
	
	
	/**
	 * Runs functions stored in the hook
	 * 
	 * @param string $target the name of the hook
	 * @param array $parameters parameters to pass to the hook
	 * @param bool|string $type if FALSE it's a simple hook, 'before'/'after' if before or after a method
	 * @return null 
	 */
	function run_hook($target, $parameters = array(), $modifier = '')
	{
		if(!isset($this->_hooks[$target]) && $modifier == 'simple')
			return end($parameters);
		
		if(!isset($this->_hooks[$target]))
			return NULL;
		
		$hook_array = $this->_hooks[$target];
		
		usort($hook_array, function($a, $b){ return $a['priority'] - $b['priority']; });
		
		// default return if nothing happens
		$return = array('parameters' => $parameters, 'return' => NULL);
		
		foreach($hook_array as $hook)
		{
			// if this is 'after', we might already have an extra parameter in the array that is the previous result
			if($hook['method'] instanceof Closure)
			{
				$return_temp = call_user_func_array($hook['method'], $parameters);
			}
			else
			{
				$return_temp = call_user_func_array(array($hook['plugin'], $hook['method']), $parameters);
			}
			if(is_null($return_temp))
			{
				// if NULL, the plugin creator didn't want to send a message outside
				continue;
			}
			else if(!is_array($return_temp))
			{
				// if not an array, it's a plain result to stack in
				// the plugin creator can't do this if the result set is an array, and must use the complex solution
				$return['return'] = $return_temp;
				// but the return as last parameter
				array_push($parameters, $return['return']);
				continue;
			}
			
			// in the most complex situation, we have array('parameters'=>array(...), 'return'=>'value')
			if($modifier == 'simple' && !is_null($return_temp['return']))
			{
				// if simple we just stack the single parameter over and over
				$parameters = array($return_temp['return']);
			}
			else if(isset($return_temp['parameters']))
			{
				$parameters = $return_temp['parameters'];
				$return['parameters'] = $return_temp['parameters'];
			}
			if(isset($return_temp['return']))
			{
				$return['return'] = $return_temp['return'];
				// but the return as last parameter
				array_push($parameters, $return['return']);
			}
		}
		
		if($modifier == 'simple')
			return $return['return'];

		return $return;
	}
	
	
	/**
	 * Registers a function to the hook targeted
	 *
	 * @param object $class the class in which the method to run is located
	 * @param string $target the name of the hook
	 * @param int $priority the lowest, the highest the priority. negatives ALLOWED
	 * @param string|Closure $method name of the method or the closure to run
	 */
	function register_hook(&$class, $target, $priority, $method)
	{
		$this->_hooks[$target][] = array('plugin' => $class, 'priority' => $priority, 'method' => $method);
	}

}

/* End of file plugins_model.php */
/* Location: ./application/models/plugins_model.php */