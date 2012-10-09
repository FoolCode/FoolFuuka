<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

$event = new \Foolz\Plugin\Event('foolfuuka\model\comment.process_comment.call.before');
$event->setCall(function($result)
{
	$post = $result->getObject();

	// the comment checker may be running and timestamp may not be set, otherwise do the check
	if (isset($post->timestamp) && $post->timestamp > 1326840000 && $post->timestamp < 1326955000)
	{
		if (strpos($post->comment, '</spoiler>') > 0)
		{
			$post->comment = str_replace(array('[spoiler]', '[/spoiler]', '</spoiler>'), '', $post->comment);
		}

		if (preg_match('/^\[spoiler\].*\[\/spoiler\]$/s', $post->comment))
		{
			$post->comment = str_replace(array('[spoiler]', '[/spoiler]'), '', $post->comment);
		}
	}

});