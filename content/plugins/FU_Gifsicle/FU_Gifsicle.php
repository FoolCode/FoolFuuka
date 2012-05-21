<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class FF_SSL_Tools extends Plugins_model
{


	function __construct()
	{
		// KEEP THIS EMPTY, use the initialize_plugin method instead

		parent::__construct();
	}
	
	function initialize_plugin()
	{
		$this->plugins->register_hook($this, 'fu_post_model_process_media_switch_thumbnailing', 4, 'gifsicle');
	}
	
	
	function gifsicle($media_config)
	{
		if(strtolower(pathinfo($media_config['soruce_image'], PATHINFO_EXTENSION)) != 'gif')
		{
			return NULL;
		}
		
		@exec('gifsicle -O2 --resize-fit ' . $media_config['width'] . 'x' . $media_config['height'] . ' < ' . $media_config['source_image'] . ' > ' . $media_config['new_image']);
		
		return TRUE;
	}
}