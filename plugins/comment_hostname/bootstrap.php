<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');


\Plugins::register_hook('fu.comment.insert.extra_json_array', function($comment){
	
	if ($comment->poster_ip)
	{
		$comment->extra->json_array['hostname'] = gethostbyaddr(\Inet::dtop($comment->poster_ip));
	}
			
}, 3);
