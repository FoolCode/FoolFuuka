<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Chan extends API_Controller
{


	function __construct()
	{
		header("Access-Control-Allow-Origin: http://boards.4chan.org");
		header("Access-Control-Allow-Origin: https://boards.4chan.org", FALSE);
		parent::__construct();
	}

	function vote_post()
	{
		$this->check_board();

		if(!$this->post('doc_id') || !is_natural($this->post('doc_id')) ||
			!$this->post('vote') || !in_array($this->post('vote'), array(-1, 1)))
		{
			$this->response(array('error' => _('Faulty value')), 404);
		}

		$this->load->model('vote');
		$vote = $this->vote->add(get_selected_radix(), $this->post('doc_id'), $this->post('vote'));

		if(isset($vote['error']))
		{
			$this->response($vote, 404);
		}

		$count = $this->vote->count(get_selected_radix(), $this->post('doc_id'));

		$this->response(array('success' => $count), 200);
	}


	/**
	 * Returns the latest threads
	 *
	 * Available filters: page, per_page (default:30, max:100), orderby
	 *
	 * @author Woxxy
	 */
	function threads_get()
	{
		$this->check_board();

		if ($this->get('page'))
		{
			if (!is_natural($this->get('page')))
			{
				$this->response(array('error' => _('Faulty page value')), 404);
			}
			else if ($this->get('page') > 100)
			{
				$this->response(array('error' => _('Can\'t go over page 500')), 404);
			}
			else
			{
				$page = intval($this->get('page'));
			}
		}
		else
		{
			$page = 1;
		}


		if ($this->get('per_page'))
		{
			if (!is_natural($this->get('per_page')))
			{
				$this->response(array('error' => _('Faulty per_page value')), 404);
			}
			else if ($this->get('per_page') > 50)
			{
				$this->response(array('error' => _('Can\'t return more than 50 threads')),
					404);
			}
			else
			{
				$per_page = intval($this->get('per_page'));
			}
		}
		else
		{
			$per_page = 25;
		}



		$posts = $this->post->get_latest(get_selected_radix(), $page,
			array('per_page' => $per_page));

		if (count($posts) > 0)
		{
			$this->response($posts, 200); // 200 being the HTTP response code
		}
		else
		{
			$this->response(array('error' => _('Threads could not be found')), 404);
		}
	}


	/**
	 * Returns chapters from selected page
	 *
	 * Available filters: page, per_page (default:30, max:100), orderby
	 *
	 * @author Woxxy
	 */
	function ghost_threads_get()
	{
		$this->check_board();

		if ($this->get('page'))
		{
			if (!is_natural($this->get('page')))
			{
				$this->response(array('error' => _('Faulty page value')), 404);
			}
			else if ($this->get('page') > 100)
			{
				$this->response(array('error' => _('Can\'t go over page 500')), 404);
			}
			else
			{
				$page = intval($this->get('page'));
			}
		}
		else
		{
			$page = 1;
		}


		if ($this->get('per_page'))
		{
			if (!is_natural($this->get('per_page')))
			{
				$this->response(array('error' => _('Faulty per_page value')), 404);
			}
			else if ($this->get('per_page') > 50)
			{
				$this->response(array('error' => _('Can\'t return more than 50 threads')),
					404);
			}
			else
			{
				$per_page = intval($this->get('per_page'));
			}
		}
		else
		{
			$per_page = 25;
		}

		$page = intval($page);

		$posts = $this->post->get_latest_ghost(get_selected_radix(), $page,
			array('per_page' => $per_page));

		if (count($posts) > 0)
		{
			$this->response($posts, 200); // 200 being the HTTP response code
		}
		else
		{
			// no comics
			$this->response(array('error' => _('Threads could not be found')), 404);
		}
	}


	/**
	 * Returns a thread
	 *
	 * Available filters: num (required)
	 *
	 * @author Woxxy
	 */
	function thread_get()
	{
		$this->check_board();

		if (!$this->get('num'))
		{
			$this->response(array('error' => _('You have to select a thread number')),
				404);
		}

		if (!is_natural($this->get('num')))
		{
			$this->response(array('error' => _('Faulty thread number')), 404);
		}

		$num = intval($this->get('num'));

		$from_realtime = FALSE;

		// build an array if we have more specifications
		if ($this->get('latest_doc_id'))
		{
			if (!is_natural($this->get('latest_doc_id')) && $this->get('latest_doc_id') < 0)
			{
				$this->response(array('error' => _('Your latest_doc_id is malformed')), 404);
			}

			$latest_doc_id = intval($this->get('latest_doc_id'));
			$from_realtime = TRUE;

			$thread = $this->post->get_thread(
				get_selected_radix(), $num,
				array('realtime' => TRUE, 'type' => 'from_doc_id', 'type_extra' => array('latest_doc_id' => $latest_doc_id))
			);
		}
		else
		{
			$thread = $this->post->get_thread(
				get_selected_radix(), $num, array()
			);
		}

		if ($thread !== FALSE)
		{
			$this->response($thread, 200); // 200 being the HTTP response code
		}
		else
		{
			if ($from_realtime)
			{
				$response = array();
				$response[$num['num']] = array('posts' => array());
				$this->response($response, 200);
			}
			// no comics
			$this->response(array('error' => _('Thread could not be found')), 200);
		}
	}


	function post_get()
	{
		$this->check_board();

		if (!$this->get('num'))
		{
			$this->response(array('error' => _('You have to select a thread number')),
				404);
		}

		if (!is_post_number($this->get('num')))
		{
			$this->response(array('error' => _('Faulty thread number')), 404);
		}

		$post = $this->post->get_post_by_num(get_selected_radix(), $this->get('num'));

		if (!$post)
		{
			$this->response(array('error' => _('Post could not be found')), 404);
		}

		$this->response($post, 200);
	}


	function thread_ghosts_posts_get()
	{
		$this->check_board();

		if (!$this->get('num'))
		{
			$this->response(array('error' => _('You have to select a thread number')),
				404);
		}

		if (!is_natural($this->get('num')))
		{
			$this->response(array('error' => _('Faulty thread number')), 404);
		}

		$num = intval($this->get('num'));

		$thread = $this->post->get_thread(
			get_selected_radix(), $num,
			array('realtime' => TRUE, 'type' => 'from_doc_id', 'type_extra' => array('latest_doc_id' => $latest_doc_id))
		);

		if ($thread !== FALSE)
		{
			$this->response($thread, 200); // 200 being the HTTP response code
		}
		else
		{
			if ($from_realtime)
			{
				$response = array();
				$response[$num['num']] = array('posts' => array());
				$this->response($response, 200);
			}
			// no comics
			$this->response(array('error' => _('Thread could not be found')), 200);
		}
	}


	function ghost_posts_get()
	{
		$this->check_board();

		if ($this->get('page'))
		{
			if (!is_natural($this->get('page')))
			{
				$this->response(array('error' => _('Faulty page value')), 404);
			}
			else if ($this->get('page') > 100)
			{
				$this->response(array('error' => _('Can\'t go over page 500')), 404);
			}
			else
			{
				$page = intval($this->get('page'));
			}
		}
		else
		{
			$page = 1;
		}


		if ($this->get('per_page'))
		{
			if (!is_natural($this->get('per_page')))
			{
				$this->response(array('error' => _('Faulty per_page value')), 404);
			}
			else if ($this->get('per_page') > 3000)
			{
				$this->response(array('error' => _('Can\'t return more than 3000 ghost posts')),
					404);
			}
			else
			{
				$per_page = intval($this->get('per_page'));
			}
		}
		else
		{
			$per_page = 1000;
		}

		$posts = $this->post->get_posts_ghost(get_selected_radix(), $page,
			array('per_page' => $per_page));

		if (count($posts['posts']) > 0)
		{
			$this->response($posts, 200); // 200 being the HTTP response code
		}
		else
		{
			// no comics
			$this->response(array('error' => _('Ghost posts could not be found')), 404);
		}
	}


	function mod_post_actions_post()
	{
		if (!$this->tank_auth->is_allowed())
		{
			$this->response(array('error' => _('Forbidden')), 403);
		}

		$this->check_board();

		if (!$this->post('actions') || !$this->post('doc_id'))
		{
			$this->response(array('error' => _('Missing arguments')), 404);
		}


		// action should be an array
		// array('ban_md5', 'ban_user', 'remove_image', 'remove_post', 'remove_report');
		$actions = $this->post('actions');
		if (!is_array($actions))
		{
			$this->response(array('error' => _('Invalid action')), 404);
		}

		$doc_id = $this->post('doc_id');
		$board = $this->radix->get_by_shortname($this->post('board'));

		$this->load->model('post');
		$post = $this->post->get_by_doc_id($board, $doc_id);

		if ($post === FALSE)
		{
			$this->response(array('error' => _('Post not found')), 404);
		}


		if (in_array('ban_md5', $actions))
		{
			$this->post->ban_media($post->media_hash);
			$actions = array_diff($actions, array('remove_image'));
		}

		if (in_array('remove_post', $actions))
		{
			$this->post->delete(
				$board,
				array(
				'post' => $post->doc_id,
				'password' => '',
				'type' => 'post'
				)
			);

			$actions = array_diff($actions, array('remove_image', 'remove_report'));
		}

		// if we banned md5 we already removed the image
		if (in_array('remove_image', $actions))
		{
			/*
			$this->post->delete(
				$board,
				array(
				'post' => $post->doc_id,
				'password' => '',
				'type' => 'image'
				)
			);*/
			$this->post->delete_media($board, $post);
		}

		if (in_array('ban_user', $actions))
		{
			$this->load->model('poster');
			$this->poster->ban(
				$post->id, isset($data['length']) ? $data['length'] : NULL,
				isset($data['reason']) ? $data['reason'] : NULL
			);
		}

		if (in_array('remove_report', $actions))
		{
			$this->load->model('report');
			$this->report->remove_by_doc_id($board, $doc_id);
		}


		$this->response(array('success' => TRUE), 200);
	}

}
