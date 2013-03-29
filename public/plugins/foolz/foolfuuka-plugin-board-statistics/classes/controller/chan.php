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
			$stats = BS::getAvailableStats();

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
							<a href="<?php echo \Uri::create([$this->_radix->shortname, 'statistics', $key]) ?>" title="<?php echo htmlspecialchars($stat['name']) ?>" ><?php echo $stat['name'] ?></a>
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
			$stats = BS::checkAvailableStats($report, $this->_radix);

			if ( ! is_array($stats))
			{
				return $this->error(__('Statistic currently not available.'));
			}

			$this->builder->getProps()->addTitle(__('Statistics') . ': ' . $stats['info']['name']);

			if (isset($stats['info']['frequency']))
			{
				$last_updated = time() - $stats['timestamp'];

				if ($last_updated < 0)
				{
					$last_updated = __('now!');
				}
				elseif ($last_updated < 60)
				{
					$last_updated = $last_updated.' ' .__('seconds');
				}
				elseif ($last_updated < 3600)
				{
					$last_updated = floor($last_updated / 60).' '.__('minutes');
				}
				elseif ($last_updated < 86400)
				{
					$last_updated = floor($last_updated / 3600).' '.__('hours');
				}
				else
				{
					$last_updated = floor($last_updated / 86400).' '.__('days');
				}

				$section_title = sprintf(__('Statistics: %s (Last Updated: %s ago)'),
					$stats['info']['name'],
					$last_updated
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