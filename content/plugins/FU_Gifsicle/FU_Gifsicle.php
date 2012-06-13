<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class FU_Gifsicle extends Plugins_model
{

	
	function initialize_plugin()
	{
		$this->plugins->register_hook($this, 'fu_post_model_process_media_switch_resize', 4, 'gifsicle');
	}
	
	
	function gifsicle($media_config, $parameter = NULL)
	{
		if($parameter === FALSE)
		{
			// if false, the processing has failed in some other plugin
			return array('return' => FALSE, 'parameters' => array($media_config, FALSE));
		}
		
		if(strtolower(pathinfo($media_config['source_image'], PATHINFO_EXTENSION)) != 'gif')
		{
			return NULL;
		}
		
		$this->load->library('image_lib');

		$this->image_lib->initialize($media_config);
		if (!$this->image_lib->resize())
		{
			return array('return' => FALSE, 'parameters' => array($media_config, FALSE));
		}

		$this->image_lib->clear();

		@exec('gifsicle -b --colors 256 -O2 ' . $media_config['new_image']);
		
		return array('return' => TRUE, 'parameters' => array($media_config, $parameter));
	}
}