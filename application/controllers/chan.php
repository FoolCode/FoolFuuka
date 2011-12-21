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
		if ($page > 1)
			$this->template->set('section_title', _('Page ') . $page);
		
		$pages_links = array();
		for($i = 1; $i < 16; $i++)
		{
			$pages_links[$i] = site_url(array(get_selected_board()->shortname, 'page', $i));
		}
		$this->template->set('pages_links', $pages_links);
		$this->template->set('pages_links_current', $page);
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
		if ($page > 1)
			$this->template->set('section_title', _('Ghosts page ') . $page);
		$pages_links = array();
		for($i = 1; $i < 16; $i++)
		{
			$pages_links[$i] = site_url(array(get_selected_board()->shortname, 'ghost', $i));
		}
		$this->template->set('pages_links', $pages_links);
		$this->template->set('pages_links_current', $page);
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


	public function sending()
	{
		// commenting
		$post_data = '';
		if ($this->input->post('reply_action') == 'Submit')
		{
			$this->load->library('form_validation');
			$this->form_validation->set_rules('reply_numero', 'Thread no.', 'required|is_natural_no_zero|xss_clean');
			$this->form_validation->set_rules('reply_bokunonome', 'Username', 'trim|xss_clean|max_lenght[64]');
			$this->form_validation->set_rules('reply_elitterae', 'Email', 'trim|xss_clean|max_lenght[64]');
			$this->form_validation->set_rules('reply_talkingde', 'Subject', 'trim|xss_clean|max_lenght[64]');
			$this->form_validation->set_rules('reply_chennodiscursus', 'Comment', 'trim|required|min_lenght[3]|max_lenght[1000]|xss_clean');
			$this->form_validation->set_rules('reply_nymphassword', 'Password', 'required|min_lenght[3]|max_lenght[32]|xss_clean');

			if ($this->tank_auth->is_allowed())
			{
				$this->form_validation->set_rules('reply_postas', 'Post as', 'required|callback__is_valid_allowed_tag|xss_clean');
				$this->form_validation->set_message('_is_valid_allowed_tag', 'You didn\'t specify a correct type of user to post as');
			}

			if ($this->form_validation->run() !== FALSE)
			{
				$data['num'] = $this->input->post('reply_numero');
				;
				$data['name'] = $this->input->post('reply_bokunonome');
				$data['email'] = $this->input->post('reply_elitterae');
				$data['subject'] = $this->input->post('reply_talkingde');
				$data['comment'] = $this->input->post('reply_chennodiscursus');
				$data['password'] = $this->input->post('reply_nymphassword');
				if ($this->tank_auth->is_allowed())
				{
					$data['postas'] = $this->input->post('reply_postas');
				}
				else
				{
					$data['postas'] = 'N';
				}

				// check if the thread exists (this could be made lighter but whatever for now) @todo
				$thread = $this->post->get_thread($data['num']);
				if (count($thread) != 1)
				{
					show_404();
				}

				$result = $this->post->comment($data);
				if (isset($result['error']))
				{
					$this->template->title(_('Error'));
					$this->template->set('error', $result['error']);
					$this->template->build('error');
					return FALSE;
				}
				else if (isset($result['success']))
				{
					$url = site_url(array(get_selected_board()->shortname, 'thread', $result['posted']->parent)) . '#' . $result['posted']->num . '_' . $result['posted']->subnum;
					$this->template->title(_('Redirecting...'));
					$this->template->set('url', $url);
					$this->template->build('redirect');
					return TRUE;
				}
			}
			else
			{
				$this->template->title(_('Error'));
				$this->template->set('error', validation_errors());
				$this->template->build('error');
				return FALSE;
			}
		}
	}


	public function _is_valid_allowed_tag($tag)
	{
		switch ($tag)
		{
			case 'user':
				return 'N';
				break;
			case 'mod':
				if ($this->tank_auth->is_allowed())
				{
					return 'M';
					break;
				}
			case 'admin':
				if ($this->tank_auth->is_admin())
				{
					return 'A';
					break;
				}
			default:
				return FALSE;
		}
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

		$hash = urldecode($hash);
		$page = intval($page);
		$posts = $this->post->get_image($hash . '==', $page);

		$this->template->set('section_title', _('Searching for posts with image hash: ') . fuuka_htmlescape($hash));
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Image: ' . urldecode($hash));
		$this->template->set('posts', $posts);
		$this->template->build('board');
	}


	// $query, $username = NULL, $tripcode = NULL, $deleted = 0, $internal = 0, $order = 'desc'
	public function search()
	{
		$modifiers = array('text', 'username', 'tripcode', 'deleted', 'ghost', 'order', 'page');
		if ($this->input->post())
		{
			$redirect_array = array(get_selected_board()->shortname, 'search');
			foreach ($modifiers as $modifier)
			{
				if ($this->input->post($modifier))
				{
					$redirect_array[] = $modifier;
					$redirect_array[] = rawurlencode($this->input->post($modifier));
				}
			}

			redirect(site_url($redirect_array));
		}
		$search = $this->uri->ruri_to_assoc(2, $modifiers);
		$result = $this->post->get_search($search);

		$title = array();
		if ($search['text'])
			$title[] = _('including') . ' "' . trim(fuuka_htmlescape($search['text'])) . '"';
		if ($search['username'])
			$title[] = _('with username'). ' "' . trim(fuuka_htmlescape($search['username'])) . '"';
		if ($search['tripcode'])
			$title[] = _('with tripcode'). ' "' . trim(fuuka_htmlescape($search['tripcode'])) . '"';
		if ($search['deleted'] == 'deleted')
			$title[] = _('that are deleted');
		if ($search['deleted'] == 'not-deleted')
			$title[] = _('that aren\'t deleted');
		if ($search['ghost'] == 'only')
			$title[] = _('that are by ghosts');
		if ($search['ghost'] == 'none')
			$title[] = _('that aren\'t by ghosts');
		if ($search['order'] == 'asc')
			$title[] = _('starting from the oldest ones');
		if(!$search['page'] || !intval($search['page']))
		{
			$search['page'] = 1;
		}

		$title = _('Searching for posts ') . implode(' ' . _('and') . ' ', $title);
		$this->template->set('section_title', $title);

		$pages_links = array();
		$pages = floor($result['total_found'] / 25) + 1;
		if ($pages > 21) {
			$pages = 21;
		}		
		
		$uri_array = $this->uri->ruri_to_assoc(2);
		foreach($uri_array as $key => $item)
		{
			if(!$item)
				unset($uri_array[$key]);
		}
		
		for($i = 1; $i < $pages; $i++)
		{
			$uri_array['page'] = $i;
			$pages_links[$i] = site_url().$this->uri->assoc_to_uri($uri_array);
		}
		$this->template->set('pages_links', $pages_links);
		$this->template->set('pages_links_current', $search['page']);
		
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - '.$title);
		$this->template->set('posts', $result['posts']);
		$this->template->set_partial('top_tools', 'top_tools', array('search' => $search));
		$this->template->build('board');
	}


	public function report($num = 0)
	{
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		if (!$this->input->is_ajax_request())
		{
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
			$this->output->set_output(json_encode(array('status' => 'failed', 'reason' => 'Sorry, failed to report post to the moderators. Please try again later.')));
			return FALSE;
		}
		$this->output->set_output(json_encode(array('status' => 'success')));
	}


	public function delete($num = 0)
	{
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		if (!$this->input->is_ajax_request())
		{
			show_404();
		}

		$post = array(
			'post' => $this->input->post("post"),
			'password' => $this->input->post("password")
		);


		$result = $this->post->delete($post);
		if (isset($result['error']))
		{
			$this->output->set_output(json_encode(array('status' => 'failed', 'reason' => $result['error'])));
			return FALSE;
		}
		if (isset($result['success']) && $result['success'] === TRUE)
		{
			$this->output->set_output(json_encode(array('status' => 'success')));
		}
	}


	public function statistics($stat = NULL)
	{
		$this->load->model('statistics');
		if (is_null($stat))
		{
			$stats_list = $this->statistics->get_available_stats();
			$this->template->set('section_title', _('Statistics'));
			$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ': ' . _('statistics'));
			$this->template->set('stats_list', $stats_list);
			$this->template->build('statistics/statistics_list');
			return TRUE;
		}

		$stat_array = $this->statistics->check_available_stats($stat, get_selected_board());

		if (!is_array($stat_array))
		{
			show_404();
		}

		$this->template->set('section_title', _('Statistics:'). ' '.$stat_array['info']['name']);
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ': ' . _('statistics'));
		$this->template->set('info', $stat_array['info']);
		$this->template->set('data', $stat_array['data']);
		$this->template->build('statistics/' . $stat_array['info']['interface']);
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
