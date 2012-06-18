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
		$this->plugins->register_hook($this, 'fu_themes_default_bottom_nav_buttons', -10, function($bottom_nav){
			return array('return' => array_merge(array(
				array('href' => site_url(array('@system', 'functions', 'theme', 'yotsuba_2', 'yotsuba')), 'text' => 'Yotsuba'),
				array('href' => site_url(array('@system', 'functions', 'theme', 'yotsuba_2', 'yotsuba_b')), 'text' => 'Yotsuba B'),
			), $bottom_nav));
		});
	}


	/**
	 * @param int $page
	 */
	public function page($page = 1)
	{
		return array('parameters' => array($page, FALSE, array('per_page' => 24, 'type' => 'by_thread')));
	}


}
