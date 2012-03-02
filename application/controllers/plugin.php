<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Plugin extends MY_Controller
{


	function __construct()
	{
		parent::__construct();
	}
	
	function test()
	{
		echo $this->tank_auth->is_allowed()?'yes':'no';
	}

}