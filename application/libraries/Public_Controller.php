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

		// Set theme by using the theme variable
		$this->fu_theme = (get_setting('fs_theme_dir') ? get_setting('fs_theme_dir') : 'default');
		if($this->input->cookie('foolfuuka_theme') && in_array($this->input->cookie('foolfuuka_theme'), array('default', 'fuuka', 'yotsuba')))
		{
			$this->fu_theme = $this->input->cookie('foolfuuka_theme');
		}

		$this->template->set_theme($this->fu_theme);

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