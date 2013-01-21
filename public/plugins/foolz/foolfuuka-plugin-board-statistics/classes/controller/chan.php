<?php

namespace Foolz\Foolfuuka\Controller\Chan;

use \Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics as BS;

class BoardStatistics extends \Foolz\Foolfuuka\Controller\Chan
{
	/**
	 * @param null $report
	 */
	public function radix_statistics($report = null)
	{
		// Load Statistics Model

		if (is_null($report))
		{
			$stats = BS::get_available_stats();

			// Set template variables required to build the HTML.
			$this->builder->getProps()->addTitle(__('Statistics'));
			$this->param_manager->setParam('section_title', __('Statistics'));

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

			$partial = $this->builder->createPartial('body', 'plugin');
			$partial->getParamManager()->setParam('content', $string);

			return \Response::forge($this->builder->build());
		}
		else
		{
			$stats = BS::check_available_stats($report, $this->_radix);

			if (!is_array($stats))
			{
				return $this->error(__('Statistic currently not available.'));
			}

			$this->builder->getProps()->addTitle(__('Statistics') . ': ' . $stats['info']['name']);

			if (isset($stats['info']['frequency']))
			{
				$time_next = strtotime($stats['timestamp']) + $stats['info']['frequency'] - time();

				if ($time_next < 0)
				{
					$time_next = __('now!');
				}
				elseif ($time_next < 60)
				{
					$time_next = $time_next.' ' .__('seconds');
				}
				elseif ($time_next < 3600)
				{
					$time_next = floor($time_next / 60).' '.__('minutes');
				}
				elseif ($time_next < 86400)
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

			$this->param_manager->setParam('section_title', $section_title);

			$data = $stats['data'];
			$info = $stats['info'];
			ob_start();
			?>
			<div style="margin: 20px auto; width:960px;">
			<?php
			include __DIR__.'/../../views/' . $stats['info']['interface'] . '.php';
			?>
			</div>
			<?php
			$string = ob_get_clean();
			$partial = $this->builder->createPartial('body', 'plugin');
			$partial->getParamManager()->setParam('content', $string);

			return \Response::forge($this->builder->build());
		}
	}
}