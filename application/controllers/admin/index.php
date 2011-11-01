<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Index extends Admin_Controller {

	function __construct() {
		parent::__construct();
		$this->viewdata['controller_title'] = 'Dashboard';
	}

	function index() {
		redirect('/admin/dashboard/');
	}

}

/* End of file index.php */
/* Location: ./application/controllers/admin/index.php */