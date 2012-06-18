<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Theme_Plugin_yotsuba_2 extends Plugins_model
{

	function __construct()
	{
		parent::__construct();
	}


	function initialize_plugin()
	{
		$this->plugins->register_controller_function($this, array('chan', 'subtheme', '(:any)'), 'subtheme');
		
		$this->plugins->register_hook($this, 'fu_themes_default_bottom_nav_buttons', -10, function($bottom_nav){
			return array('return' => array_merge(array(
				array('href' => site_url(array('subtheme', 'yotsuba')), 'text' => 'Yotsuba'),
				array('href' => site_url(array('subtheme', 'yotsuba_b')), 'text' => 'Yotsuba B'),
			), $bottom_nav));
		});	
		
		$this->plugins->register_hook($this, 'fu_themes_default_body_classes', 2, 'subtheme_class');
	}

	public function subtheme_class($array)
	{
		$selected = $this->input->cookie('foolfuuka_yotsuba_2_subtheme');
		if(in_array($selected, array('yotsuba', 'yotsuba_b')))
			$array[] = $selected;
		else
			$array[] = 'yotsuba';
		
		return array('return' => $array);
	}

	public function subtheme($theme)
	{
		if(!in_array($theme, array('yotsuba', 'yotsuba_b')))
			$theme = 'yotsuba';
		$this->theme->set_title(__('Changing Subtheme'));
		$this->input->set_cookie('foolfuuka_yotsuba_2_subtheme', $theme, 31536000);
		if ($this->input->server('HTTP_REFERER') && strpos($this->agent->referrer(), site_url()) === 0) :
			$this->theme->bind('url', $this->input->server('HTTP_REFERER'));
		else :
			$this->theme->bind('url', site_url());
		endif;
		$this->theme->set_layout('redirect');
		$this->theme->build('redirection');
	}


	/**
	 * @param int $page
	 */
	public function page($page = 1)
	{
		return array('parameters' => array($page, FALSE, array('per_page' => 24, 'type' => 'by_thread')));
	}


}
