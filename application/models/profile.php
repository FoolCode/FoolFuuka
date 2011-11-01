<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Profile extends DataMapper {

	var $has_one = array('user');
	var $has_many = array();
	var $validation = array(
		'user_id' => array(
			'rules' => array(),
			'label' => 'User ID'
		),
		'group_id' => array(
			'rules' => array(),
			'label' => 'Group ID',			
		),
		'display_name' => array(
			'rules' => array('max_length' => 20),
			'label' => 'Publicly displayed username',
			'type'	=> 'input'
		), 
		'twitter' => array(
			'rules' => array('max_length' => 30),
			'label' => 'Twitter username',
			'type'	=> 'input'
		),
		'bio' => array(
			'rules' => array('max_length' => 140),
			'label' => 'Bio',
			'type'	=> 'textarea'
		)
	);
	
	function __construct($id = NULL) {		
		parent::__construct($id);
	}

	function post_model_init($from_cache = FALSE) {
		
	}
	
	function change_group($user_id, $group_id)
	{
		$CI = & get_instance();
		if(!$CI->tank_auth->is_admin()) return false;
		
		$this->where('user_id', $user_id)->get();
		$this->group_id = $group_id;
		if(!$this->save())
		{
			log_message('error', 'change_group(): Could not change group.');
			return false;
		}
		return true;
	}

}