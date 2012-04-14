<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Cli extends MY_Controller
{
	function __construct()
	{
		parent::__construct();

		if (!$this->input->is_cli_request())
		{
			return FALSE;
		}
	}


	function stats_cron()
	{
		$this->load->model('statistics');
		$done = FALSE;

		while (!$done)
		{
			$this->statistics->cron();
			sleep(30);
		}
	}


	function statistics($board = NULL)
	{
		$this->load->model('statistics');

		$this->statistics->cron($board);
	}

	
	
	function asagi_get_settings()
	{
		$this->load->model('asagi');
		
		echo json_encode($this->asagi->get_settings()).PHP_EOL;
	}

}
