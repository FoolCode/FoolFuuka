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


	public function thread($num)
	{
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		$num = intval($num);

		$thread = $this->CI->post->get_thread($num);

		if (!is_array($thread))
		{
			show_404();
		}

		$this->CI->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Thread #' . $num);
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
			$this->CI->form_validation->set_rules('NAMAE', 'Username', 'trim|xss_clean|max_lenght[64]');
			$this->CI->form_validation->set_rules('MERU', 'Email', 'trim|xss_clean|max_lenght[64]');
			$this->CI->form_validation->set_rules('subject', 'Subject', 'trim|xss_clean|max_lenght[64]');
			$this->CI->form_validation->set_rules('KOMENTO', 'Comment', 'trim|required|min_lenght[3]|max_lenght[1000]|xss_clean');
			$this->CI->form_validation->set_rules('delpass', 'Password', 'required|min_lenght[3]|max_lenght[32]|xss_clean');


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
			$this->CI->template->build('board');
		}
	}

	public function thread_o_matic()
	{
		show_404();
	}

}