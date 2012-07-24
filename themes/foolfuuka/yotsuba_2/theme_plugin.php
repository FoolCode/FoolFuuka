<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');


class Theme_Plugin_yotsuba_2 extends Plugins_model
{

	function __construct()
	{
		parent::__construct();
	}


	function initialize_plugin()
	{

	}


	/**
	 * @param int $page
	 */
	public function page($page = 1)
	{
		return array('parameters' => array($page, FALSE, array('per_page' => 24, 'type' => 'by_thread')));
	}


}
