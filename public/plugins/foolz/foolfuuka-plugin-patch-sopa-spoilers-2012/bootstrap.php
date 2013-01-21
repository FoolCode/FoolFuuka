<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/patch_sopa_spoilers_2012')
	->setCall(function($result) {
		$event = new \Foolz\Plugin\Event('Foolz\Foolfuuka\Model\Comment.processComment.call.before');
		$event->setCall(function($result)
		{
			$post = $result->getObject();

			// the comment checker may be running and timestamp may not be set, otherwise do the check
			if (isset($post->timestamp) && $post->timestamp > 1326840000 && $post->timestamp < 1326955000)
			{
				if (strpos($post->comment, '</spoiler>') > 0)
				{
					$post->comment = str_replace(['[spoiler]', '[/spoiler]', '</spoiler>'], '', $post->comment);
				}

				if (preg_match('/^\[spoiler\].*\[\/spoiler\]$/s', $post->comment))
				{
					$post->comment = str_replace(['[spoiler]', '[/spoiler]'], '', $post->comment);
				}
			}
		});
	});