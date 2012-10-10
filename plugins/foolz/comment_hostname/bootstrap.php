<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');


\Foolz\Plugin\Event::forge('fu.comment.insert.extra_json_array')
	->setCall(function($result){
		$comment = $result->getObject();
		if ($comment->poster_ip)
		{
			$comment->extra->json_array['hostname'] = gethostbyaddr(\Inet::dtop($comment->poster_ip));
		}
	})->setPriority(3);

\Foolz\Plugin\Event::forge('foolfuuka\\model\\comment.clean_fields.call.before')
	->setCall(function($result){
		$comment = $result->getObject();
		if( ! \Auth::has_access('maccess.mod'))
		{
			if ($comment->extra instanceof \Foolfuuka\Model\Extra)
				unset($comment->extra->json_array['hostname']);
		}
	})->setPriority(5);