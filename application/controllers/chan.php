<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Chan extends Public_Controller
{


	function __construct()
	{
		parent::__construct();
	}


	/**
	 * @param string $level
	 * @return bool|string
	 */
	function _is_valid_allowed_level($level)
	{
		switch ($level)
		{
			case 'admin':
				if ($this->tank_auth->is_admin())
					return 'A';
				break;

			case 'mod':
				if ($this->tank_auth->is_allowed())
					return 'M';
				break;

			case 'user':
				return 'N';
				break;
		}

		return FALSE;
	}


	/**
	 * Remap the legacy $_GET queries to valid URI.
	 */
	function _map_query()
	{
		$url = array();

		if ($this->input->get('task'))
		{
			array_push($url, get_selected_radix()->shortname);

			// PAGE
			if ($this->input->get('task') == 'page')
			{
				if ($this->input->get('page') != '' || $this->input->get('ghost') != '')
				{
					array_push($url, ($this->input->get('ghost') != '') ? 'ghost' : 'page');
					array_push($url, ($this->input->get('page') != '') ? $this->input->get('page') : 1);
				}
			}

			//SEARCH
			if ($this->input->get('task') == 'search' || $this->input->get('task') == 'search2')
			{
				array_push($url, 'search');

				if ($this->input->get('search_text') != '')
				{
					array_push($url, 'text/' . $this->input->get('search_text'));
				}
				if ($this->input->get('search_username') != '')
				{
					array_push($url, 'username/' . $this->input->get('search_username'));
				}
				if ($this->input->get('search_tripcode') != '')
				{
					array_push($url, 'tripcode/', $this->input->get('search_tripcode'));
				}
				if ($this->input->get('search_del') != '')
				{
					array_push($url, 'deleted/'
						. str_replace(
							array('dontcare', 'yes', 'no'), array('', 'deleted', 'not-deleted'), $this->input->get('search_del')
						)
					);
				}
				if ($this->input->get('search_int') != '')
				{
					array_push($url, 'ghost/'
						. str_replace(
							array('dontcare', 'yes', 'no'), array('', 'only', 'none'), $this->input->get('search_int')
						)
					);
				}
				if ($this->input->get('search_ord') != '')
				{
					array_push($url, 'order/'
						. str_replace(array('old', 'new'), array('asc', 'desc'), $this->input->get('search_ord'))
					);
				}
			}
		}

		if (!empty($url))
		{
			redirect(site_url($url), 'location', 301);
		}
	}


	/**
	 * @param null $action
	 * @param int $num
	 * @return bool
	 */
	function _map_tools($action = NULL, $num = 0)
	{
		if (!$this->input->is_ajax_request())
		{
			show_404();
		}

		if (!is_natural($num) || $num == 0)
		{
			show_404();
		}

		switch ($action)
		{
			case 'delete':

				$post = array(
					'doc_id'   => $this->input->post('post'),
					'password' => $this->input->post('password')
				);

				$result = $this->post->delete(get_selected_radix(), $post);

				break;

			case 'report':

				$this->load->model('report');
				$post = array(
					'board_id' => get_selected_radix()->id,
					'doc_id' => $this->input->post('post'),
					'reason' => $this->input->post('reason')
				);

				$result = $this->report->add($post);

				break;

			default:
				show_404();
		}

		if (isset($result['error']))
		{
			$this->output
				->set_output(json_encode(array('status' => 'failed', 'reason' => $result['error'])));
			return FALSE;
		}

		if ((isset($result['success']) && $result['success'] == TRUE) || $result === TRUE)
		{
			$this->output
				->set_output(json_encode(array('status' => 'success')));
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * @param $method
	 * @param array $params
	 * @return mixed|type
	 */
	function _remap($method, $params = array())
	{
		if ($method == 'search')
		{
			$this->load->model('post');
		}
		else if (!empty($params))
		{
			// Determine if $board returns a valid response. If not, recheck the $method and $params.
			if (!($board = $this->radix->set_selected_by_shortname($method)))
			{
				//PLUGINS: If available, allow plugins to override default functions.
				// at this point we still didn't chose a board, and the plugin must not assume that
				if ($this->plugins->is_controller_function($this->uri->rsegment_array()))
				{
					$uri_array = $this->uri->segment_array();
					array_shift($uri_array);

					$this->load->helper('cookie');
					$this->load->helper('number');
					$this->load->library('template');
					$this->template->set_layout('chan');
					$this->template->set_partial('tools_view', 'tools_view');
					$this->template->set_partial('tools_post', 'tools_post');
					$this->template->set('is_page', FALSE);
					$this->template->set('disable_headers', FALSE);
					$this->template->set('is_statistics', FALSE);
					$this->template->set('enabled_tools_post', FALSE);
					$plugin_controller = $this->plugins->get_controller_function($this->uri->rsegment_array());
					return call_user_func_array(array($plugin_controller['plugin'], $plugin_controller['method']), $uri_array);
				}

				// not a plugin and not a board, let's send it higher
				return parent::_remap($method, $params);
			}

			// converts the sub-domain correctly
			if (defined('FOOL_SUBDOMAINS_ENABLE'))
			{
				if($board->archive && strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_ARCHIVE) !== 0)
				{
					redirect('@archive/' . implode('/', $params?:array()), 301);
				}	
					
				if(!$board->archive && strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_BOARD) !== 0)
				{
					redirect('@board/' . implode('/', $params?:array()), 301);
				}
			}

			// Load some default settings for the board.
			$this->load->model('post');
			$this->template->set('board', $board);

			$method = $params[0];
			array_shift($params);
		}

		// Load helpers and libraries and initialize public controller.
		$this->load->helper('cookie');
		$this->load->helper('number');
		$this->load->library('template');
		$this->template->set_layout('chan');

		//PLUGINS: If available, allow plugins to override default functions.

		  if ($this->plugins->is_controller_function($this->uri->rsegment_array()))
		  {
				$uri_array = $this->uri->segment_array();
				array_shift($uri_array);
				array_shift($uri_array);

				$this->template->set_partial('tools_view', 'tools_view');
				$this->template->set_partial('tools_post', 'tools_post');
				$this->template->set('is_page', FALSE);
				$this->template->set('disable_headers', FALSE);
				$this->template->set('is_statistics', FALSE);
				$this->template->set('enabled_tools_post', FALSE);

				$plugin_controller = $this->plugins->get_controller_function($this->uri->rsegment_array());
				return call_user_func_array(array($plugin_controller['plugin'], $plugin_controller['method']), $uri_array);
		  }


		// FUNCTIONS: If available, load custom functions to override default functions.
		if (method_exists($this->TC, $method))
		{
			return call_user_func_array(array($this->TC, $method), $params);
		}

		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}

		// ERROR: We reached the end of the _remap and failed to return anything.
		show_404();
	}


	/**
	 * @param array $variables
	 * @param array $partials
	 */
	function _set_parameters($variables = array(), $partials = array(), $backend_vars = array())
	{
		if ((!is_array($variables) || !is_array($partials)) || (empty($variables) && empty($partials)))
		{
			show_404();
		}

		// Initialize default values for valid
		$default = array(
			'variables' => array(
				'disable_headers',
				'is_page',
				'is_thread',
				'is_last50',
				'is_statistics',
				'@modifiers',
				'order'
			),
			'partials' => array(
				'post_thread',
				'tools_post',
				'tools_view'
			),
			'backend_vars' => array(
				'site_url'  => site_url(),
				'api_url'   => site_url(),
				'csrf_hash' => $this->security->get_csrf_hash()
			)
		);

		// include the board shortname in the backend_vars if it's set
		if (get_selected_radix())
		{
			$default['backend_vars']['board_shortname'] = get_selected_radix()->shortname;
		}

		foreach ($default['variables'] as $k)
		{
			if (!isset($variables[$k]))
			{
				if (strpos($k, '@') === FALSE)
				{
					$variables[$k] = FALSE;
				}
				else
				{
					$variables[substr($k, 1)] = array();
				}
			}
		}

		foreach ($default['partials'] as $k)
		{
			if (!isset($partials[$k]))
			{
				if (strpos($k, '@') === FALSE)
				{
					$partials[$k] = FALSE;
				}
				else
				{
					$partials[substr($k, 1)] = array();
				}
			}
		}

		// merge variables to hold all the JavaScript footer data
		$backend_vars = array_merge($default['backend_vars'], $backend_vars);
		$this->template->set('backend_vars', $backend_vars);

		// Set all of the variables and partials into the template.
		foreach ($variables as $name => $value)
		{
			$this->template->set($name, $value);
		}

		foreach ($partials as $view => $params)
		{
			if (is_array($params) || is_object($params))
			{
				$this->template->set('enabled_' . $view, TRUE);
				$this->template->set_partial($view, $view, $params);
			}
			else
			{
				// Enable/Disable Partials
				if ($params == FALSE)
				{
					$this->template->set('enabled_' . $view, FALSE);
				}
				else
				{
					$this->template->set('enabled_' . $view, TRUE);
				}

				// Set the Partials with information.
				if (is_bool($params))
				{
					$this->template->set_partial($view, $view);
				}
				else
				{
					$this->template->set_partial($view, $params);
				}
			}
		}
	}


	/**
	 * Display a simple index page listing all of the boards, the latest posts, the most popular
	 * threads and site statistics.
	 */
	public function index()
	{
		/**
		 * Set template variables required to build the HTML.
		 */
		$this->template->title('FoOlFuuka &raquo; 4chan Archiver');
		$this->_set_parameters(
			array(
				'disable_headers' => TRUE
			)
		);
		$this->template->build('index');
	}


	/**
	 * @param int   $page
	 * @param bool  $by_thread
	 * @param array $options
	 */
	public function page($page = 1, $by_thread = FALSE, $options = array())
	{
		// POST -> GET Redirection to provide URL presentable for sharing links.
		$this->_map_query();
		if ($this->input->post())
		{
			redirect(get_selected_radix()->shortname . ($by_thread? '/by_thread/' : '/page/') .
				$this->input->post('page'), 'location', 303);
		}

		// Fetch the latest posts.
		$page = intval($page);

		$options = (!empty($options)) ? $options :
			array(
				'per_page' => 24,
				'type' => ($this->input->cookie('foolfuuka_default_theme_by_thread' .
					(get_selected_radix()->archive?'_archive':'_board')) ? 'by_thread' : 'by_post')
			);

		$posts = $this->post->get_latest(get_selected_radix(), $page, $options);

		// Set template variables required to build the HTML.
		$this->template->title(get_selected_radix()->formatted_title .
			(($page > 1 ) ? ' &raquo; ' . _('Page') . ' ' . $page : ''));
		$this->_set_parameters(
			array(
				'section_title' => (($page > 1) ?
					(($by_thread ? _('Latest by Thread') . ' - ' : '') . _('Page') . ' ' . $page) : NULL),
				'is_page' => TRUE,
				'posts' => $posts['result'],
				'posts_per_thread' => 5,
				'pagination' => array(
					'base_url' => site_url(array(get_selected_radix()->shortname, ($by_thread ? 'by_thread' : 'page'))),
					'current_page' => $page,
					'total' => $posts['pages']
				),
				'order' => ($by_thread ? 'by_thread' : 'by_post')
			),
			array(
				'post_thread' => TRUE,
				'tools_post' => TRUE,
				'tools_view' => array('page' => $page)
			)
		);
		$this->template->build('board');
	}


	public function by_thread()
	{
		$this->input->set_cookie(
			'foolfuuka_default_theme_by_thread' . (get_selected_radix()->archive?'_archive':'_board'),
			'1',
			60 * 60 * 24 * 30
		);
		redirect(get_selected_radix()->shortname);
	}


	public function by_post()
	{
		$this->input->set_cookie(
			'foolfuuka_default_theme_by_thread' . (get_selected_radix()->archive?'_archive':'_board'),
			'1', '');
		redirect(get_selected_radix()->shortname);
	}


	/**
	 * @param int $page
	 */
	public function ghost($page = 1)
	{
		// POST -> GET Redirection to provide URL presentable for sharing links.
		if ($this->input->post())
		{
			redirect(get_selected_radix()->shortname . '/ghost/' . $this->input->post('page'), 'location', 303);
		}

		//  Fetch the latest ghost posts.
		$page = intval($page);
		$posts = $this->post->get_latest(get_selected_radix(), $page, array('per_page' => 24, 'type' => 'ghost'));

		// Set template variables required to build the HTML.
		$this->template->title(get_selected_radix()->formatted_title .
			(($page > 1 ) ? ' &raquo; ' . _('Ghost Page') . ' ' . $page : ''));
		$this->_set_parameters(
			array(
				'section_title' => (($page > 1) ? _('Ghost Page') . ' ' . $page : NULL),
				'is_page' => TRUE,
				'posts' => $posts['result'],
				'posts_per_thread' => 5,
				'pagination' => array(
					'base_url' => site_url(array(get_selected_radix()->shortname, 'ghost')),
					'current_page' => $page,
					'total' => $posts['pages']
				)
			),
			array(
				'post_thread' => TRUE,
				'tools_post' => TRUE,
				'tools_view' => array('page' => $page)
			)
		);
		$this->template->build('board');
	}


	/**
	 * Display the last X created threads in a gallery view.
	 */
	public function gallery($type = 'by_thread', $page = 1)
	{
		// Disable GALLERY when thumbnails is disabled for normal users.
		if (get_selected_radix()->hide_thumbnails == 1 && !$this->tank_auth->is_allowed())
		{
			show_404();
		}

		// Fetch the last X created threads to generate the GALLERY.
		$result = $this->post->get_gallery(get_selected_radix(), $page, array('type' => $type));

		// Set template variables required to build the HTML.
		$this->template->title(get_selected_radix()->formatted_title . ' &raquo; ' . _('Gallery'));

		if ($type == 'by_image')
		{
			$title = _('Gallery: Showing Latest Submitted Images') . ' - ' .
				'<a href="' . site_url(array(get_selected_radix()->shortname, 'gallery', 'by_thread')) . '">By Thread</a>';
		}
		else
		{
			$title = _('Gallery: Showing Latest Created Threads') . ' - ' .
				'<a href="' . site_url(array(get_selected_radix()->shortname, 'gallery', 'by_image')) . '">By Image</a>';
		}

		$this->_set_parameters(
			array(
				'section_title' => $title,
				'threads' => $result['threads'],
				'pagination' => array(
					'base_url' => site_url(array(get_selected_radix()->shortname, 'gallery', $type)),
					'current_page' => $page,
					'total' => ceil($result['total_found'] / 25)
				)
			),
			array(
				'tools_post' => TRUE,
				'tools_view' => TRUE
			),
			array(
				'threads_data' => $result['threads'],
				'page_function' => 'gallery'
			)
		);
		$this->template->build('gallery');
	}


	/**
	 * @param int $num
	 * @param int $limit
	 */
	public function thread($num = 0, $limit = 0)
	{
		// Check if the $num is a valid integer.
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		// Fetch the THREAD specified and generate the THREAD.
		$num = intval($num);
		$thread = $this->post->get_thread(get_selected_radix(), $num);

		if (!is_array($thread))
		{
			show_404();
		}

		if (!isset($thread[$num]['op']))
		{
			$this->post($num);
			return TRUE;
		}

		// get the latest doc_id and latest timestamp
		$latest_doc_id = (isset($thread[$num]['op'])) ? $thread[$num]['op']->doc_id : 0;
		$latest_timestamp = (isset($thread[$num]['op'])) ? $thread[$num]['op']->timestamp : 0;
		if (isset($thread[$num]['posts']))
		{
			foreach ($thread[$num]['posts'] as $post)
			{
				if ($latest_doc_id < $post->doc_id)
				{
					$latest_doc_id = $post->doc_id;
				}

				if ($latest_timestamp < $post->timestamp)
				{
					$latest_timestamp = $post->timestamp;
				}
			}
		}

		// Set template variables required to build the HTML.
		$this->template->title(get_selected_radix()->formatted_title . ' &raquo; ' . _('Thread') . ' #' . $num);
		$this->_set_parameters(
			array(
				'thread_id' => $num,
				'posts' => $thread,
				'is_thread' => TRUE
			),
			array(
				'post_thread' => TRUE,
				'tools_post' => TRUE,
				'tools_view' => TRUE
			),
			array(
				'thread_id' => $num,
				'latest_doc_id' => $latest_doc_id,
				'latest_timestamp' => $latest_timestamp,
				'thread_op_data' => $thread[$num]['op']
			)
		);
		$this->template->build('board');
	}


	/**
	 * @param int $num
	 */
	public function last50($num = 0)
	{
		// Check if the $num is a valid integer.
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		// Fetch the THREAD specified and generate the THREAD.
		$num = intval($num);
		$thread = $this->post->get_thread(get_selected_radix(), $num,
			array('type' => 'last_x', 'type_extra' => array('last_limit' => 50)));

		if (!is_array($thread))
		{
			show_404();
		}

		if (!isset($thread[$num]['op']))
		{
			$this->post($num);
			return TRUE;
		}

		// get the latest doc_id
		$latest_doc_id = (isset($thread[$num]['op'])) ? $thread[$num]['op']->doc_id : 0;
		if (isset($thread[$num]['posts']))
		{
			foreach ($thread[$num]['posts'] as $post)
			{
				if ($latest_doc_id < $post->doc_id)
				{
					$latest_doc_id = $post->doc_id;
				}
			}
		}

		// Set template variables required to build the HTML.
		$this->template->title(get_selected_radix()->formatted_title .
			' &raquo; ' . _('Thread') . ' #' . $num);
		$this->_set_parameters(
			array(
				'section_title' => _('Showing the last 50 posts for Thread No.') . $num,
				'is_last50' => TRUE,
				'thread_id' => $num,
				'posts' => $thread
			),
			array(
				'post_thread' => TRUE,
				'tools_post' => TRUE,
				'tools_view' => TRUE
			), array(
				'thread_id' => $num,
				'latest_doc_id' => $latest_doc_id
			)
		);
		$this->template->build('board');
	}


	/**
	 * @param int $num
	 */
	public function post($num = 0)
	{
		// POST -> GET Redirection to provide URL presentable for sharing links.
		if ($this->input->post('post') || !is_post_number($num))
		{
			$num = $this->input->post('post')? : $num;

			preg_match('/(?:^|\/)(\d+)(?:[_,]([0-9]*))?/', $num, $post);
			redirect(get_selected_radix()->shortname . '/post/' .
				(isset($post[1]) ? $post[1] : '') . (isset($post[2]) ? '_' . $post[2] : ''), 'location', 301);
		}

		// Redirect to THREAD if it exists.
		$num = str_replace('S', '', $num);
		$subnum = 0;

		if (strpos($num, '_') > 0)
		{
			$post = explode('_', $num);
			if (count($post) != 2)
			{
				show_404();
			}

			$num = $post[0];
			$subnum = $post[1];
		}

		if ((!is_natural($num) || !$num > 0) && (!is_natural($subnum) || !$subnum > 0))
		{
			show_404();
		}

		// Fetch the THREAD specified and generate the THREAD with OP+LAST50.
		$num = intval($num);
		$subnum = intval($subnum);
		$thread = $this->post->get_post_thread(get_selected_radix(), $num, $subnum);

		if ($thread === FALSE)
		{
			show_404();
		}

		if ($thread->subnum > 0)
		{
			$url = site_url(array(get_selected_radix()->shortname, 'thread', $thread->parent)) .
				'#' . $thread->num . '_' . $thread->subnum;
		}
		else if ($thread->parent > 0)
		{
			$url = site_url(array(get_selected_radix()->shortname, 'thread', $thread->parent)) .
				'#' . $thread->num;
		}
		else
		{
			$url = site_url(array(get_selected_radix()->shortname, 'thread', $thread->num));
		}

		redirect($url, 'location', 301);
	}


	/**
	 * Display all of the posts that contain the MEDIA HASH provided.
	 */
	public function image()
	{
		// Obtain the HASH from URI.
		$uri = $this->uri->segment_array();
		array_shift($uri);
		array_shift($uri);

		$imploded_uri = urldecode(implode('/', $uri));
		if (mb_strlen($imploded_uri) < 22)
		{
			show_404();
		}

		$hash = str_replace(' ', '+', mb_substr($imploded_uri, 0, 22));

		// Obtain the PAGE from URI.
		$page = 1;
		if (mb_strlen($imploded_uri) > 23)
		{
			$page = substr($imploded_uri, 23);
		}

		if ($hash == '' || !is_natural($page))
		{
			show_404();
		}

		// Fetch the POSTS with same media hash and generate the IMAGEPOSTS.
		$page = intval($page);
		$result = $this->post->get_same_media(get_selected_radix(), $hash . '==', $page);

		// Set template variables required to build the HTML.
		$this->template->title(get_selected_radix()->formatted_title . ' &raquo; ' .
			_('Image Hash') . ': ' . base64_encode(urlsafe_b64decode($hash)));
		$this->_set_parameters(
			array(
				'section_title' => _('Search for image posts with the image hash: ') .
					base64_encode(urlsafe_b64decode($hash)),
				'modifiers' => array('post_show_view_button' => TRUE),
				'posts' => $result['posts'],
				'pagination' => array(
					'base_url' => site_url(array(get_selected_radix()->shortname, 'image', $hash)),
					'current_page' => $page,
					'total' => ceil($result['total_found'] / 25)
				)
			), array(
				'tools_post' => TRUE,
				'tools_view' => TRUE
			)
		);
		$this->template->build('board');
	}


	/**
	 * @param $filename
	 */
	public function full_image($filename)
	{
		// Check if $filename is valid.
		if (!in_array(substr($filename, -3), array('gif', 'jpg', 'png')) || !is_natural(substr($filename, 0, 13)))
		{
			show_404();
		}

		// Fetch the FULL IMAGE with the FILENAME specified.
		$image = $this->post->get_full_media(get_selected_radix(), $filename);

		if (isset($image['media_link']))
		{
			redirect($image['media_link'], 'location', 303);
		}

		if (isset($image['error_type']))
		{
			// NOT FOUND, INVALID MEDIA HASH
			if ($image['error_type'] == 'no_record')
			{
				$this->output->set_status_header('404');
				$this->template->title(_('Error'));
				$this->_set_parameters(
					array(
						'error' => _('There is no record of the specified image in our database.')
					)
				);
				$this->template->build('error');
			}

			// NOT AVAILABLE ON SERVER
			if ($image['error_type'] == 'not_on_server')
			{
				$this->output->set_status_header('404');
				$this->template->title(get_selected_radix()->formatted_title . ' &raquo; ' . _('Image Pruned'));
				$this->_set_parameters(
					array(
						'section_title' => _('Error 404: The image has been pruned from the server.'),
						'modifiers' => array('post_show_single_post' => TRUE, 'post_show_view_button' => TRUE),
						'posts' => array('posts' => array('posts' => array($image['result'])))
					)
				);
				$this->template->build('board');
			}
		}

		// we reached the end with nothing
		show_404();
	}


	/**
	 * @param null $image
	 */
	public function redirect($image = NULL)
	{
		$this->template->set_layout('redirect');
		$this->_set_parameters(
			array(
				'url' => get_selected_radix()->images_url . $image
			)
		);
		$this->template->build('redirection');
	}


	/**
	 * Display all results matching the search modifiers applied.
	 *
	 * @return bool
	 */
	public function search()
	{
		$radix = get_selected_radix();

		// just disable the radix to run a global search
		if($this->input->post('submit_search_global'))
		{
			$radix = FALSE;
		}

		if ($this->input->post('submit_image') && $radix)
		{
			if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0)
			{
				if (false && $_FILES["image"]["size"] > 8000000)
				{
					$this->template->title(_('Error'));
					$this->template->title($radix->formatted_title);
					$this->template->append_metadata('<meta name="robots" content=""noindex" />');
					$this->_set_parameters(
						array(
							'error' => _('You uploaded a too big file. The maximum file size is 8 MegaBytes.')
						)
					);
					$this->template->build('error');
					return FALSE;
				}

				$md5 = base64_encode(pack("H*", md5(file_get_contents($_FILES['image']['tmp_name']))));
				$md5 = substr(urlsafe_b64encode(urlsafe_b64decode($md5)), 0, -2);
				redirect($radix->shortname . '/image/' . $md5);
			}
			else
			{
				$this->template->title(_('Error'));
				$this->template->title($radix->formatted_title);
				$this->template->append_metadata('<meta name="robots" content=""noindex" />');
				$this->_set_parameters(
					array(
						'error' => _('You seem not to have uploaded a valid file.')
					)
				);
				$this->template->build('error');
				return FALSE;
			}
		}

		$this->load->library('form_validation');
		$this->form_validation->set_rules('text', _('Searched text'), 'trim');
		$this->form_validation->run();

		// submit_post forces into $this->post()
		// if submit_undefined we check if it's a natural number or a
		// local or 4chan board
		if ($this->input->post('submit_post')
			|| ($this->input->post('submit_undefined')
				&& (is_post_number($this->input->post('text'))
					|| strpos($this->input->post('text'), 'http://boards.4chan.org') !== FALSE
					|| strpos($this->input->post('text'), site_url()) !== FALSE)))
		{
			if (is_post_number($this->input->post('text')))
			{
				$text = str_replace(',', '_', $this->input->post('text'));
			}
			else
			{
				$text = $this->input->post('text');
			}
			// send it to post() that should take care of weird cases too
			// just return instead of redirect because post() itself redirects
			return $this->post($text);
		}

		// Check all allowed search modifiers and apply them only.
		$modifiers = array(
			'subject', 'text', 'username', 'tripcode', 'email', 'filename', 'capcode',
			'deleted', 'ghost', 'type', 'filter', 'start', 'end',
			'order', 'page');

		// POST -> GET Redirection to provide URL presentable for sharing links.
		if ($this->input->post())
		{
			if ($radix)
			{
				$redirect_url = array($radix->shortname, 'search');
			}
			else
			{
				$redirect_url = array('search');
			}

			foreach ($modifiers as $modifier)
			{
				if ($this->input->post($modifier))
				{
					array_push($redirect_url, $modifier);
					array_push($redirect_url, rawurlencode($this->input->post($modifier)));
				}
			}

			redirect(site_url($redirect_url), 'location', 303);
		}

		// Fetch the search results and display them.
		$search = $this->uri->ruri_to_assoc($radix?2:1, $modifiers);
		$result = $this->post->get_search($radix, $search);

		// Stop! We have reached an error and shouldn't proceed any further!
		if (isset($result['error']))
		{
			$this->template->title(_('Error'));

			if ($radix)
			{
				$this->template->title($radix->formatted_title);
			}

			$this->_set_parameters(
				array(
				'error' => $result['error']
				), array(
				'tools_view' => array('search' => $search)
				)
			);
			$this->template->build('error');
			return FALSE;
		}

		// Generate the $title with all search modifiers enabled.
		$title = array();

		if ($search['text'])
			array_push($title,
				sprintf(_('that contain "%s"'), trim(fuuka_htmlescape($search['text']))));
		if ($search['subject'])
			array_push($title,
				sprintf(_('with the subject "%s"'),
					trim(fuuka_htmlescape($search['subject']))));
		if ($search['username'])
			array_push($title,
				sprintf(_('with the username "%s"'),
					trim(fuuka_htmlescape($search['username']))));
		if ($search['tripcode'])
			array_push($title,
				sprintf(_('with the tripcode "%s"'),
					trim(fuuka_htmlescape($search['tripcode']))));
		if ($search['filename'])
			array_push($title,
				sprintf(_('with the filename "%s"'),
					trim(fuuka_htmlescape($search['filename']))));
		if ($search['deleted'] == 'deleted')
			array_push($title, _('that have been deleted'));
		if ($search['deleted'] == 'not-deleted')
			array_push($title, _('that has not been deleted'));
		if ($search['ghost'] == 'only')
			array_push($title, _('that are by ghosts'));
		if ($search['ghost'] == 'none')
			array_push($title, _('that are not by ghosts'));
		if ($search['type'] == 'op')
			array_push($title, _('that are only OP posts'));
		if ($search['type'] == 'posts')
			array_push($title, _('that are only non-OP posts'));
		if ($search['filter'] == 'image')
			array_push($title, _('that do not contain images'));
		if ($search['filter'] == 'text')
			array_push($title, _('that only contain images'));
		if ($search['capcode'] == 'user')
			array_push($title, _('that were made by users'));
		if ($search['capcode'] == 'mod')
			array_push($title, _('that were made by mods'));
		if ($search['capcode'] == 'admin')
			array_push($title, _('that were made by admins'));
		if ($search['start'])
			array_push($title, sprintf(_('posts after %s'), $search['start']));
		if ($search['end'])
			array_push($title, sprintf(_('posts before %s'), $search['end']));
		if ($search['order'] == 'asc')
			array_push($title, _('in ascending order'));
		if (!empty($title))
		{
			$title = sprintf(_('Searching for posts %s.'),
				urldecode(implode(' ' . _('and') . ' ', $title)));
		}
		else
		{
			$title = _('Displaying all posts with no filters applied.');
		}

		$page = (!$search['page'] || !intval($search['page'])) ? 1 : $search['page'];

		// Generate URI for pagination.
		$uri_array = $this->uri->ruri_to_assoc($radix?4:3, $modifiers);
		foreach ($uri_array as $key => $param)
		{
			if (!$param)
			{
				unset($uri_array[$key]);
			}
		}

		if (isset($uri_array['page']))
		{
			unset($uri_array['page']);
		}

		// we need to add the shortname and the search
		$prepend_uri = (($radix) ? $radix->shortname : '') . '/search';

		// Set template variables required to build the HTML.
		//	$this->template->title($radix->formatted_title .
		//		' &raquo; ' . $title);
		$this->_set_parameters(
			array(
				'section_title' => $title,
				'modifiers' => array(
					'post_show_view_button' => TRUE,
					'post_show_board_name' => !$radix
				),
				'posts' => $result['posts'],
				'pagination' => array(
					'base_url' => site_url($prepend_uri . '/' . $this->uri->assoc_to_uri($uri_array). '/page'),
					'current_page' => $page,
					'total' => ceil((($result['total_found'] > 5000) ? 5000 : $result['total_found']) / 25)
				)
			),
			array(
				'tools_post' => TRUE,
				'tools_view' => array('search' => $search)
			)
		);

		if ($radix)
		{
			$this->template->title($radix->formatted_title);
		}
		else
		{
			$this->template->title(_('Global Search'));
		}

		$this->template->append_metadata('<meta name="robots" content=""noindex" />');
		$this->template->build('board');
	}


	/**
	 * @param null $report
	 */
	public function statistics($report = NULL)
	{
		// Load Statistics Model
		$this->load->model('statistics');

		if (is_null($report))
		{
			$stats = $this->statistics->get_available_stats();

			// Set template variables required to build the HTML.
			$this->template->title(get_selected_radix()->formatted_title . ' &raquo; ' . _('Statistics'));
			$this->_set_parameters(
				array(
					'section_title' => _('Statistics'),
					'is_statistics' => TRUE,
					'is_statistics_list' => TRUE,
					'info' => $stats
				), array(
					'tools_view' => TRUE
				)
			);
			$this->template->build('statistics');
		}
		else
		{
			$stats = $this->statistics->check_available_stats($report, get_selected_radix());

			if (!is_array($stats))
				show_404();

			// Set template variables required to build the HTML.
			$this->load->helper('date');
			$this->template->title(get_selected_radix()->formatted_title . ' &raquo; '
				. _('Statistics') . ': ' . $stats['info']['name']);

			if (isset($stats['info']['frequency']))
			{
				$section_title = sprintf(_('Statistics: %s (Next Update in %s)'),
					$stats['info']['name'],
					timespan($stats['info']['frequency'] + strtotime($stats['timestamp']))
				);
			}
			else
			{
				$section_title = sprintf(_('Statistics: %s'), $stats['info']['name']);
			}

			$this->_set_parameters(
				array(
					'section_title' => $section_title,
					'is_statistics' => TRUE,
					'is_statistics_list' => FALSE,
					'info' => $stats['info'],
					'data' => $stats['data']
				),
				array(
					'stats_interface' => 'statistics/' . $stats['info']['interface'],
					'tools_view' => TRUE
				)
			);
			$this->template->build('statistics');
		}
	}


	/**
	 * @param string $mode
	 * @return bool
	 */
	function feeds($mode = 'rss_gallery_50')
	{
		//if (is_null($format))
		//	redirect('reader/feeds/rss');
		$this->load->helper('xml');

		if (substr($mode, 0, 4) == 'atom')
		{
			$format = 'atom';
			$mode = substr($mode, 5);
		}
		else
		{
			$format = 'rss';
			$mode = substr($mode, 4);
		}

		switch ($mode)
		{
			case 'gallery_50':
				// returns last 200 threads with the thread number as key
				$threads = array_slice($this->post->get_gallery(get_selected_radix()), 0, 50);
				if (count($threads) > 0)
				{
					// let's create a pretty array of chapters [comic][chapter][teams]
					$result['threads'] = array();
					$key = 0;
					foreach ($threads['threads'] as $num => $thread)
					{
						$result['threads'][$key]['title'] = $thread->title_processed;
						$result['threads'][$key]['thumb'] = $thread->thumb_link;
						$result['threads'][$key]['href'] = site_url(array(get_selected_radix()->shortname, 'thread', $thread->num));
						$result['threads'][$key]['created'] = $thread->timestamp;
						$key++;
					}
				}
				break;


			default:
				show_404();
		}

		$data['encoding'] = 'utf-8';
		$data['feed_name'] = get_setting('fs_gen_site_title');
		$data['feed_url'] = site_url('feeds/rss');
		$data['page_description'] = get_setting('fs_gen_site_title') . ' RSS feed';
		$data['page_language'] = get_setting('fs_gen_lang') ? get_setting('fs_gen_lang') : 'en_EN';
		$data['posts'] = $result;
		if ($format == "atom")
		{
			header("Content-Type: application/atom+xml");
			$this->load->view('atom', $data);
			return TRUE;
		}
		header("Content-Type: application/rss+xml");
		$this->load->view('rss', $data);
	}


	/**
	 * @param int $num
	 */
	public function delete($num = 0)
	{
		$this->_map_tools('delete', $num);
	}


	/**
	 * @param int $num
	 */
	public function report($num = 0)
	{
		$this->_map_tools('report', $num);
	}


	/**
	 * @return bool
	 */
	public function submit()
	{
		// Determine if the invalid post fields are populated by bots.
		if (mb_strlen($this->input->post('name')) > 0
			|| mb_strlen($this->input->post('reply')) > 0
			|| mb_strlen($this->input->post('email')) > 0)
		{
			show_404();
		}

		// The form has been submitted to be validated and processed.
		if ($this->input->post('reply_gattai') || $this->input->post('reply_gattai_spoilered'))
		{
			// Validate Form!
			$this->load->library('form_validation');

			$this->form_validation->set_rules('reply_numero', 'Thread no.',
				'required|is_natural|xss_clean');
			$this->form_validation->set_rules('reply_bokunonome', 'Username',
				'trim|xss_clean|max_length[64]');
			$this->form_validation->set_rules('reply_elitterae', 'Email',
				'trim|xss_clean|max_length[64]');
			$this->form_validation->set_rules('reply_talkingde', 'Subject',
				'trim|xss_clean|max_length[64]');
			$this->form_validation->set_rules('reply_chennodiscursus', 'Comment',
				'trim|min_length[3]|max_length[4096]|xss_clean');
			$this->form_validation->set_rules('reply_nymphassword', 'Password',
				'required|min_length[3]|max_length[32]|xss_clean');

			// Verify if the user posting is a moderator or administrator and apply form validation.
			if ($this->tank_auth->is_allowed())
			{
				$this->form_validation->set_rules('reply_postas', 'Post as',
					'required|callback__is_valid_allowed_level|xss_clean');
				$this->form_validation->set_message('_is_valid_allowed_level',
					'You did not specify a valid user level to post as.');
			}

			// The validation of the form has failed! All errors will be formatted here for readability.
			if ($this->form_validation->run() == FALSE)
			{
				$this->form_validation->set_error_delimiters('', '');

				// Display a JSON output for AJAX REQUESTS.
				if ($this->input->is_ajax_request())
				{
					$this->output
						->set_content_type('application/json')
						->set_output(json_encode(array('error' => validation_errors(), 'success' => '')));
					return FALSE;
				}

				// Display a default/standard output for NON-AJAX REQUESTS.
				$this->template->title(_('Error'));
				$this->_set_parameters(
					array(
						'error' => validation_errors()
					), array(
						'tools_post' => TRUE,
						'tools_view' => TRUE
					)
				);
				$this->template->build('error');
				return FALSE;
			}

			// Everything is GOOD! Continue with posting the content to the board.
			$data = array(
				'num' => $this->input->post('reply_numero'),
				'name' => $this->input->post('reply_bokunonome'),
				'email' => $this->input->post('reply_elitterae'),
				'subject' => $this->input->post('reply_talkingde'),
				'comment' => $this->input->post('reply_chennodiscursus'),
				'spoiler' => $this->input->post('reply_gattai_spoilered') ? 1 : $this->input->post('reply_spoiler'),
				'password' => $this->input->post('reply_nymphassword'),
				'postas' => (($this->tank_auth->is_allowed()) ? $this->input->post('reply_postas') : 'N'),
				'media' => '',
				'ghost' => FALSE
			);

			//CHECK #1: Verify the TYPE of POST passing through and insert the data correctly.
			if (get_selected_radix()->archive)
			{
				// This POST is located in the ARCHIVE and MUST BE A GHOST POST.
				$data['ghost'] = TRUE;

				// Check the $num to ensure that the thread actually exists in the database and that
				// $num is actually the OP of the thread.
				$check = $this->post->check_thread(get_selected_radix(), $data['num']);

				if (isset($check['invalid_thread']) && $check['invalid_thread'] == TRUE)
				{
					if ($this->input->is_ajax_request())
					{
						$this->output
							->set_content_type('application/json')
							->set_output(json_encode(array(
									'error' => _('This thread does not exist.'),
									'success' => '')
								));
						return FALSE;
					}

					$this->template->title(_('Error'));
					$this->_set_parameters(
						array(
							'error' => _('This thread does not exist.')
						), array(
							'tools_post' => TRUE,
							'tools_view' => TRUE
						)
					);
					$this->template->build('error');
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
					$check = $this->post->check_thread(get_selected_radix(), $data['num']);

					if (isset($check['invalid_thread']) && $check['invalid_thread'] == TRUE)
					{
						if ($this->input->is_ajax_request())
						{
							$this->output
								->set_content_type('application/json')
								->set_output(json_encode(array('error' => _('This thread does not exist.'), 'success' => '')));
							return FALSE;
						}

						$this->template->title(_('Error'));
						$this->_set_parameters(
							array(
								'error' => _('This thread does not exist.')
							),
							array(
								'tools_post' => TRUE,
								'tools_view' => TRUE
							)
						);
						$this->template->build('error');
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
				if ($this->input->is_ajax_request())
				{
					$this->output
						->set_content_type('application/json')
						->set_output(json_encode(array('error' => _('You are required to upload an image when posting a new thread.'), 'success' => '')));
					return FALSE;
				}

				$this->template->title(_('Error'));
				$this->_set_parameters(
					array(
						'error' => _('You are required to upload an image when posting a new thread.')
					), array(
						'tools_post' => TRUE,
						'tools_view' => TRUE
					)
				);
				$this->template->build('error');
				return FALSE;
			}

			// Check if the comment textarea is EMPTY when no image is uploaded.
			if (mb_strlen($data['comment']) < 3
				&& (!isset($_FILES['file_image']) || $_FILES['file_image']['error'] == 4))
			{
				if ($this->input->is_ajax_request())
				{
					$this->output
						->set_content_type('application/json')
						->set_output(json_encode(array('error' => _('You are required to write a comment when no image upload is present.'), 'success' => '')));
					return FALSE;
				}

				$this->template->title(_('Error'));
				$this->_set_parameters(
					array(
						'error' => _('You are required to write a comment when no image upload is present.')
					), array(
						'tools_post' => TRUE,
						'tools_view' => TRUE
					)
				);
				$this->template->build('error');
				return FALSE;
			}

			// Check if the IMAGE LIMIT has been reached or if we are posting as a GHOST.
			if ((isset($check['disable_image_upload']) || $data['ghost'])
				&& (isset($_FILES['file_image']) && $_FILES['file_image']['error'] != 4))
			{
				if ($this->input->is_ajax_request())
				{
					$this->output
						->set_content_type('application/json')
						->set_output(json_encode(array('error' => _('The posting of images has been disabled for this thread.'), 'success' => '')));
					return FALSE;
				}

				$this->template->title(_('Error'));
				$this->_set_parameters(
					array(
					'error' => _('The posting of images has been disabled for this thread.')
					), array(
					'tools_post' => TRUE,
					'tools_view' => TRUE
					)
				);
				$this->template->build('error');
				return FALSE;
			}

			// Process the IMAGE upload.
			if (isset($_FILES['file_image']) && $_FILES['file_image']['error'] != 4)
			{
				//Initialize the MEDIA CONFIG and load the UPLOAD library.
				$media_config['upload_path'] = 'content/cache/';
				$media_config['allowed_types'] = 'jpg|jpeg|png|gif';
				$media_config['max_size'] = 3072;
				$media_config['max_width'] = 5000;
				$media_config['max_height'] = 5000;
				$media_config['overwrite'] = TRUE;

				$this->load->library('upload', $media_config);

				if ($this->upload->do_upload('file_image'))
				{
					$data['media'] = $this->upload->data();
				}
				else
				{
					if ($this->input->is_ajax_request())
					{
						$this->output
							->set_content_type('application/json')
							->set_output(json_encode(array('error' => $this->upload->display_errors(), 'success' => '')));
						return FALSE;
					}

					$this->template->title(_('Error'));
					$this->_set_parameters(
						array(
							'error' => $this->upload->display_errors()
						), array(
							'tools_post' => TRUE,
							'tools_view' => TRUE
						)
					);
					$this->template->build('error');
					return FALSE;
				}
			}

			// SEND: Process the entire post and insert the information appropriately.
			$result = $this->post->comment(get_selected_radix(), $data);

			// RESULT: Output all errors, messages, etc.
			if (isset($result['error']))
			{
				if ($this->input->is_ajax_request())
				{
					$this->output
						->set_content_type('application/json')
						->set_output(json_encode(array('error' => $result['error'], 'success' => '')));
					return FALSE;
				}

				$this->template->title(_('Error'));
				$this->_set_parameters(
					array(
						'error' => $result['error']
					), array(
						'tools_post' => TRUE,
						'tools_view' => TRUE
					)
				);
				$this->template->build('error');
				return FALSE;
			}
			else if (isset($result['success']))
			{
				if ($this->input->is_ajax_request())
				{
					$this->output
						->set_content_type('application/json')
						->set_output(json_encode(array('error' => '', 'success' => 'Your comment has been posted.')));
					return TRUE;
				}

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

				redirect($callback, 'location', 301);
				return TRUE;
			}
		}
		else
		{
			show_404();
		}
	}


}

