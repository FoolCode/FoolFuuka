<?php

use Foolz\Foolframe\Model\Auth;
use Foolz\Foolframe\Model\Autoloader;
use Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model\GeoipRegionLock;
use Foolz\Plugin\Event;

Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-geoip-region-lock')
    ->setCall(function($result) {
        /* @var Context $context */
        $context = $result->getParam('context');
        /** @var Autoloader $autoloader */
        $autoloader = $context->getService('autoloader');

        $autoloader->addClassMap([
            'Foolz\Foolframe\Controller\Admin\Plugins\GeoipRegionLock' => __DIR__.'/classes/controller/admin.php',
            'Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model\GeoipRegionLock' =>__DIR__.'/classes/model/geoip_region_lock.php'
        ]);

        Event::forge('Foolz\Foolframe\Model\Context.handleWeb.has_auth')
            ->setCall(function($result) use ($context) {
                // don't add the admin panels if the user is not an admin
                /** @var Auth $auth */
                $auth = $context->getService('auth');
                if ($auth->hasAccess('maccess.admin')) {
                    $context->getRouteCollection()->add(
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

                    Event::forge('Foolz\Foolframe\Controller\Admin.before.sidebar.add')
                        ->setCall(function($result) {
                            $sidebar = $result->getParam('sidebar');
                            $sidebar[]['plugins'] = [
                                "content" => ["geoip_region_lock/manage" => ["level" => "admin", "name" => 'GeoIP Region Lock', "icon" => 'icon-flag']]
                            ];
                            $result->setParam('sidebar', $sidebar);
                        });
                }

                $preferences = $context->getService('preferences');

                $context->getContainer()
                    ->register('foolfuuka-plugin.geoip_region_lock')
                    ->addArgument($context);

                /** @var GeoipRegionLock $object */
                $object = $context->getService('foolfuuka-plugin.geoip_region_lock');

                if (!$auth->hasAccess('maccess.mod') && !($preferences->get('foolfuuka.plugins.geoip_region_lock.allow_logged_in') && $auth->hasAccess('access.user'))) {
                    Event::forge('Foolz\Foolframe\Model\Context.handleWeb.override_response')
                        ->setCall(function($response) use ($context, $object) {
                            $object->blockCountryView($response->getParam('request'), $response);
                        })
                        ->setPriority(2);

                    Event::forge('Foolz\Foolfuuka\Model\CommentInsert::insert.call.before.method')
                        ->setCall('Foolfuuka\\Plugins\\GeoipRegionLock\\GeoipRegionLock::blockCountryComment')
                        ->setPriority(4);
                }
            });



        Event::forge('Foolz\Foolfuuka\Model\Radix::structure.result')
            ->setCall(function($result){
            $structure = $result->getParam('structure');

            $structure['plugin_geo_ip_region_lock_allow_comment'] = [
                'database' => true,
                'boards_preferences' => true,
                'type' => 'input',
                'class' => 'span3',
                'label' => 'Nations allowed to post comments',
                'help' => _i('Comma separated list of GeoIP 2-letter nation codes.'),
                'default_value' => false
            ];

            $structure['plugin_geo_ip_region_lock_disallow_comment'] = [
                'database' => true,
                'boards_preferences' => true,
                'type' => 'input',
                'class' => 'span3',
                'label' => 'Nations disallowed to post comments',
                'help' => _i('Comma separated list of GeoIP 2-letter nation codes.'),
                'default_value' => false
            ];
            $result->setParam('structure', $structure)->set($structure);
        })->setPriority(8);
    });
