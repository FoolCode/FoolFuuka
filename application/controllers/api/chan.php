<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Chan extends REST_Controller
{
	/**
	 * Returns the latest threads
	 * 
	 * Available filters: page, per_page (default:30, max:100), orderby
	 * 
	 * @author Woxxy
	 */
	function threads_get()
	{
		$this->_check_board();

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
				$this->response(array('error' => _('Can\'t return more than 50 threads')), 404);
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

		

		$posts = $this->post->get_latest($page, $per_page);

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
	 * Returns chapters from selected page
	 * 
	 * Available filters: page, per_page (default:30, max:100), orderby
	 * 
	 * @author Woxxy
	 */
	function ghost_threads_get()
	{
		$this->_check_board();

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
				$this->response(array('error' => _('Can\'t return more than 50 threads')), 404);
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

		$posts = $this->post->get_latest_ghost($page, $per_page);

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
		$this->_check_board();

		if (!$this->get('num'))
		{
			$this->response(array('error' => _('You have to select a thread number')), 404);
		}
		
		if (!is_natural($this->get('num')))
		{
			$this->response(array('error' => _('Faulty thread number')), 404);
		}
		
		$num = intval($this->get('num'));
		
		// build an array if we have more specifications
		if ($this->get('timestamp'))
		{
			if(!is_natural($this->get('timestamp')) && $this->get('timestamp') >= 0)
			{
				$this->response(array('error' => _('Your timestamp is malformed')), 404);
			}
			
			$timestamp = intval($this->get('timestamp'));
			$num = array('num' => $num, 'timestamp' => $timestamp);
		}
		
		$thread = $this->post->get_thread($num);
		
		if ($thread !== FALSE)
		{
			$this->response($thread, 200); // 200 being the HTTP response code
		}
		else
		{
			// no comics
			$this->response(array('error' => _('Thread could not be found')), 404);
		}
	}

	function ghost_posts_get()
	{
		$this->_check_board();
		
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
				$this->response(array('error' => _('Can\'t return more than 3000 ghost posts')), 404);
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
		
		$posts = $this->post->get_posts_ghost($page, $per_page);

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

}