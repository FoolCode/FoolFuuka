<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Post extends DataMapper
{

	var $table = 'board_a_posts';
	var $has_one = array();
	var $has_many = array(
		'relatedpost' => array(
			'class' => 'post',
			'join_table' => 'board_a_posts'
		)
	);
	var $validation = array(
		'subnum' => array(
			'rules' => array(),
			'label' => 'Password'
		),
		'post_id' => array(
			'rules' => array(),
			'label' => 'Email',
			'type' => 'input'
		),
		'timestamp' => array(
			'rules' => array(),
			'label' => 'Activated'
		),
		'preview' => array(
			'rules' => array(),
			'label' => 'Banned'
		),
		'preview_w' => array(
			'rules' => array(),
			'label' => 'Ban reason'
		),
		'preview_h' => array(
			'rules' => array(),
			'label' => 'New password key'
		),
		'media' => array(
			'rules' => array(),
			'label' => 'New password request'
		),
		'media_w' => array(
			'rules' => array(),
			'label' => 'New email'
		),
		'media_h	' => array(
			'rules' => array(),
			'label' => 'New email key'
		),
		'media_size' => array(
			'rules' => array(),
			'label' => 'Last IP'
		),
		'media_hash' => array(
			'rules' => array(),
			'label' => 'Last login'
		),
		'media_filename' => array(
			'rules' => array(),
			'label' => 'Modified'
		),
		'spoiler' => array(
			'rules' => array(),
			'label' => 'New password key'
		),
		'deleted' => array(
			'rules' => array(),
			'label' => 'New password request'
		),
		'capcode' => array(
			'rules' => array(),
			'label' => 'New email'
		),
		'email' => array(
			'rules' => array(),
			'label' => 'New email key'
		),
		'name' => array(
			'rules' => array(),
			'label' => 'Last IP'
		),
		'trip' => array(
			'rules' => array(),
			'label' => 'Last login'
		),
		'title' => array(
			'rules' => array(),
			'label' => 'Modified'
		),
		'comment' => array(
			'rules' => array(),
			'label' => 'Last login'
		),
		'delpass' => array(
			'rules' => array(),
			'label' => 'Modified'
		)
	);

	function __construct($id = NULL)
	{
		parent::__construct($id);
	}


	function post_model_init($from_cache = FALSE)
	{
		
	}


}