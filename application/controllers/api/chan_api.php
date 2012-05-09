<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Chan_API extends API_Controller
{


	function __construct()
	{
		parent::__construct();
	}

	/*
	function vote_post()
	{
		$this->check_board();

		if(!$this->post('doc_id') || !is_natural($this->post('doc_id')) ||
			!$this->post('vote') || !in_array($this->post('vote'), array(-1, 1)))
		{
			$this->response(array('error' => __('Faulty value')), 404);
		}

		$this->load->model('vote_model', 'vote');
		$vote = $this->vote->add(get_selected_radix(), $this->post('doc_id'), $this->post('vote'));

		if(isset($vote['error']))
		{
			$this->response($vote, 404);
		}

		$count = $this->vote->count(get_selected_radix(), $this->post('doc_id'));

		$this->response(array('success' => $count), 200);
	}
	*/

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
				$this->response(array('error' => __('Faulty page value')), 404);
			}
			else if ($this->get('page') > 100)
			{
				$this->response(array('error' => __('Can\'t go over page 500')), 404);
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
				$this->response(array('error' => __('Faulty per_page value')), 404);
			}
			else if ($this->get('per_page') > 50)
			{
				$this->response(array('error' => __('Can\'t return more than 50 threads')),
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
			$this->response(array('error' => __('Threads could not be found')), 404);
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
				$this->response(array('error' => __('Faulty page value')), 404);
			}
			else if ($this->get('page') > 100)
			{
				$this->response(array('error' => __('Can\'t go over page 500')), 404);
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
				$this->response(array('error' => __('Faulty per_page value')), 404);
			}
			else if ($this->get('per_page') > 50)
			{
				$this->response(array('error' => __('Can\'t return more than 50 threads')),
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
			$this->response(array('error' => __('Threads could not be found')), 404);
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
			$this->response(array('error' => __('You have to select a thread number')),
				404);
		}

		if (!is_natural($this->get('num')))
		{
			$this->response(array('error' => __('Faulty thread number')), 404);
		}

		$num = intval($this->get('num'));

		$from_realtime = FALSE;

		// build an array if we have more specifications
		if ($this->get('latest_doc_id'))
		{
			if (!is_natural($this->get('latest_doc_id')) && $this->get('latest_doc_id') < 0)
			{
				$this->response(array('error' => __('Your latest_doc_id is malformed')), 404);
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
			$this->response($thread['result'], 200); // 200 being the HTTP response code
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
			$this->response(array('error' => __('Thread could not be found')), 200);
		}
	}


	function post_get()
	{
		$this->check_board();

		if (!$this->get('num'))
		{
			$this->response(array('error' => __('You have to select a thread number')),
				404);
		}

		if (!is_post_number($this->get('num')))
		{
			$this->response(array('error' => __('Faulty thread number')), 404);
		}

		$post = $this->post->get_post_by_num(get_selected_radix(), $this->get('num'));

		if (!$post)
		{
			$this->response(array('error' => __('Post could not be found')), 404);
		}

		$this->response($post, 200);
	}


	function thread_ghosts_posts_get()
	{
		$this->check_board();

		if (!$this->get('num'))
		{
			$this->response(array('error' => __('You have to select a thread number')),
				404);
		}

		if (!is_natural($this->get('num')))
		{
			$this->response(array('error' => __('Faulty thread number')), 404);
		}

		$num = intval($this->get('num'));

		$thread = $this->post->get_thread(
			get_selected_radix(), $num,
			array('realtime' => TRUE, 'type' => 'from_doc_id', 'type_extra' => array('latest_doc_id' => $latest_doc_id))
		);
		
		$thread = $thread['result'];

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
			$this->response(array('error' => __('Thread could not be found')), 200);
		}
	}


	function ghost_posts_get()
	{
		$this->check_board();

		if ($this->get('page'))
		{
			if (!is_natural($this->get('page')))
			{
				$this->response(array('error' => __('Faulty page value')), 404);
			}
			else if ($this->get('page') > 100)
			{
				$this->response(array('error' => __('Can\'t go over page 500')), 404);
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
				$this->response(array('error' => __('Faulty per_page value')), 404);
			}
			else if ($this->get('per_page') > 3000)
			{
				$this->response(array('error' => __('Can\'t return more than 3000 ghost posts')),
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
			$this->response(array('error' => __('Ghost posts could not be found')), 404);
		}
	}
}
