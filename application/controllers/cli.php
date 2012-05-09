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
	
	function _println($text)
	{
		$this->_print($text);
		echo PHP_EOL;
	}
	
	function _print($text)
	{
		echo $text;
	}
	
	function _error($error)
	{
		$this->_print('[error] ');
		switch($error)
		{
			case '_parameter_missing':
				$this->_println(__("Your request is missing parameters."));
				break;
			case '_parameter_board':
				$this->_println(__("Your request is missing parameters: add the board shortname."));
				break;
			case '_parameter_board_exist':
				$this->_println(__("The board you selected doesn't exist."));
				break;
			default:
				$this->_println($error);
		}
	}
	
	function ping()
	{
		$this->_println('pong.');
	}
	
	/**
	 * Collection of tools that run heavy modifications of database
	 * 
	 * - create _search table
	 * - drop _search table 
	 */
	function database()
	{
		// get the segments
		$parameters = func_get_args();

		switch($parameters[0])
		{
			// create the _search table for a specific board
			case 'create_search':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$result = $this->radix->create_search($board);
				break;
				
			case 'drop_search':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$result = $this->radix->remove_search($board);
				break;
		}
		
		// always give a response
		if (isset($result['error']))
		{
			$this->_error($result['error']);
		}
		else if (isset($result['success']))
		{
			$this->_println($result['success']);
		}
		else
		{
			$this->_println(__('Done.'));
		}
	}


	function stats_cron()
	{
		$this->load->model('statistics_model', 'statistics');
		$done = FALSE;

		while (!$done)
		{
			$this->statistics->cron();
			sleep(30);
		}
	}


	function statistics($board = NULL)
	{
		$this->load->model('statistics_model', 'statistics');

		$this->statistics->cron($board);
	}

	
	
	function asagi_get_settings()
	{
		$this->load->model('asagi_model', 'asagi');
		
		echo json_encode($this->asagi->get_settings()).PHP_EOL;
	}

}
