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
class Theme_Controller
{


	function __construct() {
		$this->CI = & get_instance();
	}


	/**
	 * Disable GALLERY for this theme by showing 404!
	 */
	public function gallery()
	{
		show_404();
	}


	/**
	 * @return bool
	 */
	public function sending()
	{
		// The form has been submitted to be validated and processed.
		if ($this->CI->input->post('com_submit') == 'Submit') {

			// Validate Form!
			$this->CI->load->library('form_validation');

			$this->CI->form_validation->set_rules('resto', 'Thread no.',
				'required|is_natural|xss_clean');
			$this->CI->form_validation->set_rules('name', 'Username',
				'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('email', 'Email',
				'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('sub', 'Subject',
				'trim|xss_clean|max_length[64]');
			if ($this->CI->input->post('resto') == 0)
			{
				$this->CI->form_validation->set_rules('com', 'Comment',
					'trim|xss_clean');
			}
			else
			{
				$this->CI->form_validation->set_rules('com', 'Comment',
					'trim|min_length[3]|max_length[4096]|xss_clean');
			}
			$this->CI->form_validation->set_rules('pwd', 'Password',
				'required|min_length[3]|max_length[32]|xss_clean');

			// Verify if the user posting is a moderator or administrator and apply form validation.
			if ($this->CI->tank_auth->is_allowed())
			{
				$this->CI->form_validation->set_rules('reply_postas', 'Post as',
					'required|callback__is_valid_allowed_level|xss_clean');
				$this->CI->form_validation->set_message('_is_valid_allowed_tag',
					'You did not specify a valid user level to post as.');
			}

			// The validation of the form has failed! All errors will be formatted here for readability.
			if ($this->CI->form_validation->run() == FALSE)
			{
				$this->CI->form_validation->set_error_delimiters('', '');

				// Display a default/standard output for NON-AJAX REQUESTS.
				$this->CI->template->title(_('Error'));
				$this->CI->_set_parameters(
					array(
						'error'				=> validation_errors()
					),
					array(
						'tools_view'		=> TRUE
					)
				);
				$this->CI->template->build('error');
				return FALSE;
			}

			// Everything is GOOD! Continue with posting the content to the board.
			$data = array(
				'num'		=> $this->CI->input->post('resto'),
				'name'		=> $this->CI->input->post('name'),
				'email'		=> $this->CI->input->post('email'),
				'subject'	=> $this->CI->input->post('sub'),
				'comment'	=> $this->CI->input->post('com'),
				'spoiler'	=> $this->CI->input->post('spoiler'),
				'password'	=> $this->CI->input->post('pwd'),
				'postas'	=> (($this->CI->tank_auth->is_allowed()) ? $this->CI->input->post('reply_postas') : 'N'),

				'media'		=> '',
				'ghost'		=> FALSE
			);


			// CHECK #1: Verify the TYPE of POST passing through and insert the data correctly.
			if (get_selected_radix()->archive)
			{
				// This POST is located in the ARCHIVE and MUST BE A GHOST POST.
				$data['ghost'] = TRUE;

				// Check the $num to ensure that the thread actually exists in the database and that
				// $num is actually the OP of the thread.
				$check = $this->CI->post->check_thread(get_selected_radix(), $data['num']);

				if (isset($check['invalid_thread']) && $check['invalid_thread'] == TRUE)
				{
					$this->CI->template->title(_('Error'));
					$this->CI->_set_parameters(
						array(
							'error'				=> _('This thread does not exist.')
						),
						array(
							'tools_view'		=> TRUE
						)
					);
					$this->CI->template->build('error');
					return FALSE;
				}
			}
			else
			{
				// Determine if we are creating a new thread or replying to an existing thread.
				if ($data['num'] == 0)
				{
					$data['num'] = 0;
					$check = array();
				}
				else
				{
					// Check the $num to ensure that the thread actually exists in the database and that
					// $num is actually the OP of the thread.
					$check = $this->CI->post->check_thread(get_selected_radix(), $data['num']);

					if (isset($check['invalid_thread']) && $check['invalid_thread'] == TRUE)
					{
						$this->CI->template->title(_('Error'));
						$this->CI->_set_parameters(
							array(
								'error'				=> _('This thread does not exist.')
							),
							array(
								'tools_view'		=> TRUE
							)
						);
						$this->CI->template->build('error');
						return FALSE;
					}

					if (isset($check['thread_dead']) && $check['thread_dead'] == TRUE)
					{
						$data['ghost'] = TRUE;
					}
				}
			}

			// CHECK #2: Verify all IMAGE posts and set appropriate information.
			if ($data['num'] == 0
				&& (isset($_FILES['file_image']) && $_FILES['file_image']['error'] == 4))
			{
				$this->CI->template->title(_('Error'));
				$this->CI->_set_parameters(
					array(
						'error'				=> _('You are required to upload an image when posting a new thread.')
					),
					array(
						'tools_view'		=> TRUE
					)
				);
				$this->CI->template->build('error');
				return FALSE;
			}

			// Check if the comment textarea is EMPTY when no image is uploaded.
			if (mb_strlen($data['comment']) < 3
				&& (!isset($_FILES['file_image']) || $_FILES['file_image']['error'] == 4))
			{
				$this->CI->template->title(_('Error'));
				$this->CI->_set_parameters(
					array(
						'error'				=> _('You are required to write a comment when no image upload is present.')
					),
					array(
						'tools_view'		=> TRUE
					)
				);
				$this->CI->template->build('error');
				return FALSE;
			}

			// Check if the IMAGE LIMIT has been reached or if we are posting as a GHOST.
			if ((isset($check['disable_image_upload']) || $data['ghost'])
				&& (isset($_FILES['file_image']) && $_FILES['file_image']['error'] != 4))
			{
				$this->CI->template->title(_('Error'));
				$this->CI->_set_parameters(
					array(
						'error'				=> _('The posting of images has been disabled for this thread.')
					),
					array(
						'tools_view'		=> TRUE
					)
				);
				$this->CI->template->build('error');
				return FALSE;
			}

			// Process the IMAGE upload.
			if (isset($_FILES['file_image']) && $_FILES['file_image']['error'] != 4)
			{
				//Initialize the MEDIA CONFIG and load the UPLOAD library.
				$media_config['upload_path']	= 'content/cache/';
				$media_config['allowed_types']	= 'jpg|png|gif';
				$media_config['max_size']		= 3072;
				$media_config['max_width']		= 5000;
				$media_config['max_height']		= 5000;
				$media_config['overwrite']		= TRUE;

				$this->CI->load->library('upload', $media_config);

				if ($this->CI->upload->do_upload('file_image'))
				{
					$data['media'] = $this->CI->upload->data();
				}
				else
				{
					$this->CI->template->title(_('Error'));
					$this->CI->_set_parameters(
						array(
							'error'				=> $this->CI->upload->display_errors()
						),
						array(
							'tools_view'		=> TRUE
						)
					);
					$this->CI->template->build('error');
					return FALSE;
				}
			}

			// SEND: Process the entire post and insert the information appropriately.
			$result = $this->CI->post->comment(get_selected_radix(), $data);

			// RESULT: Output all errors, messages, etc.
			if (isset($result['error']))
			{
				$this->CI->template->title(_('Error'));
				$this->CI->_set_parameters(
					array(
						'error'				=> $result['error']
					),
					array(
						'tools_view'		=> TRUE
					)
				);
				$this->CI->template->build('error');
				return FALSE;
			}
			else if (isset($result['success']))
			{
				// Redirect back to the user's POST.
				if ($result['posted']->parent == 0)
				{
					$callback = site_url(array(get_selected_radix()->shortname, 'thread',
						$result['posted']->num)) . '#' . $result['posted']->num;
				}
				else
				{
					$callback = site_url(array(get_selected_radix()->shortname, 'thread',
						$result['posted']->parent)) . '#' . $result['posted']->num .
						(($result['posted']->subnum > 0) ? '_' . $result['posted']->subnum : '');
				}

				redirect($callback, 'location', 303);
				return TRUE;
			}
		}

		if ($this->CI->input->post('com_delete') == 'Delete')
		{
			foreach ($this->CI->input->post('delete') as $idx => $doc_id)
			{
				$post = array(
					'post'		=> $value,
					'password'	=> $this->CI->input->post('pwd')
				);

				$result = $this->CI->post->delete(get_selected_radix(), $post);
			}

			if ($this->CI->input->post('resto')) :
				$this->CI->template->set('url', site_url(get_selected_radix()->shortname . '/thread/' . $this->CI->input->post('resto')));
			else :
				$this->CI->template->set('url', site_url(get_selected_radix()->shortname));
			endif;
			$this->CI->template->set_layout('redirect');
			$this->CI->template->build('redirect');
		}

		/**
		 * The form has been submitted to be validated and processed.
		 */
		if ($this->CI->input->post('com_report') == 'Report')
		{
			$report = new Report();
			foreach ($this->CI->input->post('delete') as $idx => $doc_id)
			{
				$post = array(
					'board'		=> get_selected_radix()->id,
					'post'		=> $doc_id,
					'reason'	=> ''
				);

				$report->add($post);
			}

			if ($this->CI->input->post('resto')) :
				$this->CI->template->set('url', site_url(get_selected_radix()->shortname . '/thread/' . $this->CI->input->post('resto')));
			else :
				$this->CI->template->set('url', site_url(get_selected_radix()->shortname));
			endif;
			$this->CI->template->set_layout('redirect');
			$this->CI->template->build('redirect');
		}

		/**
		 * ERROR: We reached the point of no return and wasn't able to do anything.
		 */
		show_404();
	}


}