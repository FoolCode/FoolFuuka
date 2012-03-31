<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * READER CONTROLLER
 *
 * This file allows you to override the standard FoOlSlide controller to make
 * your own URLs for your theme, and to make sure your theme keeps working
 * even if the FoOlSlide default theme gets modified.
 *
 * For more information, refer to the support sites linked in your admin panel.
 */
class Theme_Controller
{


	function __construct()
	{
		$this->CI = & get_instance();
	}

	public function hot($page = 1)
	{
		$this->page($page);
	}
	
	
	/**
	 * @param int $page
	 */
	public function page($page = 1)
	{
		$this->CI->input->set_cookie('foolfuuka_default_theme_by_thread', '0',
			60 * 60 * 24 * 30);
		/**
		 * Remap everything to default theme and override array.
		 */
		$this->CI->page($page, FALSE, array('per_page' => 24, 'type' => 'by_post'));
	}


	public function newest($page = 1)
	{
		$this->CI->input->set_cookie('foolfuuka_default_theme_by_thread', '1',
			60 * 60 * 24 * 30);
		/**
		 * Remap everything to default theme and override array.
		 */
		$this->CI->page($page, FALSE, array('per_page' => 24, 'type' => 'by_thread'));
	}


	/**
	 * Disable GALLERY for this theme by showing 404!
	 */
	public function gallery()
	{
		show_404();
	}


}