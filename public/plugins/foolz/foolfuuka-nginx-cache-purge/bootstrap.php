<?php

use Foolz\Foolframe\Model\Autoloader;
use Foolz\Foolframe\Model\Context;
use Foolz\Plugin\Event;

class HHVM_NginxCache
{
    public static function run()
    {
        Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-nginx-cache-purge')
            ->setCall(function ($result) {
                /* @var Context $context */
                $context = $result->getParam('context');
                /** @var Autoloader $autoloader */
                $autoloader = $context->getService('autoloader');

                $autoloader->addClassMap([
                    'Foolz\Foolframe\Controller\Admin\Plugins\NginxCachePurge' => __DIR__ . '/classes/controller/admin.php',
                    'Foolz\Foolfuuka\Plugins\NginxCachePurge\Model\NginxCachePurge' => __DIR__ . '/classes/model/nginx_cache_purge.php'
                ]);

                $context->getContainer()
                    ->register('foolfuuka-plugin.nginx_purge_cache', 'Foolz\Foolfuuka\Plugins\NginxCachePurge\Model\NginxCachePurge')
                    ->addArgument($context);

                Event::forge('Foolz\Foolframe\Model\Context.handleWeb.has_auth')
                    ->setCall(function ($result) use ($context) {
                        // don't add the admin panels if the user is not an admin
                        if ($context->getService('auth')->hasAccess('maccess.admin')) {
                            $context->getRouteCollection()->add(
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

                            Event::forge('Foolz\Foolframe\Controller\Admin.before.sidebar.add')
                                ->setCall(function ($result) {
                                    $sidebar = $result->getParam('sidebar');
                                    $sidebar[]['plugins'] = [
                                        "content" => ["nginx_cache_purge/manage" => ["level" => "admin", "name" => 'Nginx Cache Purge', "icon" => 'icon-leaf']]
                                    ];
                                    $result->setParam('sidebar', $sidebar);
                                });
                        }
                    });

                Event::forge('Foolz\Foolfuuka\Model\Media::delete.call.before.method')
                    ->setCall(function ($result) use ($context) {
                        $context->getService('foolfuuka-plugin.nginx_purge_cache')->beforeDeleteMedia($result);
                    });
            });

    }
}

HHVM_NginxCache::run();
