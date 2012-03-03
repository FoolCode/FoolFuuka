<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Plugin extends Admin_Controller
{
	/*
	 * Activating the Admin_Controller's __construct also for plugins
	 */

	function __construct()
	{
		parent::__construct();
		echo 'admin panel';

		
	}

}