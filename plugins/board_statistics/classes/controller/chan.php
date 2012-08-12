<?php

namespace Foolfuuka\Plugins\Board_Statistics;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Controller_Plugin_Fu_Board_Statistics_Chan extends \Controller_Chan
{
	/**
	 * @param null $report
	 */
	public function action_statistics($report = NULL)
	{
		// Load Statistics Model

		if (is_null($report))
		{
			$stats = $this->get_available_stats();

			// Set template variables required to build the HTML.
			$this->theme->set_title(Radix::get_selected()->formatted_title . ' &raquo; ' . __('Statistics'));
			Chan::_set_parameters(
				array(
					'section_title' => __('Statistics'),
					'is_statistics' => TRUE,
					'is_statistics_list' => TRUE,
					'info' => $stats
				), array(
					'tools_search' => TRUE
				)
			);
			
			ob_start();
			?>

			<div style="margin: 20px auto; width:960px;">
				<nav style="margin-top:20px;">
					<ul>
						<?php foreach ($stats as $key => $stat) : ?>
						<li>
							<a href="<?php echo Uri::create(array(Radix::get_selected()->shortname, 'statistics', $key)) ?>" title="<?php echo form_prep($stat['name']) ?>" ><?php echo $stat['name'] ?></a>
						</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			</div>

			<?php
			$string = ob_get_clean();
			$this->theme->build('plugin', array('content' => $string));
		}
		else
		{
			$stats = $this->check_available_stats($report, Radix::get_selected());

			if (!is_array($stats))
			{
				return get_instance()->show_404();
			}

			// Set template variables required to build the HTML.
			$this->load->helper('date');
			$this->theme->set_title(Radix::get_selected()->formatted_title . ' &raquo; '
				. __('Statistics') . ': ' . $stats['info']['name']);

			if (isset($stats['info']['frequency']))
			{
				$section_title = sprintf(__('Statistics: %s (Next Update in %s)'),
					$stats['info']['name'],
					timespan(time(), strtotime($stats['timestamp']) + $stats['info']['frequency'])
				);
			}
			else
			{
				$section_title = sprintf(__('Statistics: %s'), $stats['info']['name']);
			}
				
			Chan::_set_parameters(
				array(
					'section_title' => $section_title,
					'is_statistics' => TRUE,
					'is_statistics_list' => FALSE
				),
				array(
					'tools_search' => TRUE
				)
			);
			
			$data = $stats['data'];
			$info = $stats['info'];
			ob_start();
			?>
			<div style="margin: 20px auto; width:960px;">
			<?php
			include FCPATH . 'content/plugins/FU_Board_Statistics/views/' . $stats['info']['interface'] . '.php';
			?>
			</div> 
			<?php
			$string = ob_get_clean();
			$this->theme->build('plugin', array('content' => $string));
		}
	}
	
	
	function cli_help()
	{
		cli_notice('notice', '');
		cli_notice('notice', 'Command list:');
		cli_notice('notice', 'php index.php cli board_stats ...');
		cli_notice('notice', '    help                        Shows this help');
		cli_notice('notice', '    cron                        Create statistics for all the boards in a loop');
		cli_notice('notice', '    cron [board_shortname]      Create statistics for the selected board');
		
	}
	
	
	/**
	 * Run a cron to update the statistics of all boards.
	 *
	 * @param int $id
	 */
	function cli_cron($shortname = NULL)
	{
		$boards = Radix::get_all();

		$available = $this->get_available_stats();

		while(true)
		{
			// Obtain all of the statistics already stored on the database to check for update frequency.
			$stats = $this->db->query('
				SELECT
					board_id, name, timestamp
				FROM ' . $this->db->protect_identifiers('plugin_fu-board-statistics',
				TRUE) . '
				ORDER BY timestamp DESC
			');
	
			// Obtain the list of all statistics enabled.
			$avail = array();
			foreach ($available as $k => $a)
			{
				// get only the non-realtime ones
				if (isset($available['frequency']))
					$avail[] = $k;
			}
		
			foreach ($boards as $board)
			{
				if (!is_null($shortname) && $shortname != $board->shortname)
					continue;
	
				// Update all statistics for the specified board or current board.
				echo $board->shortname . ' (' . $board->id . ')' . PHP_EOL;
				foreach ($available as $k => $a)
				{
					echo '  ' . $k . ' ';
					$found = FALSE;
					$skip = FALSE;
					foreach ($stats->result() as $r)
					{
						// Determine if the statistics already exists or that the information is outdated.
						if ($r->board_id == $board->id && $r->name == $k)
						{
							// This statistics report has run once already.
							$found = TRUE;
	
							if(!isset($a['frequency']))
							{
								$skip = TRUE;
								continue;
							}
	
							// This statistics report has not reached its frequency EOL.
							if ((time() - strtotime($r->timestamp)) <= $a['frequency'])
							{
								$skip = TRUE;
								continue;
							}
	
							// This statistics report has another process locked.
							if (!$this->lock_stat($r->board_id, $k, $r->timestamp))
							{
								continue;
							}
							break;
						}
					}
	
					// racing conditions with our cron.
					if ($found === FALSE)
					{
						$this->save_stat($board->id, $k, date('Y-m-d H:i:s', time() + 600), '');
					}
	
					// We were able to obtain a LOCK on the statistics report and has already reached the
					// targeted frequency time.
					if ($skip === FALSE)
					{
						echo '* Processing...';
						$process = 'process_' . $k;
						$this->db->reconnect();
						$result = $this->$process($board);
	
						// This statistics report generates a graph via GNUPLOT.
						if (isset($available[$k]['gnuplot']) && is_array($result) && !empty($result))
						{
							$this->graph_gnuplot($board->shortname, $k, $result);
						}
	
						// Save the statistics report in a JSON array.
						$this->save_stat($board->id, $k, date('Y-m-d H:i:s'), $result);
					}
	
					echo PHP_EOL;
				}
			}
			
			$stats->free_result();
			sleep(10);
		}
	}
}