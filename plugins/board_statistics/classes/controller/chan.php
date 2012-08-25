<?php

namespace Foolfuuka\Plugins\Board_Statistics;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Controller_Plugin_Fu_Board_Statistics_Chan extends \Foolfuuka\Controller_Chan
{
	/**
	 * @param null $report
	 */
	public function radix_statistics($report = NULL)
	{
		// Load Statistics Model

		if (is_null($report))
		{
			$stats = Board_Statistics::get_available_stats();

			// Set template variables required to build the HTML.
			$this->_theme->set_title($this->_radix->formatted_title . ' &raquo; ' . __('Statistics'));
			$this->_theme->bind(array(
				'section_title' => __('Statistics'),
				'is_statistics' => TRUE,
				'is_statistics_list' => TRUE,
				'info' => $stats
			));
			
			ob_start();
			?>

			<div style="margin: 20px auto; width:960px;">
				<nav style="margin-top:20px;">
					<ul>
						<?php foreach ($stats as $key => $stat) : ?>
						<li>
							<a href="<?php echo \Uri::create(array($this->_radix->shortname, 'statistics', $key)) ?>" title="<?php echo htmlspecialchars($stat['name']) ?>" ><?php echo $stat['name'] ?></a>
						</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			</div>

			<?php
			$string = ob_get_clean();
			return \Response::forge($this->_theme->build('plugin', array('content' => $string)));
		}
		else
		{
			$stats = Board_Statistics::check_available_stats($report, $this->_radix);

			if (!is_array($stats))
			{
				return get_instance()->show_404();
			}

			// Set template variables required to build the HTML.
			$this->_theme->set_title($this->_radix->formatted_title . ' &raquo; '
				. __('Statistics') . ': ' . $stats['info']['name']);

			if (isset($stats['info']['frequency']))
			{
				$time_next = strtotime($stats['timestamp']) + $stats['info']['frequency'] - time();
				
				if ($time_next < 0)
				{
					$time_next = __('now!');
				}
				else if ($time_next < 60)
				{
					$time_next = $time_next.' ' .__('seconds');
				}
				else if ($time_next < 3600)
				{
					$time_next = floor($time_next / 60).' '.__('minutes');
				}
				else if ($time_next < 86400)
				{
					$time_next = floor($time_next / 3600).' '.__('hours');
				}
				else
				{
					$time_next = floor($time_next / 86400).' '.__('days');
				}
				
				
				$section_title = sprintf(__('Statistics: %s (Next Update in %s)'),
					$stats['info']['name'],
					$time_next
				);
			}
			else
			{
				$section_title = sprintf(__('Statistics: %s'), $stats['info']['name']);
			}
			
			$this->_theme->bind(array(
				'section_title' => $section_title,
				'is_statistics' => TRUE,
				'is_statistics_list' => TRUE,
			));
			
			$data = $stats['data'];
			$info = $stats['info'];
			ob_start();
			?>
			<div style="margin: 20px auto; width:960px;">
			<?php
			include DOCROOT.'foolfuuka/plugins/board_statistics/views/' . $stats['info']['interface'] . '.php';
			?>
			</div> 
			<?php
			$string = ob_get_clean();
			return \Response::forge($this->_theme->build('plugin', array('content' => $string)));
		}
	}
}