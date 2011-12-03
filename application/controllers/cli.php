<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Cli extends MY_Controller
{
	function __construct()
	{
		parent::__construct();
		echo 'here';
		if(!$this->input->is_cli_request())
		{
			show_404();
		}
		
		$this->cron();
	}
	
	
	function cron()
	{
		$this->load->model('statistics');
		$done = FALSE;
		
		while(!$done)
		{
			$this->statistics->cron();
			sleep(30);
		}
	}
}