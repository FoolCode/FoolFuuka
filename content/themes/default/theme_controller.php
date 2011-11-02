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

class Reader_Controller {

	function __construct() {
		$this->CI = & get_instance();
	}
	
	
	/**
	 * 
	 * Example function that overrides the index page
	 * 
	 */
	//public function index($page = 1) {
	//	if($this->CI->tank_auth->is_logged_in()) 
	//		echo 'here';
	//}

}