<?php

namespace Foolz\Foolfuuka\Controller\Chan;

use Foolz\Foolframe\Model\Plugins;
use Foolz\Foolframe\Model\Uri;
use Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics as BS;
use Foolz\Plugin\Plugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BoardStatistics extends \Foolz\Foolfuuka\Controller\Chan
{
    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * @var BS
     */
    protected $board_stats;

    /**
     * @var Uri
     */
    protected $uri;

    public function before()
    {
        /** @var Plugins $plugins */
        $plugins = $this->getContext()->getService('plugins');
        $this->board_stats = $this->getContext()->getService('foolfuuka-plugin.board_statistics');
        $this->uri = $this->getContext()->getService('uri');

        $this->plugin = $plugins->getPlugin('foolz/foolfuuka-plugin-board-statistics');

        parent::before();
    }

    public function radix_statistics($report = null)
    {
        // Load Statistics Model

        if (is_null($report)) {
            $stats = $this->board_stats->getAvailableStats();

            // Set template variables required to build the HTML.
            $this->builder->getProps()->addTitle(_i('Statistics'));
            $this->param_manager->setParam('section_title', _i('Statistics'));

            ob_start();
            ?>

            <div style="margin: 20px auto; width:960px;">
                <nav style="margin-top:20px;">
                    <ul>
                        <?php foreach ($stats as $key => $stat) : ?>
                            <li>
                                <a href="<?php echo $this->uri->create([$this->radix->shortname, 'statistics', $key]) ?>"
                                   title="<?php echo htmlspecialchars($stat['name']) ?>"><?php echo $stat['name'] ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>

            <?php
            $string = ob_get_clean();

            $partial = $this->builder->createPartial('body', 'plugin');
            $partial->getParamManager()->setParam('content', $string);

            return new Response($this->builder->build());
        } else {
            $stats = $this->board_stats->checkAvailableStats($report, $this->radix);

            if (!is_array($stats)) {
                return $this->error(_i('Statistic currently not available.'));
            }

            $this->builder->getProps()->addTitle(_i('Statistics') . ': ' . $stats['info']['name']);

            if (isset($stats['info']['frequency'])) {
                $last_updated = time() - $stats['timestamp'];

                if ($last_updated < 0) {
                    $last_updated = _i('now!');
                } elseif ($last_updated < 60) {
                    $last_updated = $last_updated . ' ' . _i('seconds');
                } elseif ($last_updated < 3600) {
                    $last_updated = floor($last_updated / 60) . ' ' . _i('minutes');
                } elseif ($last_updated < 86400) {
                    $last_updated = floor($last_updated / 3600) . ' ' . _i('hours');
                } else {
                    $last_updated = floor($last_updated / 86400) . ' ' . _i('days');
                }

                $section_title = sprintf(_i('Statistics: %s (Last Updated: %s ago)'),
                    $stats['info']['name'],
                    $last_updated
                );
            } else {
                $section_title = sprintf(_i('Statistics: %s'), $stats['info']['name']);
            }

            $this->param_manager->setParam('section_title', $section_title);

            $data = $stats['data'];
            $info = $stats['info'];
            ob_start();
            ?>
            <link href="<?= $this->plugin->getAssetManager()->getAssetLink('style.css') ?>" rel="stylesheet"
                  type="text/css"/>
            <div style="margin: 20px auto; width:960px;">
                <?php
                include __DIR__ . '/../../views/' . $stats['info']['interface'] . '.php';
                ?>
            </div>
            <?php
            $string = ob_get_clean();
            $partial = $this->builder->createPartial('body', 'plugin');
            $partial->getParamManager()->setParam('content', $string);

            return new Response($this->builder->build());
        }
    }
}
