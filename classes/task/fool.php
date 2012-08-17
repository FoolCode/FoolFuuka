<?php

namespace Foolfuuka\Tasks;

class Fool
{
	public function run()
	{
		\Cli::write('--'.__('FoolFuuka module management.'));
		$section = \Cli::prompt('  '.__('Select the section.'), array('database', 'boards'));
		
		$this->{'cli_'.$section.'_help'}();
		
		$done = false;
		while(!$done)
		{
			$done = true;
			$result = \Cli::prompt(__('Choose the method to run'));
			$parameters = explode(' ', $result);
			$done = $this->{'cli_'.$section}($parameters);
		}
		
		\Cli::write(__('Goodbye.'));
	}
	
	public function cli_database_help()
	{
		\Cli::write('  --'.__('FoolFrame database management commands'));
		
		\Cli::write('    create_search <board_shortname>             Creates the _search table necessary if you don\'t have SphinxSearch');
		\Cli::write('    drop_search <board_shortname>               Drops the _search table, good idea if you don\'t need it anymore after implementing SphinxSearch');
		\Cli::write('    create_extra <board_shortname>              Creates the _extra table for the board');
		\Cli::write('    mysql_convert_utf8mb4 <board_shortname>     Converts the MySQL tables to support 4byte characters that otherwise get ignored.');
		\Cli::write('    recreate_triggers <board_shortname>         Recreate triggers for the selected board.');
		\Cli::write('    recheck_banned [<board_shortname>]          Try deleting banned images, if there\'s any left.');
	}
	
	public function cli_database($parameters)
	{
		switch($parameters[0])
		{
			// create the _search table for a specific board
			case 'create_search':
			case 'drop_search':
			case 'create_extra':
			case 'mysql_convert_utf8mb4':
			case 'recreate_triggers':
				if(!isset($parameters[1]))
				{
					\Cli::write(__('Missing parameter.'));
					return false;
				}
				$board = \Radix::get_by_shortname($parameters[1]);
				if(!$board)
				{
					\Cli::write(__('Board doesn\'t exist.'));
					return false;
				}
				if ($parameters[0] == 'create_search')
					\Radix::create_search($board);
				if ($parameters[0] == 'remove_search')
					\Radix::remove_search($board);
				if ($parameters[0] == 'create_extra')
					\Radix::mysql_create_extra($board);
				if ($parameters[0] == 'mysql_convert_utf8mb4')
					\Radix::mysql_change_charset($board);
				if ($parameters[0] == 'recreate_triggers')
				{
					\Radix::mysql_remove_triggers($board);
					\Radix::mysql_create_triggers($board);
				}
				break;

			case 'recheck_banned':
				if(isset($parameters[1]))
				{
					$board = \Radix::get_by_shortname($parameters[1]);
					if(!$board)
					{
						\Cli::write(__('Board doesn\'t exist.'));
						return false;
					}
				}
				else 
				{
					$board = false;
				}
				\Board::recheck_banned($board);
				break;

			default:
				\Cli::write(__('Bad command.'));
				return false;
		}
		
		return true;
	}
	
	public function cli_boards_help()
	{
		\Cli::write('  --'.__('FoolFrame board management commands'));

		\Cli::write('    set <board> <name> <value>        Changes a setting for the board, no <value> means NULL (ATTN: no value validation)');
		\Cli::write('    mass_set <set> <name> <value>     Changes a setting for every board, no <value> means NULL (ATTN: no value validation)');
		\Cli::write('                                      <set> can be \'archives\', \'boards\' or \'all\'');
		\Cli::write('    remove_leftover_dirs              Removes the _removed directories');
	}
	
	public function cli_boards($parameters)
	{
		switch($parameters[0])
		{
			case 'set':
				if(!isset($parameters[1]))
				{
					\Cli::write(__('Missing parameter.'));
					return false;
				}
				$board = \Radix::get_by_shortname($parameters[1]);
				if(!$board)
				{
					\Cli::write(__('Board doesn\'t exist.'));
				}
				if(!isset($parameters[1]))
				{
					\Cli::write(__('Your request is missing parameters: <name>'));
					return false;
				}
				$parameters[3] = isset($parameters[3])?$parameters[3]:NULL;
				\Radix::save(array('id' => $board->id, $parameters[2] => $parameters[3]));
				break;

			case 'mass_set':
				if(!isset($parameters[1]) || !in_array($parameters[1], array('archives', 'boards', 'all')))
				{
					\Cli::write(__("You must choose between 'archives', 'boards' or 'all'."));
					return false;
				}
				if($parameters[1] == 'all')
					$board = \Radix::get_all();
				else if ($parameters[1] == 'boards')
					$board = \Radix::get_archives();
				else if ($parameters[1] == 'archives')
					$board = \Radix::get_boards();
				else return false;
				if(!isset($parameters[2]))
				{
					\Cli::write(__('Your request is missing parameters: <name>'));
					return false;
				}
				$parameters[3] = isset($parameters[3])?$parameters[3]:NULL;
				foreach($board as $b)
					\Radix::save(array('id' => $b->id, $parameters[2] => $parameters[3]));
				break;

			case 'remove_leftover_dirs':
				// TRUE echoes the removed files
				\Radix::remove_leftover_dirs(TRUE);
				break;
			
			default:
				\Cli::write(__('Bad command.'));
				return false;
		}
	}
}	