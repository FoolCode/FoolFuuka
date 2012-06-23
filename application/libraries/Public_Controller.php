<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Public_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		// fu is a general variable containing things like cookies
		if(!isset($this->fu))
		{
			$this->fu = new stdClass();
		}

		// get the password needed for the reply field
		if($this->input->cookie('foolfuuka_reply_password') == FALSE || strlen($this->input->cookie('foolfuuka_reply_password')) < 3)
		{
			// create a new random password
			$this->load->helper('string');
			$rand_pass = random_string('alnum', 16);
			$this->input->set_cookie('foolfuuka_reply_password', $rand_pass, 60*60*24*30);
			$this->fu->reply_password = $rand_pass;
		}
		else
		{
			$this->fu->reply_password = $this->input->cookie('foolfuuka_reply_password');
		}

		$this->fu->reply_email = '';
		$this->fu->reply_name = '';
		
		if($this->input->cookie('foolfuuka_reply_email') != FALSE)
		{
			$this->fu->reply_email = $this->input->cookie('foolfuuka_reply_email');
		}

		if($this->input->cookie('foolfuuka_reply_name') != FALSE)
		{
			$this->fu->reply_name = $this->input->cookie('foolfuuka_reply_name');
		}

		$this->load->model('theme_model', 'theme');

		// give an error if there's no active themes
		if(count($this->theme->get_available_themes()) == 0)
		{
			show_error(__('No themes enabled!'), 500);
		}
		
		
		
		if ($this instanceof REST_Controller)
		{
			$selected_theme = FALSE;
			
			if ($this->input->get_post('theme'))
			{
				$selected_theme = $this->input->get_post('theme');
			}
			else
			{
				$assoc = $this->uri->uri_to_assoc(4);
				
				if(isset($assoc['theme']))
				{
					$selected_theme = $assoc['theme'];
				}
			}
		}
		else 
		{
			$selected_theme = get_setting('fs_theme_default', FOOL_THEME_DEFAULT);
			
			if($this->input->cookie('foolfuuka_theme'))
			{
				$selected_theme = $this->input->cookie('foolfuuka_theme');
			}
		}

		$this->theme->set_theme($selected_theme);
	}

}