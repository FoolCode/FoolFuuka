<?php

use Symfony\Component\Routing\Route;

require_once __DIR__ . '/functions.php';
require_once __DIR__.'/controller.php';

\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Context.loadRoutes.after')
    ->setCall(function($result) {
        foreach ($this->context->getService('foolfuuka.radix_collection')->getAll() as $radix) {
            $this->context->getRouteCollection()->add(
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
\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Comment::processComment.result.greentext')
    ->setCall(function($result) {
        $html= '\\1<span class="greentext">\\2</span>\\3';;
        $result->setParam('html', $html)->set($html);
    })
    ->setPriority(8);

\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Comment::processInternalLinks.result.html')
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

\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Comment::processExternalLinks.result.html')
    ->setCall(function($result) {
        $data = $result->getParam('data');
        $html = [
            'tags' => ['open' =>'<span class="unkfunc">', 'close' => '</span>'],
            'short_link' => '//boards.4chan.org/'.$data->shortname.'/',
            'query_link' => '//boards.4chan.org/'.$data->shortname.'/res/'.$data->query,
            'backlink_attr' => 'class="backlink"'
        ];

        $result->setParam('build_url', $html)->set($html);
    })
    ->setPriority(8);
