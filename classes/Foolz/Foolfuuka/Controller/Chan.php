<?php

namespace Foolz\Foolfuuka\Controller;

class Chan extends \Controller
{

	protected $_theme = null;
	protected $_radix = null;
	protected $_to_bind = null;


	public function before()
	{
		parent::before();

		$this->_theme = \Theme::instance('foolfuuka');

		$pass = \Cookie::get('reply_password');
		$name = \Cookie::get('reply_name');
		$email = \Cookie::get('reply_email');

		// get the password needed for the reply field
		if( ! $pass || strlen($pass) < 3)
		{
			$pass = \Str::random('alnum', 7);
			\Cookie::set('reply_password', $pass, 60*60*24*30);
		}

		// KEEP THIS IN SYNC WITH THE ONE IN THE POSTS ADMIN PANEL
		$this->_to_bind = array(
			'user_name' => $name,
			'user_email' => $email,
			'user_pass' => $pass,
			'disable_headers' => false,
			'is_page' => false,
			'is_thread' => false,
			'is_last50' => false,
			'order' => false,
			'modifiers' => array(),
			'backend_vars' => array(
				'user_name' => $name,
				'user_email' => $email,
				'user_pass' => $pass,
				'site_url'  => \Uri::base(),
				'default_url'  => \Uri::base(),
				'archive_url'  => \Uri::base(),
				'system_url'  => \Uri::base(),
				'api_url'   => \Uri::base(),
				'cookie_domain' => \Foolz\Config\Config::get('foolz/foolframe', 'package', 'config.cookie_domain'),
				'cookie_prefix' => \Foolz\Config\Config::get('foolz/foolframe', 'package', 'config.cookie_prefix'),
				'selected_theme' => isset($this->_theme)?$this->_theme->get_selected_theme():'',
				'csrf_token_key' => \Config::get('security.csrf_token_key'),
				'images' => array(
					'banned_image' => \Uri::base().$this->_theme->fallback_asset('images/banned-image.png'),
					'banned_image_width' => 150,
					'banned_image_height' => 150,
					'missing_image' => \Uri::base().$this->_theme->fallback_asset('images/missing-image.jpg'),
					'missing_image_width' => 150,
					'missing_image_height' => 150,
				),
				'gettext' => array(
					'submit_state' => __('Submitting'),
					'thread_is_real_time' => __('This thread is being displayed in real time.'),
					'update_now' => __('Update now')
				)
			)
		);

		$this->_theme->bind($this->_to_bind);
		$this->_theme->set_partial('tools_modal', 'tools_modal');
		$this->_theme->set_partial('tools_search', 'tools_search');
		$this->_theme->set_partial('tools_advanced_search', 'advanced_search');
	}


	public function router($method, $params)
	{
		$segments = \Uri::segments();

		if (isset($segments[0]) && $segments[0] === 'search')
		{
			\Response::redirect(implode('/', array_merge(array('_'), $segments)));
		}

		// the underscore function is never a board
		if (isset($segments[0]) && $segments[0] !== '_')
		{

			$this->_radix = \Radix::setSelectedByShortname($method);

			if ($this->_radix)
			{
				$this->_theme->bind('radix', $this->_radix);
				$this->_to_bind['backend_vars']['board_shortname'] = $this->_radix->shortname;
				$this->_theme->bind($this->_to_bind);
				$this->_theme->set_title($this->_radix->formatted_title);
				$method = array_shift($params);

				// methods callable with a radix are prefixed with radix_
				if (method_exists($this, 'radix_'.$method))
				{
					return call_user_func_array(array($this, 'radix_'.$method), $params);
				}

				// a board and no function means we're out of the street
				throw new \HttpNotFoundException;
			}
		}

		$this->_radix = null;
		$this->_theme->bind('radix', null);
		$this->_theme->set_title(\Preferences::get('fu.gen.website_title'));

		if (method_exists($this, 'action_'.$method))
		{
			return call_user_func_array(array($this, 'action_'.$method), $params);
		}

		throw new \HttpNotFoundException;
	}


	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		$this->_theme->bind('disable_headers', TRUE);
		return \Response::forge($this->_theme->build('index'));
	}


	/**
	 * The 404 action for the application.
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_404($error = null)
	{
		return \Response::forge($this->_theme->build('error',
					array(
					'error' => $error === null ? __('Page not found. You can use the search if you were looking for something!') : $error
				)), 404);
	}


	protected function error($error = null, $code = 200)
	{
		if (is_null($error))
		{
			return \Response::forge($this->_theme->build('error', array('error' => __('We encountered an unexpected error.'))), $code);
		}
		return \Response::forge($this->_theme->build('error', array('error' => $error)), $code);
	}

	protected function message($level = 'success', $message = null, $code = 200)
	{
		return \Response::forge($this->_theme->build('message', array('level' => $level, 'message' => $message)), $code);
	}


	public function action_theme($theme = 'default', $style = '')
	{
		$this->_theme->set_title(__('Changing Theme Settings'));

		if (!in_array($theme, $this->_theme->get_available_themes()))
		{
			$theme = 'default';
		}

		\Cookie::set('theme', $theme, 31536000, '/');

		if ($style !== '' && in_array($style, $this->_theme->get_available_styles($theme)))
		{
			\Cookie::set('theme_' . $theme . '_style', $style, 31536000, '/');
		}

		if (\Input::referrer())
		{
			$this->_theme->bind('url', \Input::referrer());
		}
		else
		{
			$this->_theme->bind('url', \Uri::base());
		}

		$this->_theme->set_layout('redirect');
		return \Response::forge($this->_theme->build('redirection'));
	}


	public function action_language($theme = 'en_EN')
	{
		$this->_theme->set_title(__('Changing Language'));

		\Cookie::set('language', $theme, 31536000);

		if (\Input::referrer())
		{
			$this->_theme->bind('url', \Input::referrer());
		}
		else
		{
			$this->_theme->bind('url', \Uri::base());
		}

		$this->_theme->set_layout('redirect');
		return \Response::forge($this->_theme->build('redirection'));
	}


	public function action_opensearch()
	{
		return \Response::forge(\View::forge('foolfuuka::opensearch'));
	}


	public function radix_page_mode($_mode = 'by_post')
	{
		$mode = $_mode === 'by_thread' ? 'by_thread' : 'by_post';
		$type = $this->_radix->archive ? 'archive' : 'board';
		\Cookie::set('default_theme_page_mode_'.$type, $mode);

		\Response::redirect($this->_radix->shortname);
	}


	public function radix_page($page = 1)
	{
		$order = \Cookie::get('default_theme_page_mode_'. ($this->_radix->archive ? 'archive' : 'board')) === 'by_thread'
			? 'by_thread' : 'by_post';

		$options = array(
			'per_page' => $this->_radix->threads_per_page,
			'per_thread' => 5,
			'order' => $order
		);

		return $this->latest($page, $options);
	}


	public function radix_ghost($page = 1)
	{
		$options = array(
			'per_page' => $this->_radix->threads_per_page,
			'per_thread' => 5,
			'order' => 'ghost'
		);

		return $this->latest($page, $options);
	}


	protected function latest($page = 1, $options = array())
	{
		\Profiler::mark('Controller Chan::latest Start');
		try
		{
			$board = \Board::forge()
				->getLatest()
				->setRadix($this->_radix)
				->setPage($page)
				->setOptions($options);

			// execute in case there's more exceptions to handle
			$board->getComments();
			$board->getCount();
		}
		catch (Foolz\Foolfuuka\Model\BoardException $e)
		{
			\Profiler::mark('Controller Chan::latest End Prematurely');
			return $this->error($e->getMessage());
		}

		if ($page > 1)
		{
			switch($options['order'])
			{
				case 'by_post':
					$order_string = __('Threads by latest replies');
					break;
				case 'by_thread':
					$order_string = __('Threads by creation');
					break;
				case 'ghost':
					$order_string = __('Threads by latest ghost replies');
					break;
			}

			$this->_theme->set_title(__('Page').' '.$page);
			$this->_theme->bind('section_title', $order_string.' - '.__('Page').' '.$page);
		}

		$this->_theme->bind(array(
			'is_page' => true,
			'board' => $board,
			'posts_per_thread' => $options['per_thread'] - 1,
			'order' => $options['order'],
			'pagination' => array(
				'base_url' => \Uri::create(array($this->_radix->shortname, $options['order'] === 'ghost' ? 'ghost' : 'page')),
				'current_page' => $page,
				'total' => $board->getPages()
			)
		));

		if (!$this->_radix->archive)
		{
			$this->_theme->set_partial('tools_new_thread_box', 'tools_reply_box');
		}

		\Profiler::mark_memory($this, 'Controller Chan $this');
		\Profiler::mark('Controller Chan::latest End');
		return \Response::forge($this->_theme->build('board'));
	}



	public function radix_thread($num = 0)
	{
		return $this->thread($num);
	}

	public function radix_last50($num = 0)
	{
		\Response::redirect($this->_radix->shortname.'/last/50/'.$num);
	}

	public function radix_last($limit = 0, $num = 0)
	{
		if ( ! ctype_digit((string) $limit) || $limit < 1)
		{
			return $this->action_404();
		}

		return $this->thread($num, array('type' => 'last_x', 'last_limit' => $limit));
	}


	protected function thread($num = 0, $options = array())
	{
		\Profiler::mark('Controller Chan::thread Start');
		$num = str_replace('S', '', $num);

		try
		{
			$board = \Board::forge()
				->getThread($num)
				->setRadix($this->_radix)
				->setOptions($options);

			// execute in case there's more exceptions to handle
			$thread = $board->getComments();
		}
		catch (\Foolz\Foolfuuka\Model\BoardThreadNotFoundException $e)
		{
			\Profiler::mark('Controller Chan::thread End Prematurely');
			return $this->radix_post($num);
		}
		catch (\Foolz\Foolfuuka\Model\BoardException $e)
		{
			\Profiler::mark('Controller Chan::thread End Prematurely');
			return $this->error($e->getMessage());
		}

		// get the latest doc_id and latest timestamp for realtime stuff
		$latest_doc_id = $board->getHighest('doc_id')->doc_id;
		$latest_timestamp = $board->getHighest('timestamp')->timestamp;

		// check if we can determine if posting is disabled
		try
		{
			$thread_status = $board->checkThreadStatus();
		}
		catch (\Foolz\Foolfuuka\Model\BoardThreadNotFoundException $e)
		{
			\Profiler::mark('Controller Chan::thread End Prematurely');
			return $this->error();
		}

		$this->_theme->set_title(__('Thread').' #'.$num);
		$this->_theme->bind(array(
			'thread_id' => $num,
			'board' => $board,
			'is_thread' => true,
			'disable_image_upload' => $thread_status['disable_image_upload'],
			'thread_dead' => $thread_status['dead'],
			'latest_doc_id' => $latest_doc_id,
			'latest_timestamp' => $latest_timestamp,
			'thread_op_data' => $thread[$num]['op']
		));

		$backend_vars = $this->_theme->get_var('backend_vars');
		$backend_vars['thread_id'] = $num;
		$backend_vars['latest_timestamp'] = $latest_timestamp;
		$backend_vars['latest_doc_id'] = $latest_doc_id;
		$backend_vars['board_shortname'] = $this->_radix->shortname;

		if (isset($options['last_limit']) && $options['last_limit'])
		{
			$backend_vars['last_limit'] = $options['last_limit'];
		}

		$this->_theme->bind('backend_vars', $backend_vars);

		if ( ! $thread_status['closed'])
		{
			$this->_theme->set_partial('tools_reply_box', 'tools_reply_box');
		}

		\Profiler::mark_memory($this, 'Controller Chan $this');
		\Profiler::mark('Controller Chan::thread End');
		return \Response::forge($this->_theme->build('board'));
	}


	public function radix_gallery($page = 1)
	{
		try
		{
			$board = \Board::forge()
				->getThreads()
				->setRadix($this->_radix)
				->setPage($page)
				->setOptions('per_page', 100);

			$comments = $board->getComments();
		}
		catch (\Foolz\Foolfuuka\Model\BoardException $e)
		{
			return $this->error($e->getMessage());
		}

		$this->_theme->bind(array(
			'board' => $board,
			'pagination' => array(
				'base_url' => \Uri::create(array($this->_radix->shortname, 'gallery')),
				'current_page' => $page,
				'total' => $board->getPages()
			)
		));
		return \Response::forge($this->_theme->build('gallery'));
	}


	public function radix_post($num = 0)
	{
		try
		{
			if (\Input::post('post') || ! \Board::isValidPostNumber($num))
			{
				// obtain post number and unset search string
				preg_match('/(?:^|\/)(\d+)(?:[_,]([0-9]*))?/', \Input::post('post') ? : $num, $post);
				unset($post[0]);

				\Response::redirect(\Uri::create(array($this->_radix->shortname, 'post', implode('_', $post))), 'location', 301);
			}

			$board = \Board::forge()
				->getPost()
				->setRadix($this->_radix)
				->setOptions('num', $num);

			$comments = $board->getComments();
		}
		catch (\Foolz\Foolfuuka\Model\BoardMalformedInputException $e)
		{
			return $this->error(__('The post number you submitted is invalid.'));
		}
		catch (\Foolz\Foolfuuka\Model\BoardPostNotFoundException $e)
		{
			return $this->error(__('The post you are looking for does not exist.'));
		}

		// it always returns an array
		$comment = current($comments);

		$redirect =  \Uri::create($this->_radix->shortname.'/thread/'.$comment->thread_num.'/');

		if ( ! $comment->op)
		{
			$redirect .= '#'.$comment->num.($comment->subnum ? '_'.$comment->subnum :'');
		}

		$this->_theme->set_title(__('Redirecting'));
		$this->_theme->set_layout('redirect');
		return \Response::forge($this->_theme->build('redirection', array('url' => $redirect)));
	}


	/**
	 * Display all of the posts that contain the MEDIA HASH provided.
	 * As of 2012-05-17, fetching of posts with same media hash is done via search system.
	 * Due to backwards compatibility, this function will still be used for non-urlsafe and urlsafe hashes.
	 */
	public function radix_image()
	{
		// support non-urlsafe hash
		$uri = \Uri::segments();
		array_shift($uri);
		array_shift($uri);

		$imploded_uri = rawurldecode(implode('/', $uri));
		if (mb_strlen($imploded_uri) < 22)
		{
			return $this->error(__('Your image hash is malformed.'));
		}

		// obtain actual media hash (non-urlsafe)
		$hash = mb_substr($imploded_uri, 0, 22);
		if (strpos($hash, '/') !== false || strpos($hash, '+') !== false)
		{
			$hash = \Media::urlsafe_b64encode(\Media::urlsafe_b64decode($hash));
		}

		// Obtain the PAGE from URI.
		$page = 1;
		if (mb_strlen($imploded_uri) > 28)
		{
			$page = substr($imploded_uri, 28);
		}

		// Fetch the POSTS with same media hash and generate the IMAGEPOSTS.
		$page = intval($page);
		return \Response::redirect(\Uri::create(array(
			$this->_radix->shortname, 'search', 'image', $hash, 'order', 'desc', 'page', $page)), 'location', 301);
	}


	/**
	 * @param $filename
	 */
	public function radix_full_image($filename)
	{
		// Check if $filename is valid.
		if ( ! in_array(\Input::extension(), array('gif', 'jpg', 'png', 'pdf')) || ! ctype_digit((string) substr($filename, 0, 13)))
		{
			return $this->action_404(__('The filename submitted is not compatible with the system.'));
		}

		try
		{
			$media = \Media::get_by_filename($this->_radix, $filename.'.'.\Input::extension());
		}
		catch (\Foolz\Foolfuuka\Model\MediaException $e)
		{
			return $this->action_404(__('The image was never in our databases.'));
		}

		if ($media->getMediaLink())
		{
			return \Response::redirect($media->getMediaLink(), 'location', 303);
		}

		return \Response::redirect(
			\Uri::create(array($this->_radix->shortname, 'search', 'image', rawurlencode(substr($media->media_hash, 0, -2)))), 'location', 404);
	}


	public function radix_redirect($filename = null)
	{
		$this->_theme->set_layout('redirect');

		$redirect  = \Uri::create(array($this->_radix->shortname));

		if ($this->_radix->archive)
		{
			$redirect  = ($this->_radix->images_url) ? : '//images.4chan.org/'.$this->_radix->shortname.'/src/';
			$redirect .= $filename.'.'.\Input::extension();
		}

		return \Response::forge($this->_theme->build('redirection', array('url' => $redirect)));
	}


	public function radix_advanced_search()
	{
		return $this->action_advanced_search();
	}

	public function action_advanced_search()
	{
		$this->_theme->bind('search_structure', \Search::structure());
		$this->_theme->bind('section_title', __('Advanced search'));

		if ($this->_radix !== null)
		{
			$this->_theme->bind('search', array('board' => array($this->_radix->shortname)));
		}

		return \Response::forge($this->_theme->build('advanced_search'));
	}


	public function action_search()
	{
		return $this->radix_search();
	}


	public function radix_search()
	{
		if (\Input::post('submit_search_global'))
		{
			$this->_radix = null;
		}

		$text = \Input::post('text');

		if ($this->_radix !== null && \Input::post('submit_post'))
		{
			return $this->radix_post(str_replace(',', '_', $text));
		}

		// Check all allowed search modifiers and apply only these
		$modifiers = array(
			'boards', 'subject', 'text', 'username', 'tripcode', 'email', 'filename', 'capcode',
			'image', 'deleted', 'ghost', 'type', 'filter', 'start', 'end',
			'order', 'page');

		if(\Auth::has_access('comment.see_ip'));
		{
			$modifiers[] = 'poster_ip';
			$modifiers[] = 'deletion_mode';
		}

		// GET -> URL Redirection to provide URL presentable for sharing links.
		if (\Input::post())
		{
			if ($this->_radix !== null)
			{
				$redirect_url = array($this->_radix->shortname, 'search');
			}
			else
			{
				$redirect_url = array('_', 'search');
			}

			foreach ($modifiers as $modifier)
			{
				if (\Input::post($modifier))
				{
					if($modifier === 'image')
					{
						array_push($redirect_url, $modifier);
						array_push($redirect_url,
							rawurlencode(\Media::urlsafe_b64encode(\Media::urlsafe_b64decode(\Input::post($modifier)))));
					}
					else if ($modifier === 'boards')
					{
						if (\Input::post('submit_search_global'))
						{

						}
						else if (count(\Input::post($modifier)) == 1)
						{
							$boards = \Input::post($modifier);
							$redirect_url[0] = $boards[0];
						}
						else if (count(\Input::post($modifier)) > 1)
						{
							$redirect_url[0] = '_';

							// avoid setting this if we're just searching on all the boards
							$sphinx_boards = array();
							foreach (\Radix::getAll() as $k => $b)
							{
								if ($b->sphinx)
								{
									$sphinx_boards[] = $b;
								}
							}

							if(count($sphinx_boards) !== count(\Input::post($modifier)))
							{
								array_push($redirect_url, $modifier);
								array_push($redirect_url, rawurlencode(implode('.', \Input::post($modifier))));
							}
						}
					}
					else
					{
						array_push($redirect_url, $modifier);
						array_push($redirect_url, rawurlencode(\Input::post($modifier)));
					}
				}
			}

			\Response::redirect(\Uri::create($redirect_url), 'location', 303);
		}

		$search = \Uri::uri_to_assoc(\Uri::segments(), 2, $modifiers);

		$this->_theme->bind('search', $search);

		// latest searches system
		if( ! is_array($cookie_array = @json_decode(\Cookie::get('search_latest_5'), true)))
		{
			$cookie_array = array();
		}

		// sanitize
		foreach($cookie_array as $item)
		{
			// all subitems must be array, all must have 'board'
			if( ! is_array($item) || ! isset($item['board']))
			{
				$cookie_array = array();
				break;
			}
		}

		$search_opts = array_filter($search);

		$search_opts['board'] = $this->_radix !== null ? $this->_radix->shortname : false;
		unset($search_opts['page']);

		// if it's already in the latest searches, remove the previous entry
		foreach($cookie_array as $key => $item)
		{
			if($item === $search_opts)
			{
				unset($cookie_array[$key]);
				break;
			}
		}

		// we don't want more than 5 entries for latest searches
		if(count($cookie_array) > 4)
		{
			array_pop($cookie_array);
		}

		array_unshift($cookie_array, $search_opts);
		$this->_theme->bind('latest_searches', $cookie_array);
		\Cookie::set('search_latest_5', json_encode($cookie_array), 60 * 60 * 24 * 30);

		foreach ($search as $key => $value)
		{
			if ($value !== null)
			{
				$search[$key] = trim(rawurldecode($value));
			}
		}

		if ($search['boards'] !== null)
		{
			$search['boards'] = explode('.', $search['boards']);
		}

		if ($search['image'] !== null)
		{
			$search['image'] = base64_encode(\Media::urlsafe_b64decode($search['image']));
		}

		if ($search['poster_ip'] !== null)
		{
			if ( ! filter_var($search['poster_ip'], FILTER_VALIDATE_IP))
			{
				return $this->error(__('The poster IP you inserted is not a valid IP address.'));
			}

			$search['poster_ip'] = \Inet::ptod($search['poster_ip']);
		}

		try
		{
			$board = \Search::forge()
				->getSearch($search)
				->setRadix($this->_radix)
				->setPage($search['page'] ? $search['page'] : 1);
			$board->getComments();
		}
		catch (\Foolz\Foolfuuka\Model\SearchException $e)
		{
			return $this->error($e->getMessage());
		}
		catch (\Foolz\Foolfuuka\Model\BoardException $e)
		{
			return $this->error($e->getMessage());
		}

		// Generate the $title with all search modifiers enabled.
		$title = array();

		if ($search['text'])
			array_push($title,
				sprintf(__('that contain &lsquo;%s&rsquo;'),
					e($search['text'])));
		if ($search['subject'])
			array_push($title,
				sprintf(__('with the subject &lsquo;%s&rsquo;'),
					e($search['subject'])));
		if ($search['username'])
			array_push($title,
				sprintf(__('with the username &lsquo;%s&rsquo;'),
					e($search['username'])));
		if ($search['tripcode'])
			array_push($title,
				sprintf(__('with the tripcode &lsquo;%s&rsquo;'),
					e($search['tripcode'])));
		if ($search['filename'])
			array_push($title,
				sprintf(__('with the filename &lsquo;%s&rsquo;'),
					e($search['filename'])));
		if ($search['image'])
		{
			array_push($title,
				sprintf(__('with the image hash &lsquo;%s&rsquo;'),
					e($search['image'])));
		}
		if ($search['deleted'] == 'deleted')
			array_push($title, __('that have been deleted'));
		if ($search['deleted'] == 'not-deleted')
			array_push($title, __('that has not been deleted'));
		if ($search['ghost'] == 'only')
			array_push($title, __('that are by ghosts'));
		if ($search['ghost'] == 'none')
			array_push($title, __('that are not by ghosts'));
		if ($search['type'] == 'op')
			array_push($title, __('that are only OP posts'));
		if ($search['type'] == 'posts')
			array_push($title, __('that are only non-OP posts'));
		if ($search['filter'] == 'image')
			array_push($title, __('that do not contain images'));
		if ($search['filter'] == 'text')
			array_push($title, __('that only contain images'));
		if ($search['capcode'] == 'user')
			array_push($title, __('that were made by users'));
		if ($search['capcode'] == 'mod')
			array_push($title, __('that were made by mods'));
		if ($search['capcode'] == 'admin')
			array_push($title, __('that were made by admins'));
		if ($search['start'])
			array_push($title, sprintf(__('posts after %s'), e($search['start'])));
		if ($search['end'])
			array_push($title, sprintf(__('posts before %s'), e($search['end'])));
		if ($search['order'] == 'asc')
			array_push($title, __('in ascending order'));
		if (!empty($title))
		{
			$title = sprintf(__('Searching for posts %s.'),
				implode(' ' . __('and') . ' ', $title));
		}
		else
		{
			$title = __('Displaying all posts with no filters applied.');
		}

		if ($this->_radix)
		{
			$this->_theme->set_title($title);
		}
		else
		{
			$this->_theme->set_title('Global Search &raquo; '.$title);
		}

		$this->_theme->bind('section_title', $title);
		$this->_theme->bind('board', $board);

		$pagination = $search;
		unset($pagination['page']);
		$pagination_arr = array();
		$pagination_arr[] = $this->_radix !== null ?$this->_radix->shortname : '_';
		$pagination_arr[] = 'search';
		foreach ($pagination as $key => $item)
		{
			if ($item || $item === 0)
			{
				$pagination_arr[] = rawurlencode($key);
				if (is_array($item))
				{
					$item = implode('.', $item);
				}

				if ($key == 'poster_ip')
				{
					$item = \Inet::dtop($item);
				}

				$pagination_arr[] = rawurlencode($item);
			}
		}

		$pagination_arr[] = 'page';
		$this->_theme->bind('pagination', array(
				'base_url' => \Uri::create($pagination_arr),
				'current_page' => $search['page'] ? : 1,
				'total' => floor($board->get_count()/25+1),
			));

		$this->_theme->bind('modifiers', array(
			'post_show_board_name' => $this->_radix === null,
			'post_show_view_button' => true
		));

		\Profiler::mark_memory($this, 'Controller Chan $this');
		\Profiler::mark('Controller Chan::search End');
		return \Response::forge($this->_theme->build('board'));
	}


	public function radix_appeal()
	{
		try
		{
			$bans = \Ban::getByIp(\Input::ip_decimal());
		}
		catch (\Foolz\Foolfuuka\Model\BanException $e)
		{
			return $this->error(__('It doesn\'t look like you\'re banned.'));
		}

		// check for a global ban
		if (isset($bans[0]))
		{
			$title = __('Appealing to a global ban.');
			$ban = $bans[0];
		}
		else if (isset($bans[$this->_radix->id]))
		{
			$title = \Str::tr(__('Appealing to a ban on :board'), array('board' => '/'.$this->_radix->shortname.'/'));
			$ban = $bans[$this->_radix->id];
		}
		else
		{
			return $this->error(__('It doesn\'t look like you\'re banned on this board.'));
		}

		if ($ban->appeal_status == \Ban::APPEAL_PENDING)
		{
			return $this->message('success', __('Your appeal is pending administrator review. Check again later.'));
		}

		if ($ban->appeal_status == \Ban::APPEAL_REJECTED)
		{
			return $this->message('error', __('Your appeal has been rejected.'));
		}

		if(\Input::post('appeal'))
		{
			if ( ! \Security::check_token())
			{
				return $this->error(__('The security token wasn\'t found. Try resubmitting.'));
			}
			else
			{
				$val = \Validation::forge();
				$val->add_field('appeal', __('Appeal'), 'required|trim|min_length[3]|max_length[4096]');

				if($val->run())
				{
					$ban->appeal($val->input('appeal'));
					return $this->message('success', __('Your appeal has been submitted!'));
				}
			}
		}

		return \Response::forge($this->_theme->build('appeal', array('title' => $title)));
	}


	public function radix_submit()
	{
		// adapter
		if( ! \Input::post())
		{
			return $this->error(__('You aren\'t sending the required fields for creating a new message.'));
		}

		if ( ! \Security::check_token())
		{
			if (\Input::is_ajax())
			{
				return \Response::forge(
				json_encode(array('error' => __('The security token wasn\'t found. Try resubmitting.'))));
			}

			return $this->error(__('The security token wasn\'t found. Try resubmitting.'));
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

		if (isset($post['reply_numero']))
			$data['thread_num'] = $post['reply_numero'];
		if (isset($post['reply_bokunonome']))
		{
			$data['name'] = $post['reply_bokunonome'];
			\Cookie::set('reply_name', $data['name'], 60*60*24*30);
		}

		if (isset($post['reply_elitterae']))
		{
			$data['email'] = $post['reply_elitterae'];
			\Cookie::set('reply_email', $data['email'], 60*60*24*30);
		}

		if (isset($post['reply_talkingde']))
		{
			$data['title'] = $post['reply_talkingde'];
		}

		if (isset($post['reply_chennodiscursus']))
		{
			$data['comment'] = $post['reply_chennodiscursus'];
		}

		if (isset($post['reply_nymphassword']))
		{
			$data['delpass'] = $post['reply_nymphassword'];
			\Cookie::set('reply_password', $data['delpass'], 60*60*24*30);
		}

		if (isset($post['reply_gattai_spoilered']))
		{
			$data['spoiler'] = true;
		}

		if (isset($post['reply_postas']))
		{
			$data['capcode'] = $post['reply_postas'];
		}

		if (isset($post['reply_last_limit']))
		{
			$data['last_limit'] = $post['reply_last_limit'];
		}

		if (isset($post['recaptcha_challenge_field']) && isset($post['recaptcha_response_field']))
		{
			$data['recaptcha_challenge'] = $post['recaptcha_challenge_field'];
			$data['recaptcha_response'] = $post['recaptcha_response_field'];
		}


		$media = null;

		if (count(\Upload::get_files()))
		{
			try
			{
				$media = \Media::forgeFromUpload($this->_radix);
				$media->spoiler = isset($data['spoiler']) && $data['spoiler'];
			}
			catch (\Foolz\Foolfuuka\Model\MediaUploadNoFileException $e)
			{
				if (\Input::is_ajax())
				{
					return \Response::forge(json_encode(array('error' => $e->getMessage())));
				}
				else
				{
					return $this->error($e->getMessage());
				}
			}
			catch (\Foolz\Foolfuuka\Model\MediaUploadException $e)
			{
				if (\Input::is_ajax())
				{
					return \Response::forge(json_encode(array('error' => $e->getMessage())));
				}
				else
				{
					return $this->error($e->getMessage());
				}
			}
		}

		return $this->submit($data, $media);
	}

	public function submit($data, $media)
	{
		// some beginners' validation, while through validation will happen in the Comment model
		$val = \Validation::forge();
		$val->add_field('thread_num', __('Thread Number'), 'required');
		$val->add_field('name', __('Username'), 'max_length[64]');
		$val->add_field('email', __('Email'), 'max_length[64]');
		$val->add_field('title', __('Subject'), 'max_length[64]');
		$val->add_field('comment', __('Comment'), 'min_length[3]|max_length[4096]');
		$val->add_field('delpass', __('Password'), 'required|min_length[3]|max_length[32]');

		// leave the capcode check to the model

		// this is for redirecting, not for the database
		$limit = false;
		if (isset($data['last_limit']))
		{
			$limit = intval($data['last_limit']);
			unset($data['last_limit']);
		}

		if($val->run($data))
		{
			try
			{
				$data['poster_ip'] = \Input::ip_decimal();
				$comment = new \Foolz\Foolfuuka\Model\CommentInsert($data, $this->_radix, array('clean' => false));
				$comment->media = $media;
				$comment->insert();
			}
			catch (\Foolz\Foolfuuka\Model\CommentSendingRequestCaptchaException $e)
			{
				if (\Input::is_ajax())
				{
					return \Response::forge(json_encode(array('captcha' => true)));
				}
				else
				{
					return $this->error(__('Your message looked like spam. Make sure you have JavaScript enabled to display the reCAPTCHA to submit the comment.'));
				}
			}
			catch (\Foolz\Foolfuuka\Model\CommentSendingException $e)
			{
				if (\Input::is_ajax())
				{
					return \Response::forge(json_encode(array('error' => $e->getMessage())));
				}
				else
				{
					return $this->error($e->getMessage());
				}
			}
		}
		else
		{
			if (\Input::is_ajax())
			{
				return \Response::forge(json_encode(array('error' => implode(' ', $val->error()))));
			}
			else
			{
				return $this->error(implode(' ', $val->error()));
			}
		}

		if (\Input::is_ajax())
		{
			$latest_doc_id = \Input::post('latest_doc_id');
			if ($latest_doc_id && ctype_digit((string) $latest_doc_id))
			{
				try
				{
					$board = \Board::forge()
						->getThread($comment->thread_num)
						->setRadix($this->_radix)
						->setApi(array('theme' => \Input::post('theme'), 'board' => false))
						->setOptions(array(
							'type' => 'from_doc_id',
							'latest_doc_id' => $latest_doc_id,
							'realtime' => true,
							'controller_method' => $limit ? 'last/'.$limit : 'thread'
					));

					$comments = $board->getComments();
				}
				catch (\Foolz\Foolfuuka\Model\BoardThreadNotFoundException $e)
				{
					return $this->error(__("Thread not found."));
				}
				catch (\Foolz\Foolfuuka\Model\BoardException $e)
				{
					return $this->error(__("Unknown error."));
				}

				return \Response::forge(json_encode(array('success' => __('Message sent.')) + $comments));
			}
			else
			{
				$comment_api = \Comment::forgeForApi($comment, $this->_radix,
					array('board' => false, 'theme' => true), array('controller_method' => $limit ? 'last/'.$limit : 'thread'));
				return \Response::forge(
					json_encode(array(
						'success' => __('Message sent.'),
						'thread_num' => $comment->thread_num,
						$comment->thread_num => array('posts' => array($comment_api)),
				)));
			}
		}
		else
		{
			$this->_theme->set_layout('redirect');
			return \Response::forge($this->_theme->build('redirection',
				array(
					'url' => \Uri::create(array($this->_radix->shortname, ! $limit ? 'thread' : 'last/'.$limit,	$comment->thread_num)).'#'.$comment->num
				)
			));
		}

	}

}
