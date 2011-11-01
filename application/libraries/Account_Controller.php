<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Account_Controller extends MY_Controller {

	public function __construct() {
		parent::__construct();
		if($this->uri->segment(2) == "login")
		$this->tank_auth->is_logged_in() or redirect('/account/auth/login');
		$user = new user($this->tank_auth->get_user_id());
		
		$this->viewdata["user_email"] = $user->email;
	}
}