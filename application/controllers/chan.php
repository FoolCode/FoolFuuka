<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Chan extends Public_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('template');
		$this->load->helper('cookie');
		$this->load->helper('number');
		$boards = new Board();
		$boards->order_by('shortname', 'ASC')->get();
		$this->template->set_partial('top_tools', 'top_tools');
		$this->template->set('boards', $boards);
		$this->template->set_partial('post_reply', 'post_reply');
		$this->template->set_partial('post_tools', 'post_tools');
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
		$this->input->set_cookie('fu_ghost_mode', FALSE, 86400);
		if ($this->input->post())
		{
			redirect($this->fu_board . '/page/' . $this->input->post('page'), 'location', 303);
		}
		
		if (!is_natural($page) || $page > 500)
		{
			show_404();
		}

		$page = intval($page);

		$posts = $this->post->get_latest($page);

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name);
		$this->template->set('posts', $posts);
		$this->template->set('is_page', TRUE);
		$this->template->set('posts_per_thread', 5);
		$this->template->set_partial('top_tools', 'top_tools', array('page' => $page));
		$this->template->build('board');
	}


	public function ghost($page = 1)
	{
		$this->input->set_cookie('fu_ghost_mode', TRUE, 86400);
		if ($this->input->post())
		{
			redirect($this->fu_board . '/ghost/' . $this->input->post('page'), 'location', 303);
		}
		
		$values = array();
		
		if (!is_natural($page) || $page > 500)
		{
			show_404();
		}

		$page = intval($page);

		$posts = $this->post->get_latest_ghost($page);

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name);
		$this->template->set('posts', $posts);
		$this->template->set('is_page', TRUE);
		$this->template->set('posts_per_thread', 5);
		$this->template->set_partial('top_tools', 'top_tools', array('page' => $page));
		$this->template->build('board');
	}


	public function thread($num = 0)
	{
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		$num = intval($num);
		
		// commenting
		$post_data = '';
		if ($this->input->post('reply_action') == 'Submit')
		{
			$this->form_validation->set_rules('reply_bokunonome', 'Username', 'trim|xss_clean|max_lenght[64]');
			$this->form_validation->set_rules('reply_elitterae', 'Email', 'trim|xss_clean|max_lenght[64]');
			$this->form_validation->set_rules('reply_talkingde', 'Subject', 'trim|xss_clean|max_lenght[64]');
			$this->form_validation->set_rules('reply_chennodiscursus', 'Comment', 'trim|min_lenght[3]|max_lenght[1000]|xss_clean');
			$this->form_validation->set_rules('reply_nymphassword', 'Password', 'required|min_lenght[3]|max_lenght[32]|xss_clean');
			
			if ($this->form_validation->run() !== FALSE)
			{
					$data['name'] = $this->input->post('reply_bokunonome');
					$data['email'] = $this->input->post('reply_elitterae');
					$data['subject'] = $this->input->post('reply_talkingde');
					$data['comment'] = $this->input->post('reply_chennodiscursus');
					$data['password'] = $this->input->post('reply_nymphassword');
					$data['num'] = $num;
					
					$this->post->comment($data);
			}
			else
			{
				$this->template->set('reply_errors', validation_errors());
			}
		}
		
		$thread = $this->post->get_thread($num);

		if (count($thread) != 1)
		{
			show_404();
		}


		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Thread #' . $num);
		$this->template->set('posts', $thread);

		$this->template->set('thread_id', $num);
		//$this->template->set_partial('post_reply', 'post_reply', array('thread_id' => $num, 'post_data' => $post_data));
		$this->template->build('board');
	}


	public function post($num = 0)
	{
		if ($this->input->post())
		{
			redirect($this->fu_board . '/post/' . $this->input->post('post'), 'location', 302);
		}
		
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}
		$num = intval($num);

		$thread = $this->post->get_post_thread($num);
		if ($thread === FALSE)
		{
			show_404();
		}

		$url = site_url($this->fu_board . '/thread/' . $thread) . '#' . $num;

		$this->template->title(_('Redirecting...'));
		$this->template->set('url', $url);
		$this->template->build('redirect');
	}


	public function image($hash, $page = 1)
	{
		if ($hash == '' || !is_numeric($page) || $page > 500)
		{
			show_404();
		}
		
		$page = intval($page);
		$posts = $this->post->get_image(urldecode($hash) . '==', $page);
		
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Image: ' . urldecode($hash));
		$this->template->set('posts', $posts);
		$this->template->build('board');
	}


	// $query, $username = NULL, $tripcode = NULL, $deleted = 0, $internal = 0, $order = 'desc'
	public function search()
	{
		$modifiers = array('text', 'username', 'tripcode', 'deleted', 'ghost', 'order', 'page');
		if($this->input->post())
		{
			$redirect_array = array(get_selected_board()->shortname, 'search');
			foreach($modifiers as $modifier)
			{
				if($this->input->post($modifier))
				{
					$redirect_array[] = $modifier;
					$redirect_array[] = rawurlencode($this->input->post($modifier));
				}
			}
			
			redirect(site_url($redirect_array));
		}
		$search = $this->uri->ruri_to_assoc(2, $modifiers);
		$posts = $this->post->get_search($search);

		//echo '<pre>'; print_r($posts); echo '</pre>';
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name);
		$this->template->set('posts', $posts);
		$this->template->set_partial('top_tools', 'top_tools', array('search' => $search));
		$this->template->build('board');
	}

	
	public function report($num = 0)
	{
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}
		
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		
		$post = array(
			'board' => get_selected_board()->id,
			'post' => $this->input->post("post"),
			'reason' => $this->input->post("reason")
		);
			
		$report = new Report();
		if (!$report->add($post))
		{
			$this->output->set_output(json_encode(array('status' => 'failed', 'reason' => '')));
			return false;
		}
		$this->output->set_output(json_encode(array('status' => 'success')));
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
				$del = str_replace(array('dontcare', 'yes', 'no'), array('', 'deleted', 'not-deleted'), $this->input->get('search_del'));
				if ($del != "")
				{
					$params .= 'deleted/' . $del . '/';
				}
			}

			if ($this->input->get('search_int') != "")
			{
				$int = str_replace(array('dontcare', 'yes', 'no'), array('', 'only', 'none'), $this->input->get('search_int'));
				if ($int != "")
				{
					$params .= 'ghost/' . $int . '/';
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
			redirect($this->fu_board . '/' . $params, 'location', 301);
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
			$this->load->model('post');
		}


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
