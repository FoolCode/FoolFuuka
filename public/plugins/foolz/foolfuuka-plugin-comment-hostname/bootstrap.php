<?php

use \Foolz\Inet\Inet;

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-comment-hostname')
    ->setCall(function($result) {

        \Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\CommentInsert::insert.result.extra_json_array')
            ->setCall(function($result){
                $comment = $result->getObject();
                if ($comment->poster_ip) {
                    $comment->extra->json_array['hostname'] = gethostbyaddr(Inet::dtop($comment->poster_ip));
                }
            })->setPriority(3);

        \Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Comment::cleanFields.call.before.method.body')
            ->setCall(function($result){
                $comment = $result->getObject();
                if (!\Auth::has_access('maccess.mod')) {
                    if ($comment->extra instanceof \Foolfuuka\Model\Extra)
                        unset($comment->extra->json_array['hostname']);
                }
            })->setPriority(5);

    });
