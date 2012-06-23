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
			show_404();
		}
		
		cli_notice('notice', sprintf(__('Welcome to {{FOOL_NAME}} version %s'), FOOL_VERSION));
		cli_notice('notice', __('Write "php index.php cli help" to display all the available command line functions.'));
	}
	
	
	function _error($error)
	{
		switch($error)
		{
			case '_parameter_missing':
				cli_notice('error', __("Your request is missing parameters."));
				break;
			case '_parameter_board':
				cli_notice('error', __("Your request is missing parameters: add the board shortname."));
				break;
			case '_parameter_board_exist':
				cli_notice('error', __("The board you selected doesn't exist."));
				break;
			default:
				cli_notice('error', $error);
		}
	}
	
	/**
	 * Check if the command line works? 
	 */
	function ping()
	{
		cli_notice('notice', 'pong.');
	}
	
	/**
	 * Display the sections available 
	 */
	function help()
	{
		cli_notice('notice', '');
		cli_notice('notice', 'Available sections:');
		cli_notice('notice', 'php index.php cli ...');
		cli_notice('notice', '    database [help]      Display the database functions available');
		cli_notice('notice', '    boards [help]         Display the functions related to the boards');
		cli_notice('notice', '    asagi [help]         Display the functions related to the Asagi fetcher');
		cli_notice('notice', '    cron [help]          Display the long-running functions available');

		$this->plugins->run_hook('fu_cli_controller_after_help', array(), 'simple');

		cli_notice('notice', '    ping                 Pong');
	}
	
	/**
	 * Collection of tools that run heavy modifications of database
	 */
	function database()
	{	
		cli_notice('notice', __('Write "php index.php cli database help" for displaying the available commands for database manipulation.'));
		
		// get the segments
		$parameters = func_get_args();

		// redirect to help if there's no parameters
		if(!isset($parameters[0]))
		{
			$parameters[0] = 'help';
		}
		
		switch($parameters[0])
		{
			case 'help':
				cli_notice('notice', '');
				cli_notice('notice', 'Command list:');
				cli_notice('notice', 'php index.php cli database ...');
				cli_notice('notice', '    create_search <board_shortname>             Creates the _search table necessary if you don\'t have SphinxSearch');
				cli_notice('notice', '    drop_search <board_shortname>               Drops the _search table, good idea if you don\'t need it anymore after implementing SphinxSearch');
				cli_notice('notice', '    create_extra <board_shortname>              Creates the _extra table for the board');
				cli_notice('notice', '    mysql_convert_utf8mb4 <board_shortname>     Converts the MySQL tables to support 4byte characters that otherwise get ignored.');
				cli_notice('notice', '    recreate_triggers <board_shortname>         Recreate triggers for the selected board.');
				cli_notice('notice', '    recheck_banned [<board_shortname>]          Try deleting banned images, if there\'s any left.');
				break;
				
			// create the _search table for a specific board
			case 'create_search':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$result = $this->radix->create_search($board);
				break;
				
			// drop the search table for a specific board
			case 'drop_search':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$result = $this->radix->remove_search($board);
				break;
				
			// create the _search table for a specific board
			case 'create_extra':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$result = $this->radix->mysql_create_extra($board);
				break;
				
			// convert a specific board to utf8mb4
			case 'mysql_convert_utf8mb4':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$result = $this->radix->mysql_change_charset($board);
				break;
				
			case 'recreate_triggers':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$this->radix->mysql_remove_triggers($board);
				$this->radix->mysql_create_triggers($board);
				break;
				
			case 'recheck_banned':
				if(isset($parameters[1]))
				{
					$board = $this->radix->get_by_shortname($parameters[1]);
					if(!$board)
						return $this->_error('_parameter_board_exist');
				}
				else 
				{
					$board = FALSE;
				}
				$this->load->model('post_model', 'post');
				$this->post->recheck_banned($board);
				break;
				
		}
		
		// always give a response
		if (isset($result['error']))
		{
			cli_notice('error', $result['error']);
		}
		else if (isset($result['success']))
		{
			cli_notice('notice', $result['success']);
		}
	}
	
	
	function boards()
	{	
		cli_notice('notice', __('Write "php index.php cli boards help" for displaying the available commands for board manipulation.'));
		
		// get the segments
		$parameters = func_get_args();

		// redirect to help if there's no parameters
		if(!isset($parameters[0]))
		{
			$parameters[0] = 'help';
		}
		
		switch($parameters[0])
		{
			case 'help':
				cli_notice('notice', '');
				cli_notice('notice', 'Command list:');
				cli_notice('notice', 'php index.php cli database ...');
				cli_notice('notice', '    set <board> <name> <value>        Changes a setting for the board, no <value> means NULL (ATTN: no value validation)');
				cli_notice('notice', '    mass_set <set> <name> <value>     Changes a setting for every board, no <value> means NULL (ATTN: no value validation)');
				cli_notice('notice', '                                      <set> can be \'archives\', \'boards\' or \'all\'');
				cli_notice('notice', '    remove_leftover_dirs              Removes the _removed directories');
				break;
			
			case 'set':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				if(!isset($parameters[2]))
					return $this->_error('Your request is missing parameters: <name>');
				$parameters[3] = isset($parameters[3])?$parameters[3]:NULL;
				$this->radix->save(array('id' => $board->id, $parameters[2] => $parameters[3]));
				break;
				
			case 'mass_set':
				if(!isset($parameters[1]) || !in_array($parameters[1], array('archives', 'boards', 'all')))
					return $this->_error(__("You must choose between 'archives', 'boards' or 'all'."));
				if($parameters[1] == 'all')
					$board = $this->radix->get_all();
				else if ($parameters[1] == 'boards')
					$board = $this->radix->get_archives();
				else if ($parameters[1] == 'archives')
					$board = $this->radix->get_boards();
				else return FALSE;
				if(!isset($parameters[2]))
					return $this->_error('Your request is missing parameters: <name>');
				$parameters[3] = isset($parameters[3])?$parameters[3]:NULL;
				foreach($board as $b)
					$this->radix->save(array('id' => $b->id, $parameters[2] => $parameters[3]));
				break;
				
			case 'remove_leftover_dirs':
				// TRUE echoes the removed files
				$this->radix->remove_leftover_dirs(TRUE);
				break;
		}
		
		// always give a response
		if (isset($result['error']))
		{
			cli_notice('error', $result['error']);
		}
		else if (isset($result['success']))
		{
			cli_notice('notice', $result['success']);
		}
	}
	
	
	function asagi_get_settings()
	{	
		$this->load->model('asagi_model', 'asagi');
		
		echo json_encode($this->asagi->get_settings()) . PHP_EOL;
	}
	
}
