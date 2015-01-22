<?php

use Foolz\Foolframe\Model\Auth;
use Foolz\Foolframe\Model\Autoloader;
use Foolz\Foolframe\Model\Context;
use Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model\GeoipRegionLock;
use Foolz\Plugin\Event;
use Foolz\Plugin\Result;

class HHVM_GeoIpBlock
{
    public function run()
    {
        Event::forge('Foolz\Plugin\Plugin::execute#foolz/foolfuuka-plugin-geoip-region-lock')
            ->setCall(function(Result $result) {
                /* @var $context Context */
                $context = $result->getParam('context');
                /** @var Autoloader $autoloader */
                $autoloader = $context->getService('autoloader');

                $autoloader->addClassMap([
                    'Foolz\Foolframe\Controller\Admin\Plugins\GeoipRegionLock' => __DIR__.'/classes/controller/admin.php',
                    'Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model\GeoipRegionLock' =>__DIR__.'/classes/model/geoip_region_lock.php'
                ]);

                Event::forge('Foolz\Foolframe\Model\Context::handleWeb#obj.afterAuth')
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

                            Event::forge('Foolz\Foolframe\Controller\Admin::before#var.sidebar')
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
                            ->register('foolfuuka-plugin.geoip_region_lock', 'Foolz\Foolfuuka\Plugins\GeoipRegionLock\Model\GeoipRegionLock')
                            ->addArgument($context);

                        /** @var GeoipRegionLock $object */
                        $object = $context->getService('foolfuuka-plugin.geoip_region_lock');

                        if (!$auth->hasAccess('maccess.mod') && !($preferences->get('foolfuuka.plugins.geoip_region_lock.allow_logged_in') && $auth->hasAccess('access.user'))) {
                            Event::forge('Foolz\Foolframe\Model\Context::handleWeb#obj.response')
                                ->setCall(function(Result $result) use ($context, $object) {
                                    $object->blockCountryView($result);
                                })
                                ->setPriority(2);

                            Event::forge('Foolz\Foolfuuka\Model\CommentInsert::insert#call.beforeMethod')
                                ->setCall(function(Result $result) use ($context, $object) {
                                    $object->blockCountryComment($result);
                                })
                                ->setPriority(4);
                        }
                    });



                Event::forge('Foolz\Foolfuuka\Model\RadixCollection::structure#var.structure')
                    ->setCall(function(Result $result){
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
    }
}

(new HHVM_GeoIpBlock())->run();
