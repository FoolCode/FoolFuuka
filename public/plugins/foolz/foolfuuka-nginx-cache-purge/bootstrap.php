<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-nginx-cache-purge')
	->setCall(function($result) {
		\Autoloader::add_classes(array(
			'Foolfuuka\\Plugins\\Nginx_Cache_Purge\\Nginx_Cache_Purge' => __DIR__.'/classes/model/nginx_cache_purge.php',
			'Foolfuuka\\Plugins\\Nginx_Cache_Purge\\Controller_Plugin_Fu_Nginx_Cache_Purge_Admin_Nginx_Cache_Purge'
				=> __DIR__.'/classes/controller/admin/nginx_cache_purge.php'
		));

		// don't add the admin panels if the user is not an admin
		if (\Auth::has_access('maccess.admin'))
		{
			\Plugins::register_sidebar_element('admin', 'plugins', array(
				"content" => array("nginx_cache_purge/manage" => array("level" => "admin", "name" => 'Nginx Cache Purge', "icon" => 'icon-leaf'))
			));
		}

		\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Media::delete.call.before')
			->setCall('Foolfuuka\\Plugins\\Nginx_Cache_Purge\\Nginx_Cache_Purge::before_delete_media');
	});

