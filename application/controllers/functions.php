<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Functions extends Public_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('template');
		$this->load->helper('cookie');
		$this->load->helper('number');
	}


	public function theme($theme = 'default')
	{
		$this->template->title(_('Changing theme'));
		$this->input->set_cookie('foolfuuka_theme', $theme, 31536000);
		if ($this->input->server('HTTP_REFERER') && strpos($this->agent->referrer(), site_url()) === 0) :
			$this->template->set('url', $this->input->server('HTTP_REFERER'));
		else :
			$this->template->set('url', site_url());
		endif;
		$this->template->set_layout('redirect');
		$this->template->build('redirection');
	}
	
}
	