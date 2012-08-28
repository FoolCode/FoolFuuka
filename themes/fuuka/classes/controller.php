<?php

namespace Foolfuuka\Themes\Fuuka;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Controller_Theme_Fu_Fuuka_Chan extends \Foolfuuka\Controller_Chan
{

	/**
	 * @param int $page
	 */
	public function radix_page($page = 1)
	{
		$order = \Cookie::get('default_theme_page_mode_'. ($this->_radix->archive ? 'archive' : 'board')) === 'by_thread'
			? 'by_thread' : 'by_post';

		$options = array(
			'per_page' => 24,
			'per_thread' => 6,
			'order' => $order
		);

		return $this->latest($page, $options);
	}
	
	
	public function radix_gallery($page = 1)
	{
		throw new \HttpNotFoundException;
	}

	/**
	 * @return bool
	 */
	public function radix_submit()
	{
		// adapter
		if(!\Input::post())
		{
			return $this->error(__('You aren\'t sending the required fields for creating a new message.'));
		}
		
		if ( ! \Security::check_token())
		{
			return $this->error(__('The security token wasn\'t found. Try resubmitting.'));
		}
		
		
		if (\Input::post('reply_delete'))
		{
			foreach (\Input::post('delete') as $idx => $doc_id)
			{
				try
				{
					$comments = \Board::forge()
						->get_post()
						->set_options('doc_id', $doc_id)
						->set_radix($this->_radix)
						->get_comments();

					$comment = current($comments);
					$comment->delete(\Input::post('delpass'));
				}
				catch (Model\BoardException $e)
				{
					return $this->response(array('error' => $e->getMessage()), 404);
				}
				catch (Model\CommentDeleteWrongPassException $e)
				{
					return $this->response(array('error' => $e->getMessage()), 404);
				}
			}

			$this->_theme->set_layout('redirect');
			$this->_theme->set_title(__('Redirecting...'));
			$this->_theme->bind('url', \Uri::create(array($this->_radix->shortname, 'thread', $comment->thread_num)));
			return \Response::forge($this->_theme->build('redirection'));
		}

		
		if (\Input::post('reply_report'))
		{
			
			foreach (\Input::post('delete') as $idx => $doc_id)
			{
				try
				{
					\Report::add($this->_radix, $doc_id, \Input::post('KOMENTO'));
				}
				catch (Model\ReportException $e)
				{
					return $this->response(array('error' => $e->getMessage()), 404);
				}
			}
			
			$this->_theme->set_layout('redirect');
			$this->_theme->set_title(__('Redirecting...'));
			$this->_theme->bind('url', \Uri::create($this->_radix->shortname.'/thread/'.\Input::post('parent')));
			return \Response::forge($this->_theme->build('redirection'));
		}

		// Determine if the invalid post fields are populated by bots.
		if (isset($post['name']) && mb_strlen($post['name']) > 0)
			return $this->error();
		if (isset($post['reply']) && mb_strlen($post['reply']) > 0)
			return $this->error();
		if (isset($post['email']) && mb_strlen($post['email']) > 0)
			return $this->error();

		$data = array();

		$post = \Input::post();

		if(isset($post['parent']))
			$data['thread_num'] = $post['parent'];
		if(isset($post['NAMAE']))
			$data['name'] = $post['NAMAE'];
		if(isset($post['MERU']))
			$data['email'] = $post['MERU'];
		if(isset($post['subject']))
			$data['title'] = $post['subject'];
		if(isset($post['KOMENTO']))
			$data['comment'] = $post['KOMENTO'];
		if(isset($post['delpass']))
			$data['delpass'] = $post['delpass'];
		if(isset($post['reply_spoiler']))
			$data['spoiler'] = true;
		if(isset($post['reply_postas']))
			$data['capcode'] = $post['reply_postas'];
		
		$media = null;

		if (count(\Upload::get_files()))
		{
			try
			{
				$media = \Media::forge_from_upload($this->_radix);
				$media->spoiler = isset($data['spoiler']) && $data['spoiler'];
			}
			catch (\Model\MediaUploadNoFileException $e)
			{
				$media = null;
			}
			catch (\Model\MediaUploadException $e)
			{
				return $this->error($e->getMessage());
			}
		}

		return $this->submit($data, $media);
		
		
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
		

		/**
		 * ERROR: We reached the point of no return and wasn't able to do anything.
		 */
		show_404();
	}

}
