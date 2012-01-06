<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * READER CONTROLLER
 *
 * This file allows you to override the standard FoOlSlide controller to make
 * your own URLs for your theme, and to make sure your theme keeps working
 * even if the FoOlSlide default theme gets modified.
 *
 * For more information, refer to the support sites linked in your admin panel.
 */

class Theme_Controller {

	function __construct() {
		$this->CI = & get_instance();
	}


	public function page($page = 1)
	{
		$this->CI->remap_query();
		$this->CI->input->set_cookie('fu_ghost_mode', FALSE, 86400);
		if ($this->CI->input->post())
		{
			redirect($this->CI->fu_board . '/page/' . $this->CI->input->post('page'), 'location', 303);
		}

		if (!is_natural($page) || $page > 500)
		{
			show_404();
		}

		$page = intval($page);

		$this->CI->post->features = FALSE;
		$posts = $this->CI->post->get_latest($page, 24, TRUE, TRUE, FALSE, TRUE, FALSE);

		$this->CI->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name);
		if ($page > 1)
			$this->CI->template->set('section_title', _('Page ') . $page);

		$pages_links = site_url(array(get_selected_board()->shortname, 'page'));
		$this->CI->template->set('pages_links', $pages_links);
		$this->CI->template->set('pages_links_current', $page);
		$this->CI->template->set('posts',  $posts);
		$this->CI->template->set('is_page', TRUE);
		$this->CI->template->set('posts_per_thread', 5);
		$this->CI->template->set_partial('top_tools', 'top_tools', array('page' => $page));
		$this->CI->template->build('board');
	}

	public function ghost($page = 1)
	{
		$this->CI->input->set_cookie('fu_ghost_mode', TRUE, 86400);
		if ($this->CI->input->post())
		{
			redirect($this->CI->fu_board . '/ghost/' . $this->CI->input->post('page'), 'location', 303);
		}

		$values = array();

		if (!is_natural($page) || $page > 500)
		{
			show_404();
		}

		$page = intval($page);

		$this->CI->post->features = FALSE;
		$posts = $this->CI->post->get_latest($page, 24, TRUE, TRUE, TRUE, TRUE, FALSE);

		$this->CI->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name);
		if ($page > 1)
			$this->CI->template->set('section_title', _('Ghosts page ') . $page);
		$pages_links = site_url(array(get_selected_board()->shortname, 'ghost'));
		$this->CI->template->set('pages_links', $pages_links);
		$this->CI->template->set('pages_links_current', $page);
		$this->CI->template->set('posts', $posts);
		$this->CI->template->set('is_page', TRUE);
		$this->CI->template->set('posts_per_thread', 5);
		$this->CI->template->set_partial('top_tools', 'top_tools', array('page' => $page));
		$this->CI->template->build('board');
	}


	public function thread($num)
	{
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		$num = intval($num);

		$this->CI->post->features = FALSE;
		$thread = $this->CI->post->get_thread($num);

		if (!is_array($thread))
		{
			show_404();
		}

		$this->CI->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name);
		$this->CI->template->set('posts', $thread);

		$this->CI->template->set('thread_id', $num);
		$sending = $this->sending();
		$this->CI->template->build('board');
	}


	public function sending()
	{
		if ($this->CI->input->post('reply_action') == 'Submit') {
			$this->CI->load->library('form_validation');
			$this->CI->form_validation->set_rules('parent', 'Thread no.', 'required|is_natural_no_zero|xss_clean');
			$this->CI->form_validation->set_rules('NAMAE', 'Username', 'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('MERU', 'Email', 'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('subject', 'Subject', 'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('KOMENTO', 'Comment', 'trim|required|min_length[3]|max_length[4096]|xss_clean');
			$this->CI->form_validation->set_rules('delpass', 'Password', 'required|min_length[3]|max_length[32]|xss_clean');


			if ($this->CI->tank_auth->is_allowed())
			{
				$this->CI->form_validation->set_rules('reply_postas', 'Post as', 'required|callback__is_valid_allowed_tag|xss_clean');
				$this->CI->form_validation->set_message('_is_valid_allowed_tag', 'You didn\'t specify a correct type of user to post as');
			}


			if ($this->CI->form_validation->run() !== FALSE)
			{
				$data['num'] = $this->CI->input->post('parent');

				$data['name'] = $this->CI->input->post('NAMAE');
				$data['email'] = $this->CI->input->post('MERU');
				$data['subject'] = $this->CI->input->post('subject');
				$data['comment'] = $this->CI->input->post('KOMENTO');
				$data['password'] = $this->CI->input->post('delpass');
				$data['postas'] = 'N';

				if ($this->CI->tank_auth->is_allowed())
				{
					$data['postas'] = $this->CI->input->post('reply_postas');
				}
				else
				{
					$data['postas'] = 'N';
				}


				// check if the thread exists (this could be made lighter but whatever for now) @todo
				$thread = $this->CI->post->get_thread($data['num']);
				if (count($thread) != 1)
				{
					show_404();
				}

				$result = $this->CI->post->comment($data);
				if (isset($result['error']))
				{
					$this->CI->template->set('reply_errors', $result['error']);
					return FALSE;
				}
				else if (isset($result['success']))
				{
					$this->CI->template->set('url', site_url(get_selected_board()->shortname . '/thread/' . $data['num']));
					$this->CI->template->set_layout('redirect');
					$this->CI->template->build('redirect');
					return TRUE;
				}
			}
			else
			{
				$this->CI->form_validation->set_error_delimiters('', '');
				$this->CI->template->set('reply_errors', validation_errors());
				return FALSE;
			}
		}

		if ($this->CI->input->post('reply_delete') == 'Delete Selected Posts')
		{
			foreach ($this->CI->input->post('delete') as $key => $value)
			{
				$post = array(
					'post' => $value,
					'password' => $this->CI->input->post('delpass')
				);

				$result = $this->CI->post->delete($post);
			}

			$this->CI->template->set('url', site_url(get_selected_board()->shortname . '/thread/' . $this->CI->input->post('parent')));
			$this->CI->template->set_layout('redirect');
			$this->CI->template->build('redirect');
		}

		if ($this->CI->input->post('reply_report') == 'Report Selected Posts')
		{
			foreach ($this->CI->input->post('delete') as $key => $value)
			{
				$post = array(
					'board' => get_selected_board()->id,
					'post' => $value,
					'reason' => $this->CI->input->post("KOMENTO")
				);

				$report = new Report();
				$report->add($post);
			}

			$this->CI->template->set('url', site_url(get_selected_board()->shortname . '/thread/' . $this->CI->input->post('parent')));
			$this->CI->template->set_layout('redirect');
			$this->CI->template->build('redirect');
		}
	}


	// $query, $username = NULL, $tripcode = NULL, $deleted = 0, $internal = 0, $order = 'desc'
	public function search()
	{
		$modifiers = array('text', 'username', 'tripcode', 'deleted', 'ghost', 'order', 'page');
		if ($this->CI->input->post())
		{
			$redirect_array = array(get_selected_board()->shortname, 'search');
			foreach ($modifiers as $modifier)
			{
				if ($this->CI->input->post($modifier))
				{
					$redirect_array[] = $modifier;
					$redirect_array[] = rawurlencode($this->CI->input->post($modifier));
				}
			}

			redirect(site_url($redirect_array));
		}
		$search = $this->CI->uri->ruri_to_assoc(2, $modifiers);
		$result = $this->CI->post->get_search($search);

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

		$title = _('Searching for posts ') . urldecode(implode(' ' . _('and') . ' ', $title));
		$this->CI->template->set('section_title', $title);

		$uri_array = $this->CI->uri->ruri_to_assoc(2);
		foreach($uri_array as $key => $item)
		{
			if(!$item)
				unset($uri_array[$key]);
		}
		$pages_links = site_url(array($this->CI->uri->assoc_to_uri($uri_array), 'page'));
		$this->CI->template->set('pages_links', $pages_links);
		$this->CI->template->set('pages_links_current', $search['page']);

		$this->CI->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' &raquo; '.$title);
		$this->CI->template->set('posts', $result['posts']);
		$this->CI->template->set('modifiers', array('post_show_view_button' => TRUE));
		$this->CI->template->set_partial('top_tools', 'top_tools', array('search' => $search));
		$this->CI->template->build('board');
	}


	public function thread_o_matic()
	{
		show_404();
	}

}