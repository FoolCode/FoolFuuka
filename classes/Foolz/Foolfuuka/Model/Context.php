<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Foolframe\Model\ContextInterface;
use Foolz\Foolframe\Model\Legacy\Config;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

class Context implements ContextInterface
{
    /**
     * @var \Foolz\Foolframe\Model\Context
     */
    public $context;

    public function __construct(\Foolz\Foolframe\Model\Context $context)
    {
        $this->context = $context;

        class_alias('Foolz\\Foolfuuka\\Model\\Ban', 'Ban');
        class_alias('Foolz\\Foolfuuka\\Model\\Board', 'Board');
        class_alias('Foolz\\Foolfuuka\\Model\\Comment', 'Comment');
        class_alias('Foolz\\Foolfuuka\\Model\\CommentInsert', 'CommentInsert');
        class_alias('Foolz\\Foolfuuka\\Model\\Extra', 'Extra');
        class_alias('Foolz\\Foolfuuka\\Model\\Media', 'Media');
        class_alias('Foolz\\Foolfuuka\\Model\\Radix', 'Radix');
        class_alias('Foolz\\Foolfuuka\\Model\\Report', 'Report');
        class_alias('Foolz\\Foolfuuka\\Model\\Search', 'Search');

        \Autoloader::add_classes([
            'StringParser_BBCode' => __DIR__.'/../../../../packages/stringparser-bbcode/library/stringparser_bbcode.class.php',
        ]);

        if (\Auth::has_access('comment.reports')) {
            \Foolz\Foolfuuka\Model\Report::preload();
        }
    }

    public function handleWeb(Request $request)
    {
        $theme_instance = \Foolz\Theme\Loader::forge('foolfuuka');
        $theme_instance->addDir(VENDPATH.'foolz/foolfuuka/'. \Foolz\Foolframe\Model\Legacy\Config::get('foolz/foolfuuka', 'package', 'directories.themes'));
        $theme_instance->addDir(VAPPPATH.'foolz/foolfuuka/themes/');
        $theme_instance->setBaseUrl(\Uri::base().'foolfuuka/');
        $theme_instance->setPublicDir(DOCROOT.'foolfuuka/');

        // set an ->enabled on the themes we want to use
        if (\Auth::has_access('maccess.admin')) {
            \Foolz\Plugin\Event::forge('Foolz\Foolframe\Model\System::environment.result')
                ->setCall(function($result) {
                    $environment = $result->getParam('environment');

                    foreach (\Foolz\Foolframe\Model\Legacy\Config::get('foolz/foolfuuka', 'environment') as $section => $data) {
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
            if ($themes_enabled = \Foolz\Foolframe\Model\Preferences::get('foolfuuka.theme.active_themes')) {
                $themes_enabled = unserialize($themes_enabled);
            } else {
                $themes_enabled = ['foolz/foolfuuka-theme-foolfuuka' => 1];
            }

            foreach ($themes_enabled as $key => $item) {
                if (!$item && !\Auth::has_access('maccess.admin')) {
                    continue;
                }

                try {
                    $theme = $theme_instance->get($key);
                    $theme->enabled = true;
                } catch (\OutOfBoundsException $e) {}
            }
        }

        try {
            $theme_name = \Input::get('theme', \Cookie::get('theme')) ? : \Preferences::get('foolfuuka.theme.default');
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
        $route_collection->add('foolfuuka.root', new Route(
            '/',
            ['_controller' => '\Foolz\Foolfuuka\Controller\Chan::index']
        ));

        $route_collection->add('404', new Route(
            '',
            ['_controller' => '\Foolz\Foolfuuka\Controller\Chan::404']
        ));

        $route = \Foolz\Plugin\Hook::forge('Foolz\Foolfuuka\Model\Content::routes.collection')
            ->setParams([
                'default_suffix' => 'page',
                'suffix' => 'page',
                'controller' => '\Foolz\Foolfuuka\Controller\Chan::*'
            ])
            ->execute();

        $radix_all = \Foolz\Foolfuuka\Model\Radix::getAll();

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
                '_controller' => '\Foolz\Foolfuuka\Controller\Api\Chan::*',
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
                '_controller' => '\Foolz\Foolfuuka\Controller\Chan::*',
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
                        '_controller' => '\Foolz\Foolfuuka\Controller\Admin\\'.ucfirst($location).'::*',
                    ],
                    [
                        '_suffix' => '.*',
                    ]
                )
            );
        }
    }

    public function handleConsole()
    {
        // no actions
    }
}
