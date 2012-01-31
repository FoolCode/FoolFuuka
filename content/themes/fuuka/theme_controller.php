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
			redirect(get_selected_radix()->shortname . '/page/' . $this->CI->input->post('page'), 'location', 303);
		}

		if (!is_natural($page) || $page > 500)
		{
			show_404();
		}

		$page = intval($page);

		$this->CI->post->features = FALSE;
		$posts = $this->CI->post->get_latest(get_selected_radix(), $page, array('per_page' => 24, 'type' => 'by_thread'));

		$pages = $posts['pages'];
		$posts = $posts['result'];

		$this->CI->template->title('/' . get_selected_radix()->shortname . '/ - ' . get_selected_radix()->name);
		if ($page > 1)
			$this->CI->template->set('section_title', _('Page ') . $page);

		$pagination = array(
			'base_url' => site_url(array(get_selected_radix()->shortname, 'page')),
			'current_page' => $page,
			'total' => $pages
		);

		$this->CI->template->set('pagination', $pagination);
		$this->CI->template->set('posts',  $posts);
		$this->CI->template->set('is_page', TRUE);
		$this->CI->template->set('posts_per_thread', 5);
		$this->CI->template->set_partial('top_tools', 'top_tools', array('page' => $page));
		$this->CI->template->set_partial('post_thread', 'post_thread');
		$this->CI->template->set_partial('post_tools', 'post_tools');
		$this->CI->template->build('board');
	}

	public function ghost($page = 1)
	{
		$this->CI->input->set_cookie('fu_ghost_mode', TRUE, 86400);
		if ($this->CI->input->post())
		{
			redirect(get_selected_radix()->shortname . '/ghost/' . $this->CI->input->post('page'), 'location', 303);
		}

		$values = array();

		if (!is_natural($page) || $page > 500)
		{
			show_404();
		}

		$page = intval($page);

		$this->CI->post->features = FALSE;
		$posts = $this->CI->post->get_latest(get_selected_radix(), $page, array('per_page' => 24, 'type' => 'ghost'));

		$pages = $posts['pages'];
		$posts = $posts['result'];

		$this->CI->template->title('/' . get_selected_radix()->shortname . '/ - ' . get_selected_radix()->name);
		if ($page > 1)
			$this->CI->template->set('section_title', _('Ghosts page ') . $page);

		$pagination = array(
			'base_url' => site_url(array(get_selected_radix()->shortname, 'page')),
			'current_page' => $page,
			'total' => $pages
		);

		$this->CI->template->set('pagination', $pagination);
		$this->CI->template->set('posts', $posts);
		$this->CI->template->set('is_page', TRUE);
		$this->CI->template->set('posts_per_thread', 5);
		$this->CI->template->set_partial('top_tools', 'top_tools', array('page' => $page));
		$this->CI->template->set_partial('post_thread', 'post_thread');
		$this->CI->template->set_partial('post_tools', 'post_tools');
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
		$thread = $this->CI->post->get_thread(get_selected_radix(), $num);

		if (!is_array($thread))
		{
			show_404();
		}

		$this->CI->template->title('/' . get_selected_radix()->shortname . '/ - ' . get_selected_radix()->name);
		$this->CI->template->set('posts', $thread);
		$this->CI->template->set('thread_id', $num);
		$this->CI->template->set_partial('top_tools', 'top_tools');
		$this->CI->template->set_partial('post_reply', 'post_reply');
		$this->CI->template->set_partial('post_tools', 'post_tools');
		$this->CI->template->build('board');
	}


	public function sending()
	{
		if ($this->CI->input->post('reply_action') == 'Submit')
		{
			$this->CI->load->library('form_validation');
			$this->CI->form_validation->set_rules('parent', 'Thread no.', 'required|is_natural|xss_clean');
			$this->CI->form_validation->set_rules('NAMAE', 'Username', 'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('MERU', 'Email', 'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('subject', 'Subject', 'trim|xss_clean|max_length[64]');
			if ($this->CI->input->post('parent') == 0)
			{
				$this->CI->form_validation->set_rules('KOMENTO', 'Comment', 'trim|xss_clean');
			}
			else
			{
				$this->CI->form_validation->set_rules('KOMENTO', 'Comment', 'trim|required|min_length[3]|max_length[4096]|xss_clean');
			}
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
				$data['spoiler'] = $this->CI->input->post('reply_spoiler');
				$data['postas'] = 'N';

				if ($this->CI->tank_auth->is_allowed())
				{
					$data['postas'] = $this->CI->input->post('reply_postas');
				}
				else
				{
					$data['postas'] = 'N';
				}

				// Check if thread exists
				$check = $this->CI->post->check_thread(get_selected_radix(), $data['num']);
				if (!get_selected_radix()->archive)
				{
					// Normal Posting
					if (isset($check['invalid_thread']) && $this->CI->input->post('parent') == 0)
					{
						$data['num'] = array('parent' => 0);
					}
					else if (isset($check['thread_dead']))
					{
						$data['num'] = $this->CI->input->post('parent');
					}
					else
					{
						$data['num'] = array('parent' => $this->CI->input->post('parent'));
					}
				}
				else
				{
					if (isset($check['invalid_thread']))
					{
						show_404();
					}
				}

				$media_config['upload_path'] = 'content/cache/';
				$media_config['allowed_types'] = 'jpg|png|gif';
				$media_config['max_size'] = 3072;
				$media_config['max_width'] = 3000;
				$media_config['max_height'] = 3000;
				$media_config['overwrite'] = TRUE;
				$this->CI->load->library('upload', $media_config);
				if ($this->CI->upload->do_upload('file_image'))
				{
					$data['media'] = $this->CI->upload->data();
				}
				else
				{
					$data['media'] = '';
					$data['media_error'] = $this->CI->upload->display_errors();
				}

				if (isset($check['disable_image_upload']))
				{
					$result = $this->CI->post->comment(get_selected_radix(), $data, FALSE);
				}
				else
				{
					$result = $this->CI->post->comment(get_selected_radix(), $data);
				}

				if (isset($result['error']))
				{
					$this->CI->template->set('error', $result['error']);
					$this->CI->template->build('error');
					return FALSE;
				}
				else if (isset($result['success']))
				{
					if ($result['posted']->parent == 0)
					{
						$url = site_url(array(get_selected_radix()->shortname, 'thread', $result['posted']->num)) . '#p' . $result['posted']->num;
					}
					else
					{
						$url = site_url(array(get_selected_radix()->shortname, 'thread', $result['posted']->parent)) . '#p' . $result['posted']->num . (($result['posted']->subnum > 0) ? '_' . $result['posted']->subnum : '');
					}

					$this->CI->template->set('url', $url);
					$this->CI->template->set('message', 0);
					$this->CI->template->set_layout('redirect');
					$this->CI->template->build('redirect');
					return TRUE;
				}
			}
			else
			{
				$this->CI->form_validation->set_error_delimiters('', '');
				$this->CI->template->set('error', validation_errors());
				$this->CI->template->build('error');
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

				$result = $this->CI->post->delete(get_selected_radix(), $post);
			}

			$this->CI->template->set('url', site_url(get_selected_radix()->shortname . '/thread/' . $this->CI->input->post('parent')));
			$this->CI->template->set_layout('redirect');
			$this->CI->template->build('redirect');
		}

		if ($this->CI->input->post('reply_report') == 'Report Selected Posts')
		{
			foreach ($this->CI->input->post('delete') as $key => $value)
			{
				$post = array(
					'board' => get_selected_radix()->id,
					'post' => $value,
					'reason' => $this->CI->input->post("KOMENTO")
				);

				$report = new Report();
				$report->add($post);
			}

			$this->CI->template->set('url', site_url(get_selected_radix()->shortname . '/thread/' . $this->CI->input->post('parent')));
			$this->CI->template->set_layout('redirect');
			$this->CI->template->build('redirect');
		}
	}


	// $query, $username = NULL, $tripcode = NULL, $deleted = 0, $internal = 0, $order = 'desc'
	public function search()
	{
		$modifiers = array('text', 'username', 'tripcode', 'deleted', 'ghost', 'capcode', 'order', 'page');
		if ($this->CI->input->post())
		{
			$redirect_array = array(get_selected_radix()->shortname, 'search');
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
		$result = $this->CI->post->get_search(get_selected_radix(), $search);

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

		$this->CI->template->title('/' . get_selected_radix()->shortname . '/ - ' . get_selected_radix()->name . ' &raquo; '.$title);
		$this->CI->template->set('posts', $result['posts']);
		$this->CI->template->set('modifiers', array('post_show_view_button' => TRUE));
		$this->CI->template->set_partial('top_tools', 'top_tools', array('search' => $search));
		$this->CI->template->set_partial('post_tools', 'post_tools');
		$this->CI->template->build('board');
	}


	public function gallery()
	{
		show_404();
	}

}