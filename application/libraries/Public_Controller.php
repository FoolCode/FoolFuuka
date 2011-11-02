<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Public_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		// We need to set some theme stuff, so let's load the template system
		$this->load->library('template');

		$this->config->load('theme');

		// Set theme by using the theme variable
		$this->template->set_theme((get_setting('fs_theme_dir') ? get_setting('fs_theme_dir') : 'default'));

		// load the controller from the current theme, else load the default one
		if (file_exists('content/themes/' . get_setting('fs_theme_dir') . '/theme_controller.php'))
		{
			require_once('content/themes/' . get_setting('fs_theme_dir') . '/theme_controller.php');
		}
		else
		{
			require_once('content/themes/' . $this->config->item('theme_extends') . '/theme_controller.php');
		}
		$this->TC = new Theme_Controller();

		// load the functions from the current theme, else load the default one
		if (file_exists('content/themes/' . get_setting('fs_theme_dir') . '/theme_functions.php'))
		{
			require_once('content/themes/' . get_setting('fs_theme_dir') . '/theme_functions.php');
		}
		else
		{
			require_once('content/themes/' . $this->config->item('theme_extends') . '/theme_functions.php');
		}
	}


}