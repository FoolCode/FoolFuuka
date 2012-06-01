<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * FoOlFrame Theme Model
 *
 * The Theme Model puts together the public interface. It allows fallback
 * a-la-wordpress child themes. It also allows using the Plugin Model to
 * fully costumize controller and models for each theme.
 *
 * @package        	FoOlFrame
 * @subpackage    	Models
 * @category    	Models
 * @author        	Woxxy
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Theme_model extends CI_Model
{

	/**
	 * The theme configurations that are loaded
	 * 
	 * @var array associative array('theme_dir' => array('item' => 'value'));
	 */
	private $_loaded = array();

	/**
	 * If get_all() was used, this will be true and themes won't be checked again
	 * 
	 * @var array 
	 */
	private $_is_all_loaded = FALSE;

	/**
	 * The name of the selected theme
	 * 
	 * @var string|bool the folder name of the theme or FALSE if not set 
	 */
	private $_selected_theme = FALSE;

	/**
	 * The reference to the theme controller
	 * 
	 * @var object|bool the object of the theme controller or FALSE if there's none 
	 */
	private $_theme_controller = FALSE;

	/**
	 * The selected layout
	 * 
	 * @var string|bool FALSE when not choosen 
	 */
	private $_selected_layout = FALSE;
	
	/**
	 * The selected partials
	 * 
	 * @var array keys as the name of the partial and the value an array of set variables
	 */
	private $_selected_partials = array();

	/**
	 * Variables available to all views
	 * 
	 * @var array 
	 */
	private $_view_variables = array();

	/**
	 * The string separating pieces of the <title>
	 * 
	 * @var string 
	 */
	private $_title_separator = '»';

	/**
	 * The breadcrumbs of which the title is composed
	 * 
	 * @var array 
	 */
	private $_title = array();
	
	/**
	 * The lines of metadata to print
	 * 
	 * @var array 
	 */
	private $_metadata = array();


	/**
	 * No special functionality 
	 */
	public function __construct()
	{
		parent::__construct();
	}


	/**
	 * Returns all the themes available and saves the array in a variable
	 * 
	 * @return array array with the theme name as index, and their config as value
	 */
	public function get_all()
	{
		if ($this->_is_all_loaded)
			return $this->_loaded;

		$array = array();

		foreach ($this->get_all_names() as $name)
		{
			$array[$name] = $this->load_config($name);
		}

		$this->_is_all_loaded = TRUE;
		$this->_loaded = $array;

		return $array;
	}


	/**
	 * Get the config array of a single theme
	 * 
	 * @param type $name
	 * @return type 
	 */
	public function get_by_name($name)
	{
		if (isset($this->_loaded[$name]))
			return $this->_loaded[$name];

		$this->_loaded[$name] = $this->load_config($name);

		return $this->_loaded[$name];
	}
	
	
	/**
	 * Returns an array of key => value of the available themes
	 */
	public function get_available_themes()
	{
		if($this->tank_auth->is_allowed())
		{
			// admins get all the themes
			return array_keys($this->get_all());
		}
		else
		{
			$active_themes = get_setting('fs_theme_active_themes');
			if(!$active_themes || !$active_themes = @unserialize($active_themes))
			{
				// default WORKING themes coming with the application
				return array(
					'default', 
					'tanline', 
					'fuuka'
				);
			}
			else
			{
				foreach($active_themes as $key => $enabled)
				{
					if(!$enabled)
					{
						unset($active_themes[$key]);
					}
				}
				
				return $active_themes = array_keys($active_themes);
			}
		}
	}

	/**
	 * Gets a config setting from the selected theme
	 * 
	 * @param type $name 
	 */
	public function get_selected_theme()
	{
		return $this->_selected_theme;
	}


	/**
	 * Gets a config setting from the selected theme
	 * 
	 * @param type $name 
	 */
	public function get_config($name)
	{
		return $this->_loaded[$this->_selected_theme][$name];
	}


	/**
	 * Browses the theme directory and grabs all the folder names
	 * 
	 * @return type 
	 */
	public function get_all_names()
	{
		$array = array();

		if ($handle = opendir('content/themes/'))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (in_array($file, array('..', '.')))
					continue;

				if (is_dir('content/themes/' . $file))
				{
					$array[] = $file;
				}
			}
			closedir($handle);
		}

		return $array;
	}


	/**
	 * Opens theme_config and grabs the $config
	 * 
	 * @param string $name the folder name of the theme
	 * @return array the config array or FALSE if not found
	 */
	private function load_config($name)
	{
		if (file_exists('content/themes/' . $name . '/theme_config.php'))
		{
			include 'content/themes/' . $name . '/theme_config.php';
			if (!isset($config))
				return FALSE;

			return $config;
		}
		else
		{
			return FALSE;
		}
	}


	/**
	 * Checks if the theme is available to the rules and loads it
	 * 
	 * @param string $theme
	 * @return array the theme config 
	 */
	public function set_theme($theme)
	{
		if(!in_array($theme, $this->get_available_themes()))
		{
			$theme = FOOL_THEME_DEFAULT;
		}
		
		$result = $this->get_by_name($theme);
		$this->_selected_theme = $theme;
		
		// load the theme functions if there is such a file
		$theme_functions_file = 'content/themes/' . $theme . '/theme_functions.php';
		if(file_exists($theme_functions_file))
		{
			require_once $theme_functions_file;
		}
		
		// load the theme plugin file if present
		$theme_plugin_file = 'content/themes/' . $theme . '/theme_plugin.php';
		if(file_exists($theme_plugin_file))
		{
			$this->plugins->inject_plugin('theme', 'Theme_Plugin_' . $theme, TRUE, $theme_plugin_file);
		}
		
		return $result;
	}


	/**
	 * Selects the layout to use 
	 * 
	 * @param string $layout the filename without .php extension
	 */
	public function set_layout($layout)
	{
		$this->_selected_layout = $layout;
	}


	/**
	 * Sets a partial view
	 * 
	 * @param type $partial
	 * @param type $data 
	 */
	public function set_partial($name, $partial, $data = array())
	{
		$this->_selected_partials[$name] = array('partial' => $partial, 'data' => $data);
	}


	/**
	 * Sets a variable that is globally avariable through layout and partials
	 * 
	 * @param type $name
	 * @param type $value 
	 */
	public function bind($name, $value)
	{
		$this->_view_variables[$name] = $value;
	}


	/**
	 * Unsets a variable that is globally avariable through layout and partials
	 * 
	 * @param string $name
	 * @param any $value 
	 */
	public function unbind($name, $value)
	{
		unset($this->_view_variables[$name]);
	}

	/**
	 * Adds breadcrumbs to the title
	 * 
	 * @param string|array if array it will set the title array from scratch
	 * @return array the title array
	 */
	public function set_title($title)
	{
		if(is_array($title))
			$this->_title = $title;
		else
			$this->_title[] = $title;
		
		return $this->_title;
	}
	
	/**
	 * Adds metadata to header
	 * 
	 * @param string|array if array it will set the metadata array from scratch
	 * @return array the metadata array
	 */
	public function set_metadata($metadata)
	{
		if(is_array($metadata))
			$this->_metadata = $metadata;
		else
			$this->_metadata[] = $metadata;
		
		return $this->_metadata;
	}
	
	
	/**
	 * Provides the path to the asset and in case its fallback.
	 * 
	 * @param type $asset the location of the asset with theme folder as root
	 * @return string The location of the asset in the theme folder
	 */
	public function fallback_asset($asset)
	{
		$asset = ltrim($asset, '/');
		if (file_exists('content/themes/' . $this->_selected_theme . '/' . $asset ))
		{
			return 'content/themes/' . $this->_selected_theme . '/' . $asset . '?v=' . FOOL_VERSION;
		}
		else
		{
			return 'content/themes/' . $this->get_config('extends') . '/' . $asset . '?v=' . FOOL_VERSION;
		}
	}
	
	/**
	 *
	 * @param type $asset
	 * @return type 
	 */
	public function fallback_override($asset, $double = FALSE)
	{
		// if we aren't going to have stuff like two CSS overrides, return the theme's file
		if(!$double || $this->get_config('extends') == $this->_selected_theme)
		{
			return array($this->fallback_asset($asset));
		}
		
		// we want first extended theme and then the override
		return array(
			'content/themes/' . $this->get_config('extends') . '/' . $asset . '?v=' . FOOL_VERSION,
			'content/themes/' . $this->_selected_theme . '/' . $asset . '?v=' . FOOL_VERSION
		);
	}
	

	/**
	 * Wraps up all the choices and returns or outputs the HTML
	 *
	 * @param string $view the content to insert in the layout
	 * @param array $data key value instead of using bind()
	 * @param bool $return TRUE to return the HTML as string
	 * @return string the HTML
	 */
	public function build($view, $data = array(), $return = FALSE, $without_layout = FALSE)
	{
		foreach ($data as $key => $item)
		{
			$this->bind($key, $item);
		}

		// build the partials
		$partials = array();
		foreach ($this->_selected_partials as $name => $partial)
		{
			$partials[$name] = $this->_build(
				$partial['partial'], 
				'partial', 
				array_merge($this->_view_variables, $partial['data'])
			);
		}

		// build the content that goes in the middle
		$content = $this->_build(
			$view, 
			'content', 
			array_merge($this->_view_variables, array('template' => array('partials' => $partials)))
		);
		
		// if there's no selected layout output or return this
		if($without_layout || $this->_selected_layout === FALSE)
		{
			if($return)
				return $content;
			
			return $this->output->append_output($content);
		}

		// build the layout
		$html = $this->_build(
			$this->_selected_layout, 
			'layout', 
			array_merge(
				$this->_view_variables, 
				array('template' => array(
						'body' => $content, 
						'title' => implode($this->_title_separator, $this->_title), 
						'partials' => $partials,
						'metadata' => implode("\n", $this->_metadata)
					)
				)
			)
		);

		if($return)
			return $html;
		
		return $this->output->append_output($html);
	}


	/**
	 * Merges variables and view and returns the HTML as string
	 * 
	 * @param string $file
	 * @param string $type
	 * @param array $data 
	 * @return string
	 */
	private function _build($_file, $_type, $_data = array())
	{
		foreach (array($this->get_selected_theme(), $this->get_config('extends')) as $_directory)
		{
			switch ($_type)
			{
				case 'layout':
					if (file_exists('content/themes/' . $_directory . '/views/layouts/' . $_file . '.php'))
					{
						$_location = 'content/themes/' . $_directory . '/views/layouts/' . $_file . '.php';
					}
					break;
				case 'content':
				case 'partial':
					if (file_exists('content/themes/' . $_directory . '/views/' . $_file . '.php'))
					{
						$_location = 'content/themes/' . $_directory . '/views/' . $_file . '.php';
					}
					break;
			}
			if (isset($_location))
				break;
		}

		// get rid of interfering variables 
		unset($_type, $_file);

		extract($_data);

		ob_start();
		include $_location;
		$string = ob_get_clean();
		return $string;
	}

}

/* End of file theme_model.php */
/* Location: ./application/models/theme_model.php */