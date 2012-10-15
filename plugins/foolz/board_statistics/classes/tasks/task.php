<?php

namespace Foolfuuka\Plugins\Board_Statistics;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Task
{

	public static function cli_board_statistics_help()
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
					if (\Radix::get_by_shortname($parameters[1]) !== false)
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
		$boards = \Radix::get_all();

		$available = Board_Statistics::get_available_stats();

		while(true)
		{
			// Obtain all of the statistics already stored on the database to check for update frequency.
			$stats = \DB::select('board_id', 'name', 'timestamp')
				->from('plugin_fu-board-statistics')
				->order_by('timestamp', 'desc')
				->as_object()
				->execute()
				->as_array();

			// Obtain the list of all statistics enabled.
			$avail = array();
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
				if (!is_null($shortname) && $shortname != $board->shortname)
				{
					continue;
				}

				// Update all statistics for the specified board or current board.
				\Cli::write($board->shortname . ' (' . $board->id . ')');
				foreach ($available as $k => $a)
				{
					\Cli::write('  ' . $k . ' ');
					$found = FALSE;
					$skip = FALSE;

					foreach ($stats as $r)
					{
						// Determine if the statistics already exists or that the information is outdated.
						if ($r->board_id == $board->id && $r->name == $k)
						{
							// This statistics report has run once already.
							$found = TRUE;

							if( ! isset($a['frequency']))
							{
								$skip = TRUE;
								continue;
							}

							// This statistics report has not reached its frequency EOL.
							if (time() - strtotime($r->timestamp) <= $a['frequency'])
							{
								$skip = TRUE;
								continue;
							}

							// This statistics report has another process locked.
							if ( ! Board_Statistics::lock_stat($r->board_id, $k, $r->timestamp))
							{
								continue;
							}
							break;
						}
					}

					// racing conditions with our cron.
					if ($found === FALSE)
					{
						Board_Statistics::save_stat($board->id, $k, date('Y-m-d H:i:s', time() + 600), '');
					}

					// We were able to obtain a LOCK on the statistics report and has already reached the
					// targeted frequency time.
					if ($skip === FALSE)
					{
						\Cli::write('* Processing...');
						$process = 'process_' . $k;
						$result = Board_Statistics::$process($board);

						// This statistics report generates a graph via GNUPLOT.
						if (isset($available[$k]['gnuplot']) && is_array($result) && !empty($result))
						{
							Board_Statistics::graph_gnuplot($board->shortname, $k, $result);
						}

						// Save the statistics report in a JSON array.
						Board_Statistics::save_stat($board->id, $k, date('Y-m-d H:i:s'), $result);
					}
				}
			}

			sleep(10);
		}
	}

}