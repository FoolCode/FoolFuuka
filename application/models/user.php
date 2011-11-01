<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class User extends DataMapper {

	var $has_one = array('profile');
	var $has_many = array();
	var $validation = array(
		'username' => array(
			'rules' => array(),
			'label' => 'Username',
			'type'	=> 'input'
		),
		'password' => array(
			'rules' => array(),
			'label' => 'Password'
		),
		'email' => array(
			'rules' => array(),
			'label' => 'Email',
			'type'	=> 'input'
		),
		'activated' => array(
			'rules' => array(),
			'label' => 'Activated'
		),
		'banned' => array(
			'rules' => array(),
			'label' => 'Banned'
		),
		'ban_reason' => array(
			'rules' => array(),
			'label' => 'Ban reason'
		),
		'new_password_key' => array(
			'rules' => array(),
			'label' => 'New password key'
		),
		'new_password_request' => array(
			'rules' => array(),
			'label' => 'New password request'
		),
		'new_email' => array(
			'rules' => array(),
			'label' => 'New email'
		),
		'new_email_key' => array(
			'rules' => array(),
			'label' => 'New email key'
		),
		'last_ip' => array(
			'rules' => array(),
			'label' => 'Last IP'
		),
		'last_login' => array(
			'rules' => array(),
			'label' => 'Last login'
		),
		'modified' => array(
			'rules' => array(),
			'label' => 'Modified'
		)
	);
	
	function __construct($id = NULL) {		
		parent::__construct($id);
	}

	function post_model_init($from_cache = FALSE) {
		
	}
}