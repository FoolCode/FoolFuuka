<?php

namespace Foolz\Foolfuuka\Plugins\BoardStatistics\Model;

use \Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics as BS;
use \Foolz\Foolframe\Model\DoctrineConnection as DC;

class Task
{

	public static function cliBoardStatisticsHelp()
	{
		\Cli::write('  --Board Statistics command list:');
		\Cli::write('    cron                        Create statistics for all the boards in a loop');
		\Cli::write('    cron [board_shortname]      Create statistics for the selected board');
	}


	public static function cli_board_statistics($result)
	{
		$parameters = $result->getParam('parameters');

		switch ($parameters[0])
		{
			case 'cron':
				if (isset($parameters[1]))
				{
					if (\Radix::getByShortname($parameters[1]) !== false)
					{
						static::board_statistics($parameters[1]);
						return true;
					}
				}
				else
				{
					static::board_statistics();
				}
				break;
			default:
				\Cli::write(__('Bad command.'));
				return false;
		}
	}

	public static function board_statistics($shortname = null)
	{
		$boards = \Radix::getAll();

		$available = BS::getAvailableStats();

		while(true)
		{
			// Obtain all of the statistics already stored on the database to check for update frequency.
			$stats = DC::qb()
				->select('board_id, name, timestamp')
				->from(DC::p('plugin_fu_board_statistics'), 'bs')
				->orderBy('timestamp', 'desc')
				->execute()
				->fetchAll();

			// Obtain the list of all statistics enabled.
			$avail = [];
			foreach ($available as $k => $a)
			{
				// get only the non-realtime ones
				if (isset($available['frequency']))
				{
					$avail[] = $k;
				}
			}

			foreach ($boards as $board)
			{
				if ( ! is_null($shortname) && $shortname != $board->shortname)
				{
					continue;
				}

				// Update all statistics for the specified board or current board.
				\Cli::write($board->shortname . ' (' . $board->id . ')');
				foreach ($available as $k => $a)
				{
					\Cli::write('  ' . $k . ' ');
					$found = false;
					$skip = false;

					foreach ($stats as $r)
					{
						// Determine if the statistics already exists or that the information is outdated.
						if ($r['board_id'] == $board->id && $r['name'] == $k)
						{
							// This statistics report has run once already.
							$found = true;

							if ( ! isset($a['frequency']))
							{
								$skip = true;
								continue;
							}

							// This statistics report has not reached its frequency EOL.
							if (time() - strtotime($r['timestamp']) <= $a['frequency'])
							{
								$skip = true;
								continue;
							}
							break;
						}
					}

					// racing conditions with our cron.
					if ($found === false)
					{
						BS::saveStat($board->id, $k, date('Y-m-d H:i:s', time() + 600), '');
					}

					// We were able to obtain a LOCK on the statistics report and has already reached the
					// targeted frequency time.
					if ($skip === false)
					{
						\Cli::write('* Processing...');
						$process = 'process'.static::lowercaseToClassName($k);
						$result = BS::$process($board);

						// This statistics report generates a graph via GNUPLOT.
						if (isset($available[$k]['gnuplot']) && is_array($result) && ! empty($result))
						{
							BS::graphGnuplot($board->shortname, $k, $result);
						}

						// Save the statistics report in a JSON array.
						BS::saveStat($board->id, $k, date('Y-m-d H:i:s'), $result);
					}
				}
			}

			sleep(10);
		}
	}

	/**
	 * Reformats a lowercase string to a class name by splitting on underscores and capitalizing
	 *
	 * @param  string  $class_name  The name of the class, lowercase and with words separated by underscore
	 *
	 * @return  string
	 */
	public static function lowercaseToClassName($class_name)
	{
		$pieces = explode('_', $class_name);

		$result = '';
		foreach ($pieces as $piece)
		{
			$result .= ucfirst($piece);
		}

		return $result;
	}
}