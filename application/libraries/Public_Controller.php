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
		$all_themes = array_keys($this->theme->get_all());
		
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
				// default WORKING themes coming with the application
				$active_themes = array('default', 'tanline', 'fuuka');
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
			show_error(__('No themes enabled!'), 500);
		}
		
		$selected_theme = get_setting('fs_theme_default', FOOL_THEME_DEFAULT);
		if($this->input->cookie('foolfuuka_theme') && in_array($this->input->cookie('foolfuuka_theme'), $active_themes))
		{
			$selected_theme = $this->input->cookie('foolfuuka_theme');
		}

		$this->theme->set_theme($selected_theme);
	}

}