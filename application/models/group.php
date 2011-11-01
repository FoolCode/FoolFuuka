<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Group extends DataMapper {

	var $has_one = array('user_profile');
	var $has_many = array();
	var $validation = array(
		'name' => array(
			'rules' => array(),
			'label' => 'Group name'
		),
		'Description' => array(
			'rules' => array(),
			'label' => 'Description'
		)
	);
	
	function __construct($id = NULL) {		
		parent::__construct($id);
	}

	function post_model_init($from_cache = FALSE) {
		
	}
}