<?php

use Foolz\Foolfuuka\Model\Comment;

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/patch_sopa_spoilers_2012')
    ->setCall(function($result) {
        $event = new \Foolz\Plugin\Event('Foolz\Foolfuuka\Model\Comment.processComment.call.before');
        $event->setCall(function($result) {
            /** @var Comment $post */
            $post = $result->getObject();

            // the comment checker may be running and timestamp may not be set, otherwise do the check
            if (isset($post->comment->timestamp) && $post->comment->timestamp > 1326840000 && $post->comment->timestamp < 1326955000) {
                if (strpos($post->comment, '</spoiler>') > 0) {
                    $post->comment->comment = str_replace(['[spoiler]', '[/spoiler]', '</spoiler>'], '', $post->comment->comment);
                }

                if (preg_match('/^\[spoiler\].*\[\/spoiler\]$/s', $post->comment->comment)) {
                    $post->comment->comment = str_replace(['[spoiler]', '[/spoiler]'], '', $post->comment->comment);
                }
            }
        });
    });
