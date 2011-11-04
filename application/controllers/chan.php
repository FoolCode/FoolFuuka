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
		$boards = new Board();
		$boards->order_by('shortname', 'ASC')->get();
		$this->template->set('boards', $boards);
		$this->template->set_layout('chan');
	}


	/*
	 * Show the boards
	 */
	public function index()
	{
		$this->template->title(get_setting('fs_gen_title'));
		$this->template->set('disable_headers', TRUE);
		$this->template->build('index');
	}
	
	public function board($page = 1)
	{
		$posts = new Post();
		$posts->where('post_id', 0)->limit(25)->get();
		foreach($posts->all as $key => $post)
		{
			$posts->all[$key]->post = new Post();
			$posts->all[$key]->post->where('post_id', $post->id)->order_by('id', 'DESC')->limit(5)->get();
		}
		
		$this->template->title(_('Team'));
		$this->template->set('posts', $posts);
		$this->template->build('board');
	}
	
	public function thread($id = 0)
	{
		if(!is_numeric($id) || !$id > 0)
		{
			show_404();
		}
		
		$thread = new Post();
		$thread->where('id', $id)->limit(1)->get();
		if($thread->result_count() == 0)
		{
			show_404();
		}
		
		$thread->all[0]->post = new Post();
		$thread->all[0]->post->where('post_id', $id)->order_by('id', 'DESC')->get();
		$this->template->title(_('Team'));
		$this->template->set('posts', $thread);
		$this->template->build('board');
	}
	
	public function post($id = 0)
	{
		if(!is_numeric($id) || !$id > 0)
		{
			show_404();
		}
		
		$post = new Post();
		$post->where('id', $id)->get();
		if($post->result_count() == 0)
		{
			show_404();
		}
		
		$url = site_url($this->fu_board . '/thread/' . $post->post_id) . '#' . $post->id;
		
		$this->template->title(_('Redirecting...'));
		$this->template->set('url', $url);
		$this->template->build('redirect');
	}
	
	public function ghost($page = 1)
	{
		
	}
	
	public function _remap($method, $params = array())
	{
		$this->fu_board = $method;
		if(isset($params[0]))
		{
			$board = new Board();
			if(!$board->check_shortname($this->fu_board))
			{
				show_404();
			}
			$this->template->set('board', $board);
			$method = $params[0];
			array_shift($params);
		}
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