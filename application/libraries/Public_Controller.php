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

		$this->load->model('theme_model', 'theme');
		
		// give an error if there's no active themes
		if(count($this->theme->get_available_themes()) == 0)
		{
			show_error(__('No themes enabled!'), 500);
		}
		
		$selected_theme = get_setting('fs_theme_default', FOOL_THEME_DEFAULT);
		if($this->input->cookie('foolfuuka_theme'))
		{
			$selected_theme = $this->input->cookie('foolfuuka_theme');
		}

		$this->theme->set_theme($selected_theme);
	}

}