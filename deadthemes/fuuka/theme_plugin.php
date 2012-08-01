<?php

if (!defined('DOCROOT'))
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
class Theme_Plugin_fuuka extends Plugins_model
{

	function __construct()
	{
		parent::__construct();
	}


	function initialize_plugin()
	{
		// use hooks for manipulating comments
		Plugins::register_hook($this, 'fu_post_model_process_comment_greentext_result', 8, '_greentext');
		Plugins::register_hook($this, 'fu_post_model_process_internal_links_html_result', 8,
			'_process_internal_links_html');

		Plugins::register_hook($this, 'fu_post_model_process_crossboard_links_html_result', 8,
			'_process_crossboard_links_html');

		Plugins::register_hook($this, 'fu_chan_controller_before_page', 3, 'page');
		Plugins::register_hook($this, 'fu_chan_controller_before_gallery', 3, function(){ show_404(); });
		// for safety, force 404
		Plugins::register_hook($this, 'fu_chan_controller_before_submit', 3, function(){ show_404(); });

		// if we have to outright change the name of the function, we need to register a new controller function
		Plugins::register_controller_function($this, array('chan', '(:any)', 'sending'), 'sending');
	}


	function _greentext($html)
	{
		return '\\1<span class="greentext">\\2</span>\\3';
	}

	function _process_internal_links_html($data, $html, $previous_result = NULL)
	{
		// a plugin with higher priority modified this
		if(!is_null($previous_result))
		{
			return array('return' => $previous_result);
		}

		return array('return' => array(
			'tags' => array('<span class="unkfunc">', '</span>'),
			'hash' => '',
			'attr' => 'class="backlink" onclick="replyHighlight(' . $data->num . ');"',
			'attr_op' => 'class="backlink"',
			'attr_backlink' => 'class="backlink"',
		));
	}

	function _process_crossboard_links_html($data, $html, $previous_result = NULL)
	{
		// a plugin with higher priority modified this
		if(!is_null($previous_result))
		{
			return array('return' => $previous_result);
		}

		return array('return' => array(
			'tags' => array('<span class="unkfunc">', 'suffix' => '</span>'),
			'backlink' => ''
		));
	}

	/**
	 * @param int $page
	 */
	public function page($page = 1)
	{
		return array('parameters' => array($page, FALSE, array('per_page' => 24, 'type' => 'by_thread')));
	}

	/**
	 * @return bool
	 */
	public function sending()
	{
		/**
		 * The form has been submitted to be validated and processed.
		 */
		if ($this->input->post('reply_action') == 'Submit')
		{
			/**
			 * Validate Form!
			 */
			$this->load->library('form_validation');

			$this->form_validation->set_rules('parent', 'Thread no.', 'required|is_natural|xss_clean');
			$this->form_validation->set_rules('NAMAE', 'Username', 'trim|xss_clean|max_length[64]');
			$this->form_validation->set_rules('MERU', 'Email', 'trim|xss_clean|max_length[64]');
			$this->form_validation->set_rules('subject', 'Subject', 'trim|xss_clean|max_length[64]');
			if ($this->input->post('parent') == 0)
			{
				$this->form_validation->set_rules('KOMENTO', 'Comment', 'trim|xss_clean');
			}
			else
			{
				$this->form_validation->set_rules('KOMENTO', 'Comment', 'trim|min_length[3]|max_length[4096]|xss_clean');
			}
			$this->form_validation->set_rules('delpass', 'Password', 'required|min_length[3]|max_length[32]|xss_clean');

			if (Auth::has_access('maccess.mod'))
			{
				$this->form_validation->set_rules('reply_postas', 'Post as',
					'required|callback__is_valid_allowed_level|xss_clean');
				$this->form_validation->set_message('_is_valid_allowed_level',
					'You did not specify a valid user level to post as.');
			}

			if ($this->form_validation->run() == FALSE)
			{
				$this->form_validation->set_error_delimiters('', '');

				/**
				 * Display a default/standard output for NON-AJAX REQUESTS.
				 */
				$this->set_title(__('Error'));
				Chan::_set_parameters(
					array(
					'error' => validation_errors()
					), array(
					'tools_search' => TRUE
					)
				);
				$this->build('error');
				return FALSE;
			}

			/**
			 * Everything is GOOD! Continue with posting the content to the board.
			 */
			$data = array(
				'num' => $this->input->post('parent'),
				'name' => $this->input->post('NAMAE'),
				'email' => $this->input->post('MERU'),
				'subject' => $this->input->post('subject'),
				'comment' => $this->input->post('KOMENTO'),
				'spoiler' => $this->input->post('reply_spoiler'),
				'password' => $this->input->post('delpass'),
				'postas' => ((Auth::has_access('maccess.mod')) ? $this->input->post('reply_postas') : 'N'),
				'media' => '',
				'ghost' => FALSE
			);

			/**
			 * CHECK #1: Verify the TYPE of POST passing through and insert the data correctly.
			 */
			if (Radix::get_selected()->archive)
			{
				/**
				 * This POST is located in the ARCHIVE and MUST BE A GHOST POST.
				 */
				$data['ghost'] = TRUE;

				/**
				 * Check the $num to ensure that the thread actually exists in the database and that
				 * $num is actually the OP of the thread.
				 */
				$check = $this->post->check_thread(Radix::get_selected(), $data['num']);

				if (isset($check['invalid_thread']) && $check['invalid_thread'] == TRUE)
				{
					$this->set_title(__('Error'));
					Chan::_set_parameters(
						array(
						'error' => __('This thread does not exist.')
						), array(
						'tools_search' => TRUE
						)
					);
					$this->build('error');
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
					$check = $this->post->check_thread(Radix::get_selected(), $data['num']);

					if (isset($check['invalid_thread']) && $check['invalid_thread'] == TRUE)
					{
						$this->set_title(__('Error'));
						Chan::_set_parameters(
							array(
							'error' => __('This thread does not exist.')
							), array(
							'tools_search' => TRUE
							)
						);
						$this->build('error');
						return FALSE;
					}

					if (isset($check['ghost_disabled']) && $check['ghost_disabled'] == TRUE)
					{
						$this->set_title(__('Error'));
						Chan::_set_parameters(
							array(
							'error' => __('This thread is closed.')
							), array(
							'tools_search' => TRUE
							)
						);
						$this->build('error');
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
				$this->set_title(__('Error'));
				Chan::_set_parameters(
					array(
					'error' => __('You are required to upload an image when posting a new thread.')
					), array(
					'tools_search' => TRUE
					)
				);
				$this->build('error');
				return FALSE;
			}

			/**
			 * Check if the comment textarea is EMPTY when no image is uploaded.
			 */
			if (mb_strlen($data['comment']) < 3
				&& (!isset($_FILES['file_image']) || $_FILES['file_image']['error'] == 4))
			{
				$this->set_title(__('Error'));
				Chan::_set_parameters(
					array(
					'error' => __('You are required to write a comment when no image upload is present.')
					), array(
					'tools_search' => TRUE
					)
				);
				$this->build('error');
				return FALSE;
			}

			/**
			 * Check if the IMAGE LIMIT has been reached or if we are posting as a GHOST.
			 */
			if ((isset($check['disable_image_upload']) || $data['ghost'])
				&& (isset($_FILES['file_image']) && $_FILES['file_image']['error'] != 4))
			{
				$this->set_title(__('Error'));
				Chan::_set_parameters(
					array(
					'error' => __('The posting of images has been disabled for this thread.')
					), array(
					'tools_search' => TRUE
					)
				);
				$this->build('error');
				return FALSE;
			}

			/**
			 * Process the IMAGE upload.
			 */
			if (isset($_FILES['file_image']) && $_FILES['file_image']['error'] != 4)
			{
				// Initialize the MEDIA CONFIG and load the UPLOAD library.
				$media_config['upload_path'] = 'content/cache/';
				$media_config['allowed_types'] = 'jpg|jpeg|png|gif';
				$media_config['max_size'] = Radix::get_selected()->max_image_size_kilobytes;
				$media_config['max_width'] = Radix::get_selected()->max_image_size_width;
				$media_config['max_height'] = Radix::get_selected()->max_image_size_height;
				$media_config['overwrite'] = TRUE;

				$this->load->library('upload', $media_config);

				if ($this->upload->do_upload('file_image'))
				{
					$data['media'] = $this->upload->data();
				}
				else
				{
					$this->set_title(__('Error'));
					Chan::_set_parameters(
						array(
						'error' => $this->upload->display_errors()
						), array(
						'tools_search' => TRUE
						)
					);
					$this->build('error');
					return FALSE;
				}
			}

			/**
			 * SEND: Process the entire post and insert the information appropriately.
			 */
			$result = $this->post->comment(Radix::get_selected(), $data);

			/**
			 * RESULT: Output all errors, messages, etc.
			 */
			if (isset($result['error']))
			{
				$this->set_title(__('Error'));
				Chan::_set_parameters(
					array(
					'error' => $result['error']
					), array(
					'tools_search' => TRUE
					)
				);
				$this->build('error');
				return FALSE;
			}
			else if (isset($result['success']))
			{
				/**
				 * Redirect back to the user's POST.
				 */
				if ($result['posted']->thread_num == 0)
				{
					$callback = Uri::create(array(Radix::get_selected()->shortname, 'thread',
							$result['posted']->num)) . '#' . $result['posted']->num;
				}
				else
				{
					$callback = Uri::create(array(Radix::get_selected()->shortname, 'thread',
							$result['posted']->thread_num)) . '#' . $result['posted']->num .
						(($result['posted']->subnum > 0) ? '_' . $result['posted']->subnum : '');
				}

				$this->set_layout('redirect');
				$this->set_title(__('Redirecting...'));
				Chan::_set_parameters(
					array(
						'title' => fuuka_title(0),
						'url' => $callback
					)
				);
				$this->build('redirection');
				return TRUE;
			}
		}


		/**
		 * The form has been submitted to be validated and processed.
		 */
		if ($this->input->post('reply_delete') == 'Delete Selected Posts')
		{
			foreach ($this->input->post('delete') as $idx => $doc_id)
			{
				$post = array(
					'doc_id' => $doc_id,
					'password' => $this->input->post('delpass')
				);

				$this->post->delete(Radix::get_selected(), $post);
			}

			$this->set_layout('redirect');
			$this->set_title(__('Redirecting...'));
			Chan::_set_parameters(
				array(
					'title' => fuuka_title(0),
					'url' => Uri::create(Radix::get_selected()->shortname)
				)
			);
			$this->build('redirection');
			return TRUE;
		}


		/**
		 * The form has been submitted to be validated and processed.
		 */
		if ($this->input->post('reply_report') == 'Report Selected Posts')
		{
			$this->load->model('report_model', 'report');
			foreach ($this->input->post('delete') as $idx => $doc_id)
			{
				$post = array(
					'board' => Radix::get_selected()->id,
					'doc_id' => $doc_id,
					'reason' => $this->input->post('KOMENTO')
				);

				$this->report->add($post);
			}

			$this->set_layout('redirect');
			$this->set_title(__('Redirecting...'));
			Chan::_set_parameters(
				array(
					'title' => fuuka_title(0),
					'url' => Uri::create(Radix::get_selected()->shortname . '/thread/' .
						$this->input->post('parent'))
				)
			);
			$this->build('redirection');
			return TRUE;
		}

		/**
		 * ERROR: We reached the point of no return and wasn't able to do anything.
		 */
		show_404();
	}

}
