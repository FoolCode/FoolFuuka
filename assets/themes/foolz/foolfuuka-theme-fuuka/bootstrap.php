<?php

use Foolz\Plugin\Event;
use Symfony\Component\Routing\Route;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/controller.php';

Event::forge('Foolz\Foolfuuka\Model\Context::loadRoutes#obj.afterRouting')
    ->setCall(function($result) {
        $obj = $result->getObject();
        foreach ($obj->context->getService('foolfuuka.radix_collection')->getAll() as $radix) {
            $obj->context->getRouteCollection()->add(
                'foolfuuka.chan.radix.'.$radix->shortname, new Route(
                '/'.$radix->shortname.'/{_suffix}',
                [
                    '_default_suffix' => 'page',
                    '_suffix' => 'page',
                    '_controller' => '\Foolz\Foolfuuka\Themes\Fuuka\Controller\Chan::*',
                    'radix_shortname' => $radix->shortname
                ],
                [
                    '_suffix' => '.*'
                ]
            ));
        }
    });

// use hooks for manipulating comments
Event::forge('Foolz\Foolfuuka\Model\Comment::processComment#var.greentext')
    ->setCall(function($result) {
        $html= '\\1<span class="greentext">\\2</span>\\3';;
        $result->setParam('html', $html)->set($html);
    })
    ->setPriority(8);

Event::forge('Foolz\Foolfuuka\Model\Comment::processInternalLinks#var.link')
    ->setCall(function($result) {
        $data = $result->getParam('data');
        $html = [
            'tags' => ['<span class="unkfunc">', '</span>'],
            'hash' => '',
            'attr' => 'class="backlink" onclick="replyHighlight(' . $data->num . ');"',
            'attr_op' => 'class="backlink"',
            'attr_backlink' => 'class="backlink"',
        ];

        $result->setParam('build_url', $html)->set($html);
    })
    ->setPriority(8);

Event::forge('Foolz\Foolfuuka\Model\Comment::processExternalLinks#var.link')
    ->setCall(function($result) {
        $data = $result->getParam('data');
        $html = [
            'tags' => ['open' =>'<span class="unkfunc">', 'close' => '</span>'],
            'short_link' => '//boards.4chan.org/'.$data->shortname.'/',
            'query_link' => '//boards.4chan.org/'.$data->shortname.'/res/'.$data->query,
            'backlink_attr' => 'class="backlink"',
            'attributes' => ''
        ];

        $result->setParam('build_url', $html)->set($html);
    })
    ->setPriority(8);
