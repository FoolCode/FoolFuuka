<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Public_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		// if this is a load balancer FoOlSlide, disable the public interface
		if (get_setting('fs_balancer_master_url'))
		{
			show_404();
		}

		// We need to set some theme stuff, so let's load the template system
		$this->load->library('template');

		$this->config->load('theme');

		// Set theme by using the theme variable
		$this->template->set_theme((get_setting('fs_theme_dir') ? get_setting('fs_theme_dir') : 'default'));

		// load the controller from the current theme, else load the default one
		if (file_exists('content/themes/' . get_setting('fs_theme_dir') . '/reader_controller.php'))
		{
			require_once('content/themes/' . get_setting('fs_theme_dir') . '/reader_controller.php');
		}
		else
		{
			require_once('content/themes/' . $this->config->item('theme_extends') . '/reader_controller.php');
		}
		$this->RC = new Reader_Controller();

		// load the functions from the current theme, else load the default one
		if (file_exists('content/themes/' . get_setting('fs_theme_dir') . '/reader_functions.php'))
		{
			require_once('content/themes/' . get_setting('fs_theme_dir') . '/reader_functions.php');
		}
		else
		{
			require_once('content/themes/' . $this->config->item('theme_extends') . '/reader_functions.php');
		}
	}


}