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
		if (!is_natural($page) || $page > 500)
		{
			show_404();
		}

		$page = intval($page);
		
		$board = $this->db->protect_identifiers('woxxy_tv').'.' . $this->db->protect_identifiers(get_selected_board()->shortname);

		// get exactly 10 be it thread starters or parents with distinct parent
		$query = $this->db->query('
			SELECT DISTINCT( IF(parent = 0, num, parent)) as unq_parent
			FROM '.$board.'
			ORDER BY num DESC
			LIMIT ' . (($page * 10) - 10) . ',10
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

		$url = site_url($this->fu_board . '/thread/' . $post->parent) . '#' . $post->num;

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


	// $query, $username = NULL, $tripcode = NULL, $deleted = 0, $internal = 0, $order = 'desc'
	public function search()
	{
		$search = $this->uri->ruri_to_assoc(2, array('text', 'username', 'tripcode', 'deleted', 'ghost', 'order'));


		if (get_selected_board()->sphinx)
		{
			$this->load->library('SphinxClient');
			$this->sphinxclient->SetServer(
					// gotta turn the port into int
					get_setting('fs_sphinx_hostname') ? get_setting('fs_sphinx_hostname') : 'localhost', get_setting('fs_sphinx_hostname') ? get_setting('fs_sphinx_port') : 3312
			);
			
			$this->sphinxclient->SetLimits(0, 25);

			if ($search['username'])
			{
				$this->sphinxclient->setFilter('name', $search['username']);
			}
			
			if ($search['tripcode'])
			{
				$this->sphinxclient->setFilter('trip', $search['tripcode']);
			}
			
			if ($search['text'])
			{
			//	$this->sphinxclient->setFilter('comment', $search['text']);
			}
			
			if ($search['deleted'] == "deleted")
			{
				$this->sphinxclient->setFilter('is_deleted', 1);
			}
			if ($search['deleted'] == "not-deleted")
			{
				$this->sphinxclient->setFilter('is_deleted', 0);
			}
			
			if ($search['ghost'] == "only")
			{
				$this->sphinxclient->setFilter('is_internal', 1);
			}
			if ($search['ghost'] == "none")
			{
				$this->sphinxclient->setFilter('is_internal', 0);
			}

			$this->sphinxclient->setMatchMode(SPH_MATCH_EXTENDED2);
			$this->sphinxclient->setSortMode(SPH_SORT_ATTR_DESC, 'num');
			print_r($this->sphinxclient->query($search['text']), 'a_ancient a_main a_delta');
		}
/*
		$posts = new Post();
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Search: ' . $query);
		//$this->template->set('posts', $posts);
		$this->template->build('board');
 * 
 */
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