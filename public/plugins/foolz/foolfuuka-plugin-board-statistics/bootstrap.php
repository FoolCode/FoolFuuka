<?php

use Foolz\Foolframe\Model\DoctrineConnection as DC;
use Symfony\Component\Routing\Route;

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-board-statistics')
	->setCall(function($result) {
		/* @var $framework \Foolz\Foolframe\Model\Framework */
		$framework = $result->getParam('framework');

		\Autoloader::add_classes([
			'Foolz\Foolframe\Controller\Admin\Plugins\BoardStatistics' => __DIR__.'/classes/controller/admin.php',
			'Foolz\Foolfuuka\Controller\Chan\BoardStatistics' => __DIR__.'/classes/controller/chan.php',
			'Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics' => __DIR__.'/classes/model/board_statistics.php',
			'Foolz\Foolfuuka\Plugins\BoardStatistics\Console\Console' => __DIR__.'/classes/console/console.php'
		]);

		// don't add the admin panels if the user is not an admin
		if (\Auth::has_access('maccess.admin'))
		{
			\Plugins::registerSidebarElement('admin', 'plugins', [
				"content" => ["board_statistics/manage" => ["level" => "admin", "name" => _i("Board Statistics"), "icon" => 'icon-bar-chart']]
			]);

			$framework->getRouteCollection()->add(
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

		\Foolz\Plugin\Event::forge('Foolz\Foolframe\Model\Framework::handleConsole.add')
			->setCall(function($result) {
				$result->getParam('application')
					->add(new \Foolz\Foolfuuka\Plugins\BoardStatistics\Console\Console);
			});

		\Foolz\Plugin\Event::forge('foolframe.themes.generic_top_nav_buttons')
			->setCall(function($result) {
				$top_nav = $result->getParam('nav');
				if (\Radix::getSelected())
				{
					$top_nav[] = ['href' => Uri::create([Radix::getSelected()->shortname, 'statistics']), 'text' => _i('Stats')];
					$result->set($top_nav);
					$result->setParam('nav', $top_nav);
				}
			})->setPriority(3);

		$radix_all = \Foolz\Foolfuuka\Model\Radix::getAll();
		foreach ($radix_all as $radix)
		{
			$framework->getRouteCollection()->add(
				'foolfuuka.plugin.board_statistics.chan.radix.'.$radix->shortname, new Route(
				'/'.$radix->shortname.'/statistics/{_suffix}',
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

\Foolz\Plugin\Event::forge('Foolz\Foolframe\Model\Plugin::schemaUpdate.foolz/foolfuuka-plugin-board-statistics')
	->setCall(function($result) {
		$schema = $result->getParam('schema');
		$table = $schema->createTable(DC::p('plugin_fu_board_statistics'));
		if (DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$table->addOption('charset', 'utf8mb4');
			$table->addOption('collate', 'utf8mb4_general_ci');
		}
		$table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$table->addColumn('board_id', 'integer', ['unsigned' => true]);
		$table->addColumn('name', 'string', ['length' => 32]);
		$table->addColumn('timestamp', 'integer', ['unsigned' => true]);
		$table->addColumn('data', 'text', ['length' => 65532]);
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['board_id', 'name'], DC::p('plugin_fu_board_statistics_board_id_name_index'));
		$table->addIndex(['timestamp'], DC::p('plugin_fu_board_statistics_timestamp_index'));
	});