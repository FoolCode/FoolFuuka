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


	function __construct()
	{
		$this->CI = & get_instance();
	}


	/**
	 * @param int $page
	 */
	public function page($page = 1)
	{
		/**
		 * POST -> GET Redirection to provide URL presentable for sharing links.
		 */
		$this->CI->_map_query();
		if ($this->CI->input->post())
			redirect(get_selected_radix()->shortname . '/page/' . $this->CI->input->post('page'), 'location', 303);

		/**
		 * Fetch the latest posts.
		 */
		$page  = intval($page);
		$posts = $this->CI->post->get_latest(get_selected_radix(), $page,
			array('per_page' => 24, 'type' => 'by_thread'));

		/**
		 * Set template variables required to build the HTML.
		 */
		$this->CI->template->title(get_selected_radix()->formatted_title .
			(($page >1 ) ? ' &raquo; ' . _('Page') . ' ' . $page : ''));
		$this->CI->_set_parameters(
			array(
				'section_title'		=> (($page > 1) ? _('Page') . ' ' . $page : ''),

				'is_page'			=> TRUE,

				'posts'				=> $posts['result'],
				'posts_per_thread'	=> 5,

				'pagination'		=> array(
					'base_url'		=> site_url(array(get_selected_radix()->shortname, 'page')),
					'current_page'	=> $page,
					'total'			=> $posts['pages']
				)
			),
			array(
				'post_thread'		=> TRUE,
				'tools_post'		=> TRUE,
				'tools_view'		=> array('page' => $page)
			)
		);
		$this->CI->template->build('board');
	}


	public function by_thread($page = 1)
	{
		if ($this->CI->input->post())
			redirect(get_selected_radix()->shortname . '/page/' . $this->CI->input->post('page'), 'location', 303);
		else
			redirect(get_selected_radix()->shortname . '/page/' . $page, 'location', 303);
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
		/**
		 * The form has been submitted to be validated and processed.
		 */
		if ($this->CI->input->post('reply_action') == 'Submit')
		{
			/**
			 * Validate Form!
			 */
			$this->CI->load->library('form_validation');

			$this->CI->form_validation->set_rules('parent', 'Thread no.',
				'required|is_natural|xss_clean');
			$this->CI->form_validation->set_rules('NAMAE', 'Username',
				'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('MERU', 'Email',
				'trim|xss_clean|max_length[64]');
			$this->CI->form_validation->set_rules('subject', 'Subject',
				'trim|xss_clean|max_length[64]');
			if ($this->CI->input->post('parent') == 0)
			{
				$this->CI->form_validation->set_rules('KOMENTO', 'Comment',
					'trim|xss_clean');
			}
			else
			{
				$this->CI->form_validation->set_rules('KOMENTO', 'Comment',
					'trim|min_length[3]|max_length[4096]|xss_clean');
			}
			$this->CI->form_validation->set_rules('delpass', 'Password',
				'required|min_length[3]|max_length[32]|xss_clean');

			if ($this->CI->tank_auth->is_allowed())
			{
				$this->CI->form_validation->set_rules('reply_postas', 'Post as',
					'required|callback__is_valid_allowed_level|xss_clean');
				$this->CI->form_validation->set_message('_is_valid_allowed_level',
					'You did not specify a valid user level to post as.');
			}

			if ($this->CI->form_validation->run() == FALSE)
			{
				$this->CI->form_validation->set_error_delimiters('', '');

				/**
				 * Display a default/standard output for NON-AJAX REQUESTS.
				 */
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

			/**
			 * Everything is GOOD! Continue with posting the content to the board.
			 */
			$data = array(
				'num'		=> $this->CI->input->post('parent'),
				'name'		=> $this->CI->input->post('NAMAE'),
				'email'		=> $this->CI->input->post('MERU'),
				'subject'	=> $this->CI->input->post('subject'),
				'comment'	=> $this->CI->input->post('KOMENTO'),
				'spoiler'	=> $this->CI->input->post('reply_spoiler'),
				'password'	=> $this->CI->input->post('delpass'),
				'postas'	=> (($this->CI->tank_auth->is_allowed()) ? $this->CI->input->post('reply_postas') : 'N'),

				'media'		=> '',
				'ghost'		=> FALSE
			);

			/**
			 * CHECK #1: Verify the TYPE of POST passing through and insert the data correctly.
			 */
			if (get_selected_radix()->archive)
			{
				/**
				 * This POST is located in the ARCHIVE and MUST BE A GHOST POST.
				 */
				$data['ghost'] = TRUE;

				/**
				 * Check the $num to ensure that the thread actually exists in the database and that
				 * $num is actually the OP of the thread.
				 */
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
				/**
				 * Determine if we are creating a new thread or replying to an existing thread.
				 */
				if ($data['num'] == 0)
				{
					$data['num'] = 0;
					$check = array();
				}
				else
				{
					/**
					 * Check the $num to ensure that the thread actually exists in the database and that
					 * $num is actually the OP of the thread.
					 */
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

			/**
			 * CHECK #2: Verify all IMAGE posts and set appropriate information.
			 */
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

			/**
			 * Check if the comment textarea is EMPTY when no image is uploaded.
			 */
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

			/**
			 * Check if the IMAGE LIMIT has been reached or if we are posting as a GHOST.
			 */
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

			/**
			 * Process the IMAGE upload.
			 */
			if (isset($_FILES['file_image']) && $_FILES['file_image']['error'] != 4)
			{
				/**
				 * Initialize the MEDIA CONFIG and load the UPLOAD library.
				 */
				$media_config['upload_path']	= 'content/cache/';
				$media_config['allowed_types']	= 'jpg|png|gif';
				$media_config['max_size']		= 3072;
				$media_config['max_width']		= 5000;
				$media_config['max_height']		= 5000;
				$media_config['overwrite']		= TRUE;

				$this->CI->load->library('upload', $media_config);

				if ($this->CI->upload->do_upload('image'))
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

			/**
			 * SEND: Process the entire post and insert the information appropriately.
			 */
			$result = $this->CI->post->comment(get_selected_radix(), $data);

			/**
			 * RESULT: Output all errors, messages, etc.
			 */
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
				/**
				 * Redirect back to the user's POST.
				 */
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

				$this->CI->template->set_layout('redirect');
				$this->CI->template->title(_('Redirecting...'));
				$this->CI->_set_parameters(
					array(
						'redirection_msg'	=> 0,
						'redirection_url'	=> $callback
					)
				);
				$this->CI->template->build('redirection');
				return TRUE;
			}
		}


		/**
		 * The form has been submitted to be validated and processed.
		 */
		if ($this->CI->CI->input->post('reply_delete') == 'Delete Selected Posts')
		{
			foreach ($this->CI->CI->input->post('delete') as $idx => $doc_id)
			{
				$post = array(
					'post'		=> $doc_id,
					'password'	=> $this->CI->CI->input->post('delpass')
				);

				$this->CI->CI->post->delete(get_selected_radix(), $post);
			}

			$this->CI->template->set_layout('redirect');
			$this->CI->template->title(_('Redirecting...'));
			$this->CI->_set_parameters(
				array(
					'redirection_msg'	=> 0,
					'redirection_url'	=> site_url(get_selected_radix()->shortname . '/thread/' .
											$this->CI->CI->input->post('parent'))
				)
			);
		}


		/**
		 * The form has been submitted to be validated and processed.
		 */
		if ($this->CI->CI->input->post('reply_report') == 'Report Selected Posts')
		{
			$report = new Report();
			foreach ($this->CI->CI->input->post('delete') as $idx => $doc_id)
			{
				$post = array(
					'board'		=> get_selected_radix()->id,
					'post'		=> $doc_id,
					'reason'	=> $this->CI->CI->intput->post('KOMENTO')
				);

				$report->add($post);
			}

			$this->CI->template->set_layout('redirect');
			$this->CI->template->title(_('Redirecting...'));
			$this->CI->_set_parameters(
				array(
					'redirection_msg'	=> 0,
					'redirection_url'	=> site_url(get_selected_radix()->shortname . '/thread/' .
											$this->CI->input->post('parent'))
				)
			);
		}

		/**
		 * ERROR: We reached the point of no return and wasn't able to do anything.
		 */
		show_404();
	}


}