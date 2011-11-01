<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Loadbalancer extends DataMapper {

	var $has_one = array();
	var $has_many = array();
	var $validation = array(
		'url' => array(
			'rules' => array('required', 'unique'),
			'label' => 'URL',
			'placeholder' => 'required',
			'required' => 'required',
			'type' => 'input',
			'help' => 'Address to the load balancing FoOlSlide. Can be an URL or an IP. Examples: http://yoursite/slide/ or 34.54.243.23/slide'
		),
		'ip' => array(
			'rules' => array(),
			'label' => 'IP',
			'type' => 'input',
			'help' => 'Absolutely add this if your server has static IP. It should increate security'
		),
		'key' => array(
			'rules' => array('required'),
			'label' => 'Secret key',
			'type'	=> 'input',
			'placeholder' => 'required',
			'required' => 'required',
			'help' => 'The key that your loadbalancing FoOlSlide is showing in its Loadbalancer/client page. Allows avoiding certain locks, like the nationality filter'
		), 
		'status' => array(
			'rules' => array(),
			'label' => 'Status'
		),
		'backlog' => array(
			'rules' => array(),
			'label' => 'Bio',
		)
	);
	
	function __construct($id = NULL) {		
		parent::__construct($id);
	}

	function post_model_init($from_cache = FALSE) {
		
	}

}