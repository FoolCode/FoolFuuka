<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-nginx-cache-purge')
	->setCall(function($result) {
		\Autoloader::add_classes([
			'Foolfuuka\\Plugins\\NginxCachePurge\\NginxCachePurge' => __DIR__.'/classes/model/nginxcachepurge.php',
			'Foolfuuka\\Plugins\\NginxCachePurge\\ControllerPluginFuNginxCachePurgeAdminNginxCachePurge'
				=> __DIR__.'/classes/controller/admin/nginxcachepurge.php'
		]);

		// don't add the admin panels if the user is not an admin
		if (\Auth::has_access('maccess.admin'))
		{
			\Plugins::register_sidebar_element('admin', 'plugins', [
				"content" => ["nginx_cache_purge/manage" => ["level" => "admin", "name" => 'Nginx Cache Purge', "icon" => 'icon-leaf']]
			]);
		}

		\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Media::delete.call.before')
			->setCall('Foolfuuka\\Plugins\\NginxCachePurge\\NginxCachePurge::beforeDeleteMedia');
	});

