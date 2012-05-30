<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Functions extends Public_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('theme_controller', 'theme');
		$this->load->helper('cookie');
		$this->load->helper('number');
	}


	public function theme($theme = 'default')
	{
		$this->theme->set_title(__('Changing Theme'));
		$this->input->set_cookie('foolfuuka_theme', $theme, 31536000);
		if ($this->input->server('HTTP_REFERER') && strpos($this->agent->referrer(), site_url()) === 0) :
			$this->theme->set('url', $this->input->server('HTTP_REFERER'));
		else :
			$this->theme->set('url', site_url());
		endif;
		$this->theme->set_layout('redirect');
		$this->theme->build('redirection');
	}
	
	public function language($lang = 'en_EN')
	{
		$this->theme->set_title(__('Changing Language'));
		$this->input->set_cookie('foolfuuka_language', $lang, 31536000);
		if ($this->input->server('HTTP_REFERER') && strpos($this->agent->referrer(), site_url()) === 0) :
			$this->theme->set('url', $this->input->server('HTTP_REFERER'));
		else :
			$this->theme->set('url', site_url());
		endif;
		$this->theme->set_layout('redirect');
		$this->theme->build('redirection');
	}

}
