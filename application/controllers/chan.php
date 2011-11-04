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
	
	public function page($page = 1)
	{
		$posts = new Post();
		$posts->where('parent', 0)->get_paged($page, 25);
		foreach($posts->all as $key => $post)
		{
			$posts->all[$key]->post = new Post();
			$posts->all[$key]->post->where(array('parent' => $post->num, 'subnum' => 0))->order_by('num', 'DESC')->limit(5)->get();
		}
		
		$this->template->title('/'. get_selected_board()->shortname .'/ - '. get_selected_board()->name);
		$this->template->set('posts', $posts);
		$this->template->build('board');
	}
	
	public function thread($num = 0)
	{
		if(!is_numeric($num) || !$num > 0)
		{
			show_404();
		}
		
		$thread = new Post();
		$thread->where('num', $num)->limit(1)->get();
		if($thread->result_count() == 0)
		{
			show_404();
		}
		
		$thread->all[0]->post = new Post();
		$thread->all[0]->post->where('parent', $num)->order_by('num', 'DESC')->get();
		$this->template->title(_('Team'));
		$this->template->set('posts', $thread);
		$this->template->build('board');
	}
	
	public function post($num = 0)
	{
		if(!is_numeric($num) || !$num > 0)
		{
			show_404();
		}
		
		$post = new Post();
		$post->where('num', $num)->get();
		if($post->result_count() == 0)
		{
			show_404();
		}
		
		$url = site_url($this->fu_board . '/thread/' . $post->parent) . '#' . $post->num;
		
		$this->template->title(_('Redirecting...'));
		$this->template->set('url', $url);
		$this->template->build('redirect');
	}
	
	public function ghost($page = 1)
	{
		$posts = new Post();
		$posts->where('parent', 0)->get_paged($page, 25);
		foreach($posts->all as $key => $post)
		{
			$posts->all[$key]->post = new Post();
			$posts->all[$key]->post->where('parent', $post->num)->order_by('num', 'DESC')->limit(5)->get();
		}
		
		$this->template->title('/'. get_selected_board()->shortname .'/ - '. get_selected_board()->name);
		$this->template->set('posts', $posts);
		$this->template->build('board');
	}
	
	public function image($hash = NULL, $limit = 25)
	{
		if($hash == NULL || !is_numeric($limit))
		{
			show_404();
		}
		
		$posts = new Post();
		$posts->where('media_hash', $hash . '==')->limit($limit)->order_by('num', 'DESC')->get();
		$this->template->title(_('Image'));
		$this->template->set('posts', $posts);
		$this->template->build('board');
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