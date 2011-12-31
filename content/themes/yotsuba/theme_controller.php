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
		$posts = $this->CI->post->get_latest($page);

		$this->CI->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . (($page > 1)?' » Page '.$page:''));
		if ($page > 1)
			$this->CI->template->set('section_title', _('Page ') . $page);

		$pages_links = array();
		for($i = 1; $i < 16; $i++)
		{
			$pages_links[$i] = site_url(array(get_selected_board()->shortname, 'page', $i));
		}
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
		$posts = $this->CI->post->get_latest($page, 20, TRUE, TRUE, TRUE);

		$this->CI->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' » Ghost' . (($page > 1)?' » Page '.$page:''));
		if ($page > 1)
			$this->CI->template->set('section_title', _('Ghosts page ') . $page);
		$pages_links = array();
		for($i = 1; $i < 16; $i++)
		{
			$pages_links[$i] = site_url(array(get_selected_board()->shortname, 'ghost', $i));
		}
		$this->CI->template->set('pages_links', $pages_links);
		$this->CI->template->set('pages_links_current', $page);
		$this->CI->template->set('posts', $posts);
		$this->CI->template->set('is_page', TRUE);
		$this->CI->template->set('posts_per_thread', 5);
		$this->CI->template->set_partial('top_tools', 'top_tools', array('page' => $page));
		$this->CI->template->build('board');
	}


	public function thread($num = 0)
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

		$this->CI->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' » Thread #' . $num);
		$this->CI->template->set('posts', $thread);

		$this->CI->template->set('thread_id', $num);
		//$this->CI->template->set_partial('post_reply', 'post_reply', array('thread_id' => $num, 'post_data' => $post_data));
		$this->CI->template->build('board');
	}


	public function sending()
	{
		if ($this->CI->input->post('com_submit') == 'Submit') {
			$this->CI->load->library('form_validation');
			$this->CI->form_validation->set_rules('resto', 'Thread no.', 'required|is_natural_no_zero|xss_clean');
			$this->CI->form_validation->set_rules('name', 'Username', 'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('email', 'Email', 'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('sub', 'Subject', 'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('com', 'Comment', 'trim|required|min_lenght[3]|max_length[1000]|xss_clean');
			$this->CI->form_validation->set_rules('pwd', 'Password', 'required|min_lenght[3]|max_length[32]|xss_clean');


			if ($this->CI->tank_auth->is_allowed())
			{
				$this->CI->form_validation->set_rules('reply_postas', 'Post as', 'required|callback__is_valid_allowed_tag|xss_clean');
				$this->CI->form_validation->set_message('_is_valid_allowed_tag', 'You didn\'t specify a correct type of user to post as');
			}


			if ($this->CI->form_validation->run() !== FALSE)
			{
				$data['num'] = $this->CI->input->post('resto');

				$data['name'] = $this->CI->input->post('name');
				$data['email'] = $this->CI->input->post('email');
				$data['subject'] = $this->CI->input->post('sub');
				$data['comment'] = $this->CI->input->post('com');
				$data['password'] = $this->CI->input->post('pwd');
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
					$this->CI->template->build('board');
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

		if ($this->CI->input->post('com_delete') == 'Delete')
		{
			foreach ($this->CI->input->post('delete') as $key => $value)
			{
				$post = array(
					'post' => $value,
					'password' => $this->CI->input->post('pwd')
				);

				$result = $this->CI->post->delete($post);
			}

			$this->CI->template->set('url', site_url(get_selected_board()->shortname . '/thread/' . $this->CI->input->post('resto')));
			$this->CI->template->set_layout('redirect');
			$this->CI->template->build('board');
		}

		if ($this->CI->input->post('com_report') == 'Report')
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

			$this->CI->template->set('url', site_url(get_selected_board()->shortname . '/thread/' . $this->CI->input->post('resto')));
			$this->CI->template->set_layout('redirect');
			$this->CI->template->build('board');
		}
	}


	public function thread_o_matic()
	{
		show_404();
	}

}