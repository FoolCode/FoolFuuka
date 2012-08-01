<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');


class FU_Comment_Hostname extends Plugins_model
{
	function initialize_plugin()
	{
		Plugins::register_hook($this, 'model/post/comment/extra_json', 3, 'comment_hostname');
	}
	
	function comment_hostname($comment, $prev_result = NULL){
		$hostname = gethostbyaddr(inet_dtop($this->input->ip_address()));

		if(is_array($prev_result))
		{
			return array('return' => array_merge($prev_result, array('hostname' => $hostname)));
		}

		return array('return' => array('hostname' => $hostname));
	}
}

