<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Chan extends Public_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('template');
		$this->load->helper('number');
		$this->template->set_layout('chan');
	}


	/*
	 * Show the boards
	 */
	public function index()
	{
		echo 'here';
	}
	
	public function board($page = 1)
	{
		
		$this->fu_board;
		$posts = new Post();
		
		$posts->where('post_id', 0)->limit(25)->get();
		foreach($posts->all as $key => $post)
		{
			$posts->all[$key]->post = new Post();
			$posts->all[$key]->post->where('post_id', $post->id)->limit(5)->get();
		}
		
		$this->template->title(_('Team'));
		$this->template->set('posts', $posts);
		$this->template->build('board');
	}
	
	public function thread($id)
	{
		
	}
	
	public function post($id)
	{
		
	}
	
	public function ghost($page = 1)
	{
		
	}
	
	public function _remap($method, $params = array())
	{
		$this->fu_board = $method;
		$method = $params[0];
		
		/**
		 * ADD CHECK IF BOARD EXISTS
		 */
		
		if (method_exists($this->TC, $method))
		{
			return call_user_func_array(array($this->TC, $method), $params);
		}
		
		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}
	
}