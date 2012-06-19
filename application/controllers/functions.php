<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Functions extends Public_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('theme_model', 'theme');
		$this->load->helper('cookie');
		$this->load->helper('number');
	}


	public function theme($theme = 'default', $style = '')
	{
		$this->theme->set_title(__('Changing Theme Settings'));

		if (!in_array($theme, $this->theme->get_available_themes()))
			$theme = 'default';

		$this->input->set_cookie('foolfuuka_theme', $theme, 31536000, NULL, '/');
		if ($style !== '' && in_array($style, $this->theme->get_available_styles($theme)))
		{
			$this->input->set_cookie('foolfuuka_theme_' . $theme . '_style', $style, 31536000, NULL, '/');
		}

		if ($this->agent->referrer()) :
			$this->theme->bind('url', $this->agent->referrer());
		else :
			$this->theme->bind('url', site_url());
		endif;
		$this->theme->set_layout('redirect');
		$this->theme->build('redirection');
	}


	public function language($lang = 'en_EN')
	{
		$this->theme->set_title(__('Changing Software Language'));
		$this->input->set_cookie('foolfuuka_language', $lang, 31536000, NULL, '/');
		if ($this->agent->referrer()) :
			$this->theme->bind('url', $this->agent->referrer());
		else :
			$this->theme->bind('url', site_url());
		endif;
		$this->theme->set_layout('redirect');
		$this->theme->build('redirection');
	}


	public function opensearch()
	{
		header("Content-Type: text/xml");
		$this->load->view('opensearch');
	}

}
