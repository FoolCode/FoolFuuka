<?php

namespace Foolz\FoolFuuka\Model;

use Foolz\FoolFrame\Model\Auth;
use Foolz\FoolFrame\Model\ContextInterface;
use Foolz\FoolFrame\Model\Legacy\Config;
use Foolz\Plugin\Event;
use Foolz\Plugin\Hook;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

class Context implements ContextInterface
{
    /**
     * @var \Foolz\FoolFrame\Model\Context
     */
    public $context;

    public function __construct(\Foolz\FoolFrame\Model\Context $context)
    {
        $this->context = $context;

        /** @var \Foolz\FoolFrame\Model\Config $config */
        $config = $this->context->getService('config');
        $config->addPackage('foolz/foolfuuka', ASSETSPATH);

        class_alias('Foolz\\FoolFuuka\\Model\\Audit', 'Audit');
        class_alias('Foolz\\FoolFuuka\\Model\\Ban', 'Ban');
        class_alias('Foolz\\FoolFuuka\\Model\\Board', 'Board');
        class_alias('Foolz\\FoolFuuka\\Model\\Comment', 'Comment');
        class_alias('Foolz\\FoolFuuka\\Model\\CommentInsert', 'CommentInsert');
        class_alias('Foolz\\FoolFuuka\\Model\\Media', 'Media');
        class_alias('Foolz\\FoolFuuka\\Model\\Radix', 'Radix');
        class_alias('Foolz\\FoolFuuka\\Model\\Report', 'Report');
        class_alias('Foolz\\FoolFuuka\\Model\\Search', 'Search');

        $context->getContainer()
            ->register('foolfuuka.radix_collection', 'Foolz\FoolFuuka\Model\RadixCollection')
            ->addArgument($context);

        $context->getContainer()
            ->register('foolfuuka.comment_factory', 'Foolz\FoolFuuka\Model\CommentFactory')
            ->addArgument($context);

        $context->getContainer()
            ->register('foolfuuka.media_factory', 'Foolz\FoolFuuka\Model\MediaFactory')
            ->addArgument($context);

        $context->getContainer()
            ->register('foolfuuka.audit_factory', 'Foolz\FoolFuuka\Model\AuditFactory')
            ->addArgument($context);

        $context->getContainer()
            ->register('foolfuuka.ban_factory', 'Foolz\FoolFuuka\Model\BanFactory')
            ->addArgument($context);

        $context->getContainer()
            ->register('foolfuuka.report_collection', 'Foolz\FoolFuuka\Model\ReportCollection')
            ->addArgument($context);
    }

    public function handleWeb(Request $request)
    {
        $preferences = $this->context->getService('preferences');
        $config = $this->context->getService('config');
        $uri = $this->context->getService('uri');

        $theme_instance = \Foolz\Theme\Loader::forge('foolfuuka');
        $theme_instance->addDir($config->get('foolz/foolfuuka', 'package', 'directories.themes'));
        $theme_instance->addDir(VAPPPATH.'foolz/foolfuuka/themes/');
        $theme_instance->setBaseUrl($uri->base().'foolfuuka/');
        $theme_instance->setPublicDir(DOCROOT.'foolfuuka/');

        // set an ->enabled on the themes we want to use
        /** @var Auth $auth */
        $auth = $this->context->getService('auth');
        if ($auth->hasAccess('maccess.admin')) {
            Event::forge('Foolz\FoolFrame\Model\System::getEnvironment#var.environment')
                ->setCall(function($result) use ($config) {
                    $environment = $result->getParam('environment');

                    foreach ($config->get('foolz/foolfuuka', 'environment') as $section => $data) {
                        foreach ($data as $k => $i) {
                            array_push($environment[$section]['data'], $i);
                        }
                    }

                    $result->setParam('environment', $environment)->set($environment);
                })->setPriority(0);

            foreach ($theme_instance->getAll() as $theme) {
                $theme->enabled = true;
            }
        } else {
            if ($themes_enabled = $preferences->get('foolfuuka.theme.active_themes')) {
                $themes_enabled = unserialize($themes_enabled);
            } else {
                $themes_enabled = ['foolz/foolfuuka-theme-foolfuuka' => 1];
            }

            foreach ($themes_enabled as $key => $item) {
                if (!$item && !$auth->hasAccess('maccess.admin')) {
                    continue;
                }

                try {
                    $theme = $theme_instance->get($key);
                    $theme->enabled = true;
                } catch (\OutOfBoundsException $e) {}
            }
        }

        try {
            $theme_name = $request->query->get('theme', $request->cookies->get($this->context->getService('config')->get('foolz/foolframe', 'config', 'config.cookie_prefix').'theme')) ? : $preferences->get('foolfuuka.theme.default');
            $theme_name_exploded = explode('/', $theme_name);

            // must get rid of the style
            if (count($theme_name_exploded) >= 2) {
                $theme_name = $theme_name_exploded[0].'/'.$theme_name_exploded[1];
            }

            $theme = $theme_instance->get($theme_name);

            if (!isset($theme->enabled) || !$theme->enabled) {
                throw new \OutOfBoundsException;
            }
        } catch (\OutOfBoundsException $e) {
            $theme = $theme_instance->get('foolz/foolfuuka-theme-foolfuuka');
        }

        $theme->bootstrap();
    }

    public function loadRoutes(RouteCollection $route_collection)
    {
        Hook::forge('Foolz\FoolFuuka\Model\Context::loadRoutes#obj.beforeRouting')
            ->setObject($this)
            ->setParam('route_collection', $route_collection)
            ->execute();

        $route_collection->add('foolfuuka.root', new Route(
            '/',
            ['_controller' => '\Foolz\FoolFuuka\Controller\Chan::index']
        ));

        $route_collection->add('404', new Route(
            '',
            ['_controller' => '\Foolz\FoolFuuka\Controller\Chan::404']
        ));

        $route = Hook::forge('Foolz\FoolFuuka\Model\Context::loadRoutes#var.collection')
            ->setParams([
                'default_suffix' => 'page',
                'suffix' => 'page',
                'controller' => '\Foolz\FoolFuuka\Controller\Chan::*'
            ])
            ->execute();


        /** @var Radix[] $radix_all */
        $radix_all = $this->context->getService('foolfuuka.radix_collection')->getAll();

        foreach ($radix_all as $radix) {
            $route_collection->add(
                'foolfuuka.chan.radix.'.$radix->shortname, new Route(
                '/'.$radix->shortname.'/{_suffix}',
                [
                    '_default_suffix' => $route->getParam('default_suffix'),
                    '_suffix' => $route->getParam('suffix'),
                    '_controller' => $route->getParam('controller'),
                    'radix_shortname' => $radix->shortname
                ],
                [
                    '_suffix' => '.*'
                ]
            ));
        }

        $route_collection->add(
            'foolfuuka.chan.api', new Route(
            '/_/api/chan/{_suffix}',
            [
                '_suffix' => '',
                '_controller' => '\Foolz\FoolFuuka\Controller\Api\Chan::*',
            ],
            [
                '_suffix' => '.*'
            ]
        ));

        $route_collection->add(
            'foolfuuka.chan._', new Route(
            '/_/{_suffix}',
            [
                '_suffix' => '',
                '_controller' => '\Foolz\FoolFuuka\Controller\Chan::*',
            ],
            [
                '_suffix' => '.*'
            ]
        ));

        foreach(['boards', 'moderation'] as $location) {
            $route_collection->add(
                'foolfuuka.admin.'.$location, new Route(
                    '/admin/'.$location.'/{_suffix}',
                    [
                        '_suffix' => '',
                        '_controller' => '\Foolz\FoolFuuka\Controller\Admin\\'.ucfirst($location).'::*',
                    ],
                    [
                        '_suffix' => '.*',
                    ]
                )
            );
        }

        Hook::forge('Foolz\FoolFuuka\Model\Context::loadRoutes#obj.afterRouting')
            ->setObject($this)
            ->setParam('route_collection', $route_collection)
            ->execute();
    }

    public function handleConsole()
    {
        // no actions
    }
}
