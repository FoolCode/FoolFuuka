<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-geoip-region-lock')
	->setCall(function($result) {
		/* @var $framework \Foolz\Foolframe\Model\Framework */
		$framework = $result->getParam('framework');

		\Autoloader::add_classes([
			'Foolz\Foolframe\Controller\Admin\Plugins\GeoipRegionLock' => __DIR__.'/classes/controller/admin.php',
			'Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model\GeoipRegionLock' =>__DIR__.'/classes/model/geoip_region_lock.php'
		]);

		// don't add the admin panels if the user is not an admin
		if (\Auth::has_access('maccess.admin'))
		{
			$framework->getRouteCollection()->add(
				'foolframe.plugin.geoip_region_lock.admin', new \Symfony\Component\Routing\Route(
					'/admin/plugins/geoip_region_lock/{_suffix}',
					[
						'_suffix' => 'manage',
						'_controller' => '\Foolz\Foolframe\Controller\Admin\Plugins\GeoipRegionLock::manage'
					],
					[
						'_suffix' => '.*'
					]
				)
			);

			\Plugins::registerSidebarElement('admin', 'plugins', [
				"content" => ["geoip_region_lock/manage" => ["level" => "admin", "name" => 'GeoIP Region Lock', "icon" => 'icon-flag']]
			]);
		}

		if ( ! \Auth::has_access('maccess.mod') && !(\Preferences::get('foolfuuka.plugins.geoip_region_lock.allow_logged_in') && \Auth::has_access('access.user')))
		{
			\Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model\GeoipRegionLock::blockCountryView();

			\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\CommentInsert::insert.call.before.method')
				->setCall('Foolfuuka\\Plugins\\GeoipRegionLock\\GeoipRegionLock::blockCountryComment')
				->setPriority(4);
		}

		\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Radix::structure.result')
			->setCall(function($result){
			$structure = $result->getParam('structure');

			$structure['plugin_geo_ip_region_lock_allow_comment'] = [
				'database' => true,
				'boards_preferences' => true,
				'type' => 'input',
				'class' => 'span3',
				'label' => 'Nations allowed to post comments',
				'help' => __('Comma separated list of GeoIP 2-letter nation codes.'),
				'default_value' => false
			];

			$structure['plugin_geo_ip_region_lock_disallow_comment'] = [
				'database' => true,
				'boards_preferences' => true,
				'type' => 'input',
				'class' => 'span3',
				'label' => 'Nations disallowed to post comments',
				'help' => __('Comma separated list of GeoIP 2-letter nation codes.'),
				'default_value' => false
			];
			$result->setParam('structure', $structure)->set($structure);
		})->setPriority(8);
	});