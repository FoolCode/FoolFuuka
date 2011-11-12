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
		$this->template->set_partial('top_tools', 'top_tools');
		$this->template->set('boards', $boards);
		$this->template->set_partial('reply', 'reply');
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
		$this->remap_query();
		
		if (!is_natural($page) || $page > 500)
		{
			show_404();
		}
		
		$board = $this->db->protect_identifiers('board_' . get_selected_board()->shortname, TRUE);

		// get exactly 10 be it thread starters or parents with distinct parent
		$query = $this->db->query('
			SELECT DISTINCT( IF(parent = 0, num, parent)) as unq_parent
			FROM ' . $board . '
			ORDER BY num DESC
			LIMIT '. (($page*10)-10) . ',10
		');
		
		// get the IDs of the threads to fetch
		$threads = array();
		$posts = array(); // an associative array for later

		foreach ($query->result() as $row)
		{
			$threads[] = $row->unq_parent;
		}

		$sql = array();
		rsort($threads);
		foreach ($threads as $thread)
		{
			$sql[] = '
				(
					SELECT *
					FROM ' . $board . '
					WHERE num = ' . $thread . ' OR parent = ' . $thread . '
					ORDER BY num DESC
				)
			';
		}

		$sql = implode('UNION', $sql) . '
			ORDER BY num DESC
		';


		$posts = new Post();
		$posts->query($sql);


		foreach ($posts->all as $key => $post)
		{
			if ($post->parent > 0)
			{
				foreach ($posts->all as $k => $p)
				{
					if ($p->num == $post->parent)
					{
						if (count($posts->all[$k]->post->all) < 5)
							$posts->all[$k]->post->all[] = $post->get_copy();

						else
						{
							$posts->all[$k]->omitted++;
						}
					}
				}
				unset($posts->all[$key]);
			}
		}

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name);
		$this->template->set('posts', $posts);
		$this->template->set('is_page', true);
		$this->template->build('board');
	}


	public function ghost($page = 1)
	{
		$values = array();
		$this->db->select('parent')->from('board_' . get_selected_board()->shortname . '_local')->order_by('num', 'DESC');
		$ghosts = $this->db->get();
		foreach ($ghosts->result() as $key => $ghost)
		{
			$values[] = $ghost->parent;
		}

		if (empty($values))
		{
			$values[] = '';
		}

		$posts = new Post();
		$posts->where_in('num', $values)->get_paged($page, 25);
		foreach ($posts->all as $key => $post)
		{
			$posts->all[$key]->post = new Post();
			$posts->all[$key]->post->where('parent', $post->num)->order_by('num', 'DESC')->limit(5)->get();
		}

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name);
		$this->template->set('posts', $posts);
		$this->template->build('board');
	}


	public function thread($num = 0)
	{
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		$thread = new Post();
		$thread->where('num', $num)->limit(1)->get();
		if ($thread->result_count() == 0)
		{
			show_404();
		}

		$post_data = '';
		if ($this->input->post())
		{
			// LETS HANDLE THE GHOST POSTS HERE
			$post_data = $this->input->post();
		}

		$thread->all[0]->post = new Post();
		$thread->all[0]->post->where('parent', $num)->order_by('num', 'DESC')->get();
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Thread #' . $num);
		$this->template->set('posts', $thread);

		$this->template->set_partial('reply', 'reply', array('thread_id' => $num, 'post_data' => $post_data));
		$this->template->build('board');
	}


	public function post($num = 0)
	{
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		$post = new Post();
		$post->where('num', $num)->get();
		if ($post->result_count() == 0)
		{
			show_404();
		}

		if ($post->parent == 0)
		{
			$url = site_url($this->fu_board . '/thread/' . $post->num) . '#' . $post->num;
		}
		else
		{
			$url = site_url($this->fu_board . '/thread/' . $post->parent) . '#' . $post->num;
		}
		
		$this->template->title(_('Redirecting...'));
		$this->template->set('url', $url);
		$this->template->build('redirect');
	}


	public function image($hash, $limit = 25)
	{
		if ($hash == '' || !is_numeric($limit))
		{
			show_404();
		}

		$posts = new Post();
		$posts->where('media_hash', urldecode($hash) . '==')->limit($limit)->order_by('num', 'DESC')->get();
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Image: ' . urldecode($hash));
		$this->template->set('posts', $posts);
		$this->template->build('board');
	}


	public function search($params = array())
	{	
		$params = $this->uri->uri_to_assoc(3);
		// Build Initial SQL Statement
			
		if (!empty($params))
		{
				// Build SQL Statement with Parameters
		}
		
		$posts = new Post();
		$posts->where('media_hash', '==')->limit(25)->order_by('num', 'DESC')->get();
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Search: ' . implode($params, ':'));
		$this->template->set('posts', $posts);
		$this->template->build('board');
	}

	
	public function report($num = 0)
	{
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}
	}

	
	public function remap_query()
	{
		$params = '';
		
		// Page Redirect
		if ($this->input->get('task') == "page")
		{
			if ($this->input->get('page') != "")
			{
				$params = 'page/' . $this->input->get('page') . '/';
			}
			
			if ($this->input->get('ghost') != "")
			{
				$params = 'ghost/' . $this->input->get('page') . '/';
			}
		}
		
		// Search Redirect
		if ($this->input->get('task') == "search" || $this->input->get('task') == "search2")
		{
			$params = 'search/';
			
			// Build Redirect for Search
			if ($this->input->get('search_text') != "")
			{
				$params .= 'text/' . $this->input->get('search_text') . '/';
			}
			
			if ($this->input->get('search_username') != "")
			{
				$params .= 'username/' . $this->input->get('search_username') . '/';
			}
			
			if ($this->input->get('search_tripcode') != "")
			{
				$params .= 'tripcode/' . $this->input->get('search_tripcode') . '/';
			}
			
			if ($this->input->get('search_del') != "")
			{
				$del = str_replace('dontcare', '', $this->input->get('search_del'));
				if ($del != "")
				{
					$params .= 'deleted/' . $del . '/';
				}
			}
			
			if ($this->input->get('search_int') != "")
			{
				$int = str_replace('dontcare', '', $this->input->get('search_int'));
				if ($int != "")
				{
					$params .= 'internal/' . $int . '/';
				}
			}
			
			if ($this->input->get('search_ord') != "")
			{
				$ord = str_replace(array('old', 'new'), array('asc', 'desc'), $this->input->get('search_ord'));
				$params .= 'order/' . $ord . '/';
			}
		}
		
		if ($params != "")
		{
			$url = site_url($this->fu_board . '/' . $params);
			$this->template->title(_('Redirecting...'));
			$this->template->set('url', $url);
			die($this->template->build('redirect'));
		}
	}
	
	public function _remap($method, $params = array())
	{
		$this->fu_board = $method;
		if (isset($params[0]))
		{
			$board = new Board();
			if (!$board->check_shortname($this->fu_board))
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