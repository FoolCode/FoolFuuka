<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Public_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		// get the password needed for the reply field
		if($this->input->cookie('foolfuuka_reply_password') == FALSE || strlen($this->input->cookie('foolfuuka_reply_password')) < 3)
		{
			// create a new random password
			$this->load->helper('string');
			$rand_pass = random_string('alnum', 16);
			$this->input->set_cookie('foolfuuka_reply_password', $rand_pass, 60*60*24*30);
			$this->fu_reply_password = $rand_pass;
		}
		else
		{
			$this->fu_reply_password = $this->input->cookie('foolfuuka_reply_password');
		}

		if($this->input->cookie('foolfuuka_reply_email') != FALSE)
		{
			$this->fu_reply_email = $this->input->cookie('foolfuuka_reply_email');
		}
		else
		{
			$this->fu_reply_email = '';
		}

		if($this->input->cookie('foolfuuka_reply_name') != FALSE)
		{
			$this->fu_reply_name = $this->input->cookie('foolfuuka_reply_name');
		}
		else
		{
			$this->fu_reply_name = '';
		}

		// We need to set some theme stuff, so let's load the template system
		$this->load->library('template');

		$this->config->load('theme');

		// @todo: check directory when we support extra themes
		$all_themes = array('default', 'fuuka', 'yotsuba');
		
		if($this->tank_auth->is_allowed())
		{
			// admins get all the themes
			$active_themes = $all_themes;
		}
		else
		{
			$active_themes = get_setting('fs_theme_active_themes');
			if(!$active_themes || !$active_themes = @unserialize($active_themes))
			{
				// default themes coming with FoOlFuuka
				$active_themes = array('default', 'fuuka');
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
				$active_themes = array_keys($active_themes);
			}
		}
		
		// give an error if there's no active themes
		if(empty($active_themes))
		{
			show_error(_('No themes enabled!'), 500);
		}
		
		$this->fu_theme = get_setting('fs_theme_default', FOOL_THEME_DEFAULT);
		if($this->input->cookie('foolfuuka_theme') && in_array($this->input->cookie('foolfuuka_theme'), $active_themes))
		{
			$this->fu_theme = $this->input->cookie('foolfuuka_theme');
		}

		$this->template->set_theme($this->fu_theme);
		
		// let's get extra info on each theme and prepare an useable array of data
		$this->fu_available_themes = array();
		foreach($active_themes as $theme)
		{
			if (file_exists('content/themes/' . $theme . '/theme_config.php'))
			{
				include('content/themes/' . $theme . '/theme_config.php');
				$this->fu_available_themes[$theme] = $config; 
			}
		}
		
		// load the controller from the current theme, else load the default one
		if (file_exists('content/themes/' . $this->fu_theme . '/theme_controller.php'))
		{
			require_once('content/themes/' . $this->fu_theme . '/theme_controller.php');
		}
		else
		{
			require_once('content/themes/' . $this->config->item('theme_extends') . '/theme_controller.php');
		}
		$this->TC = new Theme_Controller();

		// load the functions from the current theme, else load the default one
		if (file_exists('content/themes/' . $this->fu_theme . '/theme_functions.php'))
		{
			require_once('content/themes/' . $this->fu_theme . '/theme_functions.php');
		}
		else
		{
			require_once('content/themes/' . $this->config->item('theme_extends') . '/theme_functions.php');
		}
	}


}