<?php

if ( ! defined('DOCROOT'))
	exit('No direct script access allowed');

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-geoip-region-lock')
	->setCall(function($result) {
		\Autoloader::add_classes([
			'Foolfuuka\\Plugins\\GeoipRegionLock\\GeoipRegionLock' => __DIR__.'/classes/model/geoipregionlock.php',
			'Foolfuuka\\Plugins\\GeoipRegionLock\\ControllerPluginFuGeoipRegionLockAdminGeoipRegionLock'
				=> __DIR__.'/classes/controller/admin/geoipregionlock.php'
		]);

		// don't add the admin panels if the user is not an admin
		if (\Auth::has_access('maccess.admin'))
		{
			\Router::add('admin/plugins/geoip_region_lock', 'plugin/fu/geoip_region_lock/admin/geoip_region_lock/manage');

			\Plugins::registerSidebarElement('admin', 'plugins', [
				"content" => ["geoip_region_lock" => ["level" => "admin", "name" => 'GeoIP Region Lock', "icon" => 'icon-flag']]
			]);
		}

		if ( ! \Auth::has_access('maccess.mod') && !(\Preferences::get('fu.plugins.geoip_region_lock.allow_logged_in') && \Auth::has_access('access.user')))
		{
			\Foolfuuka\Plugins\Geoip_Region_Lock\GeoipRegionLock::block_country_view();

			\Foolz\Plugin\Event::forge('fu.comment.insert.call.before')
				->setCall('Foolfuuka\\Plugins\\GeoipRegionLock\\GeoipRegionLock::blockCountryComment')
				->setPriority(4);
		}

		\Foolz\Plugin\Event::forge('fu.radix.structure.structure_alter')
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