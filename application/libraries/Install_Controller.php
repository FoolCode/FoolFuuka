<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Install_Controller extends MY_Controller {

	public function __construct() {
		parent::__construct();
		$this->viewdata["sidebar"] = "";
	}

}