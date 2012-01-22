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
		$this->template->set_partial('top_options', 'top_options');
		$this->template->set('boards', $boards);
		$this->template->set_partial('post_thread', 'post_thread');
		$this->template->set_partial('post_reply', 'post_reply');
		$this->template->set_partial('post_tools', 'post_tools');
		$this->template->set_layout('chan');
	}


	/*
	 * Show the boards
	 */
	public function index()
	{
		$this->template->set('disable_headers', TRUE);
		$this->template->title('FoOlFuuka &raquo; 4chan Archiver');
		$this->template->build('index');
	}


	function feeds($mode = 'rss_gallery_50')
	{
		//if (is_null($format))
		//	redirect('reader/feeds/rss');
		$this->load->helper('xml');

		if (substr($mode, 0, 4) == 'atom')
		{
			$format = 'atom';
			$mode = substr($mode, 5);
		}
		else
		{
			$format = 'rss';
			$mode = substr($mode, 4);
		}

		switch ($mode)
		{
			case 'gallery_50':
				// returns last 200 threads with the thread number as key
				$threads = array_slice($this->post->gallery(), 0, 50);

				if (count($threads) > 0)
				{
					// let's create a pretty array of chapters [comic][chapter][teams]
					$result['threads'] = array();
					$key = 0;
					foreach ($threads as $num => $thread)
					{
						$result['threads'][$key]['title'] = $thread->title_processed;
						$result['threads'][$key]['thumb'] = $thread->thumbnail_href;
						$result['threads'][$key]['href'] = site_url(array(get_selected_board()->shortname, 'thread', $thread->num));
						$result['threads'][$key]['created'] = $thread->timestamp;
						$key++;
					}
				}
				break;


			default:
				show_404();
		}

		$data['encoding'] = 'utf-8';
		$data['feed_name'] = get_setting('fs_gen_site_title');
		$data['feed_url'] = site_url('feeds/rss');
		$data['page_description'] = get_setting('fs_gen_site_title') . ' RSS feed';
		$data['page_language'] = get_setting('fs_gen_lang') ? get_setting('fs_gen_lang') : 'en_EN';
		$data['posts'] = $result;
		if ($format == "atom")
		{
			header("Content-Type: application/atom+xml");
			$this->load->view('atom', $data);
			return TRUE;
		}
		header("Content-Type: application/rss+xml");
		$this->load->view('rss', $data);
	}


	public function gallery()
	{
		if (!get_selected_board()->thumbnails && !$this->tank_auth->is_allowed())
		{
			show_404();
		}

		$threads = $this->post->gallery();

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name);
		$this->template->set('section_title', 'Gallery ß - Showing: threads');
		$this->template->set('threads', $threads);
		$this->template->set_partial('top_tools', 'top_tools');
		$this->template->build('gallery');
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

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . (($page > 1) ? ' &raquo; Page ' . $page : ''));
		if ($page > 1)
			$this->template->set('section_title', _('Page ') . $page);
		$pagination = array(
			'base_url' => site_url(array(get_selected_board()->shortname, 'page')),
			'current_page' => $page,
			'total' => 0
		);
		$this->template->set('pagination', $pagination);
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

		$posts = $this->post->get_latest($page, 20, TRUE, TRUE, TRUE);

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' &raquo; Ghost' . (($page > 1) ? ' &raquo; Page ' . $page : ''));
		if ($page > 1)
			$this->template->set('section_title', _('Ghosts page ') . $page);
		$pagination = array(
			'base_url' => site_url(array(get_selected_board()->shortname, 'ghost')),
			'current_page' => $page,
			'total' => 0
		);
		$this->template->set('pagination', $pagination);
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

		if (!is_array($thread))
		{
			show_404();
		}

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' &raquo; Thread #' . $num);
		$this->template->set('posts', $thread);

		$this->template->set('thread_id', $num);
		$this->template->build('board');
	}


	public function last50($num = 0)
	{
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		$num = intval($num);

		$thread = $this->post->get_last50($num);

		if (!is_array($thread))
		{
			show_404();
		}

		if (isset($thread[$num]['op']))
		{
			$this->post($num);
		}

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' &raquo; Thread #' . $num);
		$this->template->set('posts', $thread);

		$this->template->set('thread_id', $num);
		$this->template->set('last50', TRUE);
		$this->template->build('board');
	}


	public function sending()
	{
		// commenting
		$post_data = '';

		if (mb_strlen($this->input->post('name')) > 0 || mb_strlen($this->input->post('reply')) > 0 || mb_strlen($this->input->post('email')) > 0)
		{
			show_404();
		}

		if ($this->input->post('reply_gattai') == 'Submit')
		{
			$this->load->library('form_validation');
			$this->form_validation->set_rules('reply_numero', 'Thread no.', 'required|is_natural|xss_clean');
			$this->form_validation->set_rules('reply_bokunonome', 'Username', 'trim|xss_clean|max_length[64]');
			$this->form_validation->set_rules('reply_elitterae', 'Email', 'trim|xss_clean|max_length[64]');
			$this->form_validation->set_rules('reply_talkingde', 'Subject', 'trim|xss_clean|max_length[64]');
			$this->form_validation->set_rules('reply_chennodiscursus', 'Comment', 'trim|required|min_length[3]|max_length[4096]|xss_clean');
			$this->form_validation->set_rules('reply_nymphassword', 'Password', 'required|min_length[3]|max_length[32]|xss_clean');

			if ($this->tank_auth->is_allowed())
			{
				$this->form_validation->set_rules('reply_postas', 'Post as', 'required|callback__is_valid_allowed_tag|xss_clean');
				$this->form_validation->set_message('_is_valid_allowed_tag', 'You didn\'t specify a correct type of user to post as');
			}

			if ($this->form_validation->run() !== FALSE)
			{
				$data['num'] = $this->input->post('reply_numero');
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

				// Check if thread exists
				$check = $this->post->check_thread($data['num']);
				if (!get_selected_board()->archive)
				{
					// Normal Posting
					if (isset($check['invalid_thread']) && $this->input->post('reply_numero') == 0)
					{
						$data['num'] = array('parent' => 0);
					}
					else if (isset($check['thread_dead']))
					{
						$data['num'] = $this->input->post('reply_numero');
					}
					else
					{
						$data['num'] = array('parent' => $this->input->post('reply_numero'));
					}
				}
				else
				{
					if (isset($check['invalid_thread']))
					{
						if ($this->input->is_ajax_request())
						{
							$this->output
									->set_content_type('application/json')
									->set_output(json_encode(array('error' => 'This thread does not exist.', 'success' => '')));
						}
					}
				}

				$media_config['upload_path'] = 'content/cache/';
				$media_config['allowed_types'] = 'jpg|png|gif';
				$media_config['max_size'] = 3072;
				$this->load->library('upload', $media_config);
				if ($this->upload->do_upload('file_image'))
				{
					$data['media'] = $this->upload->data();
				}
				else
				{
					$data['media'] = '';
					$data['media_error'] = $this->upload->display_errors();
				}

				if (isset($check['disable_image_upload']))
				{
					$result = $this->post->comment($data, FALSE);
				}
				else
				{
					$result = $this->post->comment($data);
				}

				if (isset($result['error']))
				{
					if ($this->input->is_ajax_request())
					{
						$this->output
								->set_content_type('application/json')
								->set_output(json_encode(array('error' => $result['error'], 'success' => '')));
						return FALSE;
					}
					$this->template->title(_('Error'));
					$this->template->set('error', $result['error']);
					$this->template->build('error');
					return FALSE;
				}
				else if (isset($result['success']))
				{
					if ($this->input->is_ajax_request())
					{
						$this->output
								->set_content_type('application/json')
								->set_output(json_encode(array('error' => '', 'success' => 'Your comment has been posted.')));
						return FALSE;
					}

					if ($result['posted']->parent == 0)
					{
						$url = site_url(array(get_selected_board()->shortname, 'thread', $result['posted']->num)) . '#' . $result['posted']->num;
					}
					else
					{
						$url = site_url(array(get_selected_board()->shortname, 'thread', $result['posted']->parent)) . '#' . $result['posted']->num . (($result['posted']->subnum > 0) ? '_' . $result['posted']->subnum : '');
					}

					$this->template->title(_('Redirecting...'));
					$this->template->set('url', $url);
					$this->template->set_layout('redirect');
					$this->template->build('redirect');
					return TRUE;
				}
			}
			else
			{
				$this->form_validation->set_error_delimiters('', '');
				if ($this->input->is_ajax_request())
				{
					$this->output
							->set_content_type('application/json')
							->set_output(json_encode(array('error' => validation_errors(), 'success' => '')));
					return FALSE;
				}
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

		if (strpos($num, '_') > 0)
		{
			$nums = explode('_', $num);
			if (count($nums) != 2)
				show_404();
			$subnum = $nums[1];
			$num = $nums[0];
			if (!is_natural($subnum) || !$subnum > 0)
			{
				show_404();
			}
			$subnum = intval($subnum);
		}
		else
		{
			$subnum = 0;
		}

		if (!is_natural($num) || !$num > 0)
		{
			show_404();
		}
		$num = intval($num);

		$thread = $this->post->get_post_thread($num, $subnum);
		if ($thread === FALSE)
		{
			show_404();
		}

		if ($thread->subnum > 0)
		{
			$url = site_url($this->fu_board . '/thread/' . $thread->parent) . '#' . $thread->num . '_' . $thread->subnum;
		}
		else if ($thread->parent > 0)
		{
			$url = site_url($this->fu_board . '/thread/' . $thread->parent) . '#' . $thread->num;
		}
		else
		{
			$url = site_url($this->fu_board . '/thread/' . $thread->num);
		}

		$this->template->title(_('Redirecting...'));
		$this->template->set('url', $url);
		$this->template->set('fast_redirect', TRUE);
		$this->template->set_layout('redirect');
		$this->template->build('redirect');
	}


	public function image()
	{
		$uri = $this->uri->segment_array();

		array_shift($uri);
		array_shift($uri);

		$imploded_uri = urldecode(implode('/', $uri));
		if(mb_strlen($imploded_uri) < 22)
		{
			show_404();
		}

		$hash = str_replace(' ', '+', mb_substr($imploded_uri, 0, 22));
		if(mb_strlen($imploded_uri) > 23)
		{
			$page = substr($imploded_uri, 23);
		}
		else
		{
			$page = 1;
		}


		if ($hash == '' || !is_natural($page) || $page > 500)
		{
			show_404();
		}

		$page = intval($page);
		$posts = $this->post->get_image($hash . '==', $page);

		$this->template->set('section_title', _('Searching for posts with image hash: ') . fuuka_htmlescape($hash));
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Image: ' . urldecode($hash));
		$this->template->set('posts', $posts);
		$this->template->set('modifiers', array('post_show_view_button' => TRUE));
		$this->template->build('board');
	}


	public function full_image($image)
	{
		if (!in_array(substr($image, -3), array('jpg', 'png', 'gif')) || !is_natural(substr($image, 0, 13)))
		{
			show_404();
		}

		$image_data = $this->post->get_full_image($image);

		if (isset($image_data['image_href']))
		{
			redirect($image_data['image_href']);
		}
		if (isset($image_data['error_type']))
		{
			if ($image_data['error_type'] == 'no_record')
			{
				$this->output->set_status_header('404');
				$this->template->title(_('Error'));
				$this->template->set('error', _('There\'s no record of such image in our database.'));
				$this->template->build('error');
			}

			if ($image_data['error_type'] == 'not_on_server')
			{
				$this->output->set_status_header('404');
				$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' &raquo; Image pruned');
				$this->template->set('posts', array('posts' => array($image_data['result'])));
				$this->template->set('modifiers', array('post_show_single_post' => TRUE));
				$this->template->build('board');
			}
		}
	}


	public function redirect($image = NULL)
	{
		$this->template->set('url', get_selected_board()->images_url . $image);
		$this->template->set_layout('redirect');
		$this->template->build('redirect');
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

		$this->template->set_partial('top_tools', 'top_tools', array('search' => $search));

		if (isset($result['error']))
		{
			$this->template->title(_('Error'));
			$this->template->set('error', $result['error']);
			$this->template->build('error');
			return FALSE;
		}

		$title = array();
		if ($search['text'])
			$title[] = _('including') . ' "' . trim(fuuka_htmlescape($search['text'])) . '"';
		if ($search['username'])
			$title[] = _('with username') . ' "' . trim(fuuka_htmlescape($search['username'])) . '"';
		if ($search['tripcode'])
			$title[] = _('with tripcode') . ' "' . trim(fuuka_htmlescape($search['tripcode'])) . '"';
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
		if (!$search['page'] || !intval($search['page']))
		{
			$search['page'] = 1;
		}

		$title = _('Searching for posts ') . urldecode(implode(' ' . _('and') . ' ', $title));
		$this->template->set('section_title', $title);

		$uri_array = $this->uri->ruri_to_assoc(2);
		foreach ($uri_array as $key => $item)
		{
			if (!$item)
				unset($uri_array[$key]);
		}

		if (isset($uri_array['page']))
			unset($uri_array['page']);

		$total_pages = ceil($result['total_found'] / 25);
		$pagination = array(
			'base_url' => site_url(array($this->uri->assoc_to_uri($uri_array), 'page')),
			'current_page' => $search['page'],
			'total' => (($total_pages > 200) ? 200 : $total_pages)
		);
		$this->template->set('pagination', $pagination);

		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' &raquo; ' . $title);
		$this->template->set('posts', $result['posts']);
		$this->template->set('modifiers', array('post_show_view_button' => TRUE));
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


	public function spam($num = 0)
	{
		if (!$this->tank_auth->is_allowed())
		{
			show_404();
		}

		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		if (!$this->input->is_ajax_request())
		{
			show_404();
		}

		$result = $this->post->spam($this->input->post("post"));
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
			$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . '&raquo; ' . _('statistics'));
			$this->template->set('stats_list', $stats_list);
			$this->template->set_partial('statistics_interface', 'statistics/statistics_list');
			$this->template->build('statistics/statistics_show');
			return TRUE;
		}

		$stat_array = $this->statistics->check_available_stats($stat, get_selected_board());
		if (!is_array($stat_array))
		{
			show_404();
		}

		$time_left = $stat_array['info']['frequence'] + strtotime($stat_array['timestamp']);
		$this->load->helper('date');
		$this->template->set('section_title', _('Statistics:') . ' ' . $stat_array['info']['name'] . '. Next update: ' . timespan(time(),$time_left));
		$this->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . '&raquo; ' . _('statistics'));
		$this->template->set('info', $stat_array['info']);
		$this->template->set('data', $stat_array['data']);
		$this->template->set_partial('statistics_interface', 'statistics/' . $stat_array['info']['interface']);
		$this->template->build('statistics/statistics_show');
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
