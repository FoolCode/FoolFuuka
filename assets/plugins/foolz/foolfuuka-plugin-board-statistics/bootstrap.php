<?php

use Doctrine\DBAL\Schema\Schema;
use Foolz\Foolframe\Model\Autoloader;
use Foolz\Foolframe\Model\Context;
use Foolz\Foolframe\Model\DoctrineConnection;
use Foolz\Foolframe\Model\Plugins;
use Foolz\Foolframe\Model\Uri;
use Foolz\Foolfuuka\Model\RadixCollection;
use Foolz\Plugin\Event;
use Symfony\Component\Routing\Route;

class HHVM_BS
{
    public function run()
    {
        Event::forge('Foolz\Plugin\Plugin::execute#foolz/foolfuuka-plugin-board-statistics')
            ->setCall(function ($result) {
                /* @var Context $context */
                $context = $result->getParam('context');
                /** @var Autoloader $autoloader */
                $autoloader = $context->getService('autoloader');

                $autoloader->addClassMap([
                    'Foolz\Foolframe\Controller\Admin\Plugins\BoardStatistics' => __DIR__ . '/classes/controller/admin.php',
                    'Foolz\Foolfuuka\Controller\Chan\BoardStatistics' => __DIR__ . '/classes/controller/chan.php',
                    'Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics' => __DIR__ . '/classes/model/board_statistics.php',
                    'Foolz\Foolfuuka\Plugins\BoardStatistics\Console\Console' => __DIR__ . '/classes/console/console.php'
                ]);

                $context->getContainer()
                    ->register('foolfuuka-plugin.board_statistics', 'Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics')
                    ->addArgument($context);

                Event::forge('Foolz\Foolframe\Model\Context::handleWeb#obj.afterAuth')
                    ->setCall(function ($result) use ($context) {
                        // don't add the admin panels if the user is not an admin
                        if ($context->getService('auth')->hasAccess('maccess.admin')) {
                            Event::forge('Foolz\Foolframe\Controller\Admin::before#var.sidebar')
                                ->setCall(function ($result) {
                                    $sidebar = $result->getParam('sidebar');
                                    $sidebar[]['plugins'] = [
                                        "content" => ["board_statistics/manage" => ["level" => "admin", "name" => _i("Board Statistics"), "icon" => 'icon-bar-chart']]
                                    ];
                                    $result->setParam('sidebar', $sidebar);
                                });

                            $context->getRouteCollection()->add(
                                'foolframe.plugin.board_statistics.admin', new Route(
                                    '/admin/plugins/board_statistics/{_suffix}',
                                    [
                                        '_suffix' => 'manage',
                                        '_controller' => '\Foolz\Foolframe\Controller\Admin\Plugins\BoardStatistics::manage'
                                    ],
                                    [
                                        '_suffix' => '.*'
                                    ]
                                )
                            );
                        }

                        Event::forge('foolframe.themes.generic_top_nav_buttons')
                            ->setCall(function ($result) {
                                $obj = $result->getObject();
                                $top_nav = $result->getParam('nav');
                                if ($obj->getRadix()) {
                                    $top_nav[] = ['href' => $obj->getUri()->create([$obj->getRadix()->shortname, 'statistics']), 'text' => _i('Stats')];
                                    $result->set($top_nav);
                                    $result->setParam('nav', $top_nav);
                                }
                            })->setPriority(3);
                    });

                Event::forge('Foolz\Foolframe\Model\Context::handleConsole#obj.app')
                    ->setCall(function ($result) use ($context) {
                        $result->getParam('application')
                            ->add(new \Foolz\Foolfuuka\Plugins\BoardStatistics\Console\Console($context));
                    });

                Event::forge('Foolz\Foolframe\Model\Context::handleWeb#obj.routing')
                    ->setCall(function ($result) use ($context) {
                        /** @var RadixCollection $radix_coll */
                        $radix_coll = $context->getService('foolfuuka.radix_collection');
                        $radix_all = $radix_coll->getAll();

                        foreach ($radix_all as $radix) {
                            $obj = $result->getObject();
                            $obj->getRouteCollection()->add(
                                'foolfuuka.plugin.board_statistics.chan.radix.' . $radix->shortname, new Route(
                                '/' . $radix->shortname . '/statistics/{_suffix}',
                                [
                                    '_suffix' => '',
                                    '_controller' => '\Foolz\Foolfuuka\Controller\Chan\BoardStatistics::statistics',
                                    'radix_shortname' => $radix->shortname
                                ],
                                [
                                    '_suffix' => '.*'
                                ]
                            ));
                        }
                    });
            });

        Event::forge('Foolz\Foolframe\Model\Plugin::install#foolz/foolfuuka-plugin-board-statistics')
            ->setCall(function ($result) {
                /** @var Context $context */
                $context = $result->getParam('context');
                /** @var DoctrineConnection $dc */
                $dc = $context->getService('doctrine');

                /** @var Schema $schema */
                $schema = $result->getParam('schema');
                $table = $schema->createTable($dc->p('plugin_fu_board_statistics'));
                if ($dc->getConnection()->getDriver()->getName() == 'pdo_mysql') {
                    $table->addOption('charset', 'utf8mb4');
                    $table->addOption('collate', 'utf8mb4_general_ci');
                }
                $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
                $table->addColumn('board_id', 'integer', ['unsigned' => true]);
                $table->addColumn('name', 'string', ['length' => 32]);
                $table->addColumn('timestamp', 'integer', ['unsigned' => true]);
                $table->addColumn('data', 'text', ['length' => 4294967295]);
                $table->setPrimaryKey(['id']);
                $table->addUniqueIndex(['board_id', 'name'], $dc->p('plugin_fu_board_statistics_board_id_name_index'));
                $table->addIndex(['timestamp'], $dc->p('plugin_fu_board_statistics_timestamp_index'));
            });
    }
}

(new HHVM_BS())->run();
