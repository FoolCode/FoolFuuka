<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-nginx-cache-purge')
    ->setCall(function($result) {
        \Autoloader::add_classes([
            'Foolz\Foolframe\Controller\Admin\Plugins\NginxCachePurge' => __DIR__.'/classes/controller/admin.php',
            'Foolz\Foolfuuka\Plugins\NginxCachePurge\Model\NginxCachePurge' =>__DIR__.'/classes/model/nginx_cache_purge.php'
        ]);

        // don't add the admin panels if the user is not an admin
        if (\Auth::has_access('maccess.admin')) {
            $result->getParam('framework')->getRouteCollection()->add(
                'foolfuuka.plugin.nginx_cache_purge.admin', new \Symfony\Component\Routing\Route(
                    '/admin/plugins/nginx_cache_purge/{_suffix}',
                    [
                        '_suffix' => 'manage',
                        '_controller' => 'Foolz\Foolframe\Controller\Admin\Plugins\NginxCachePurge::manage'
                    ],
                    [
                        '_suffix' => '.*'
                    ]
                )
            );

            \Plugins::registerSidebarElement('admin', 'plugins', [
                "content" => ["nginx_cache_purge/manage" => ["level" => "admin", "name" => 'Nginx Cache Purge', "icon" => 'icon-leaf']]
            ]);
        }

        \Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Media::delete.call.before.method')
            ->setCall('Foolz\Foolfuuka\Plugins\NginxCachePurge\Model\NginxCachePurge::beforeDeleteMedia');
    });
