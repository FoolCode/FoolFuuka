<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Chan extends Public_Controller
{


	function __construct()
	{
		parent::__construct();

		// set these headers here instead of in HTML so w3validator won't ever bother us
		header('X-UA-Compatible: IE=edge,chrome=1');
		header('imagetoolbar: false');
	}

	
	/**
	 * The functions with an underscore prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	function __call($name, $parameters)
	{
		$before = $this->plugins->run_hook('fu_chan_controller_before_' . $name, $parameters);

		if (is_array($before))
		{
			// stop the call if the value returned is FALSE
			if($before['return'] === FALSE)
				return FALSE;
			
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		switch (count($parameters)) {
			case 0:
				$return = $this->{'p_' . $name}();
				break;
			case 1:
				$return = $this->{'p_' . $name}($parameters[0]);
				break;
			case 2:
				$return = $this->{'p_' . $name}($parameters[0], $parameters[1]);
				break;
			case 3:
				$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2]);
				break;
			case 4:
				$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
				break;
			case 5:
				$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
				break;
			default:
				$return = call_user_func_array(array(&$this, 'p_' . $name), $parameters);
			break;
		}


		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = $this->plugins->run_hook('fu_chan_controller_after_' . $name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
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
			return $this->show_404();
		}

		if (!is_natural($num) || $num == 0)
		{
			return $this->show_404();
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

				$this->load->model('report_model', 'report');
				$post = array(
					'board_id' => get_selected_radix()->id,
					'doc_id' => $this->input->post('post'),
					'reason' => $this->input->post('reason')
				);

				$result = $this->report->add($post);

				break;

			default:
				return $this->show_404();
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
		// convert the subdomain of the index page to the default
		if (defined('FOOL_SUBDOMAINS_ENABLED')
			&& empty($params) && strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_DEFAULT) !== 0)
		{
			redirect('@default');
		}

		// we really can't make a board called "search" at the moment
		if ($method == 'search')
		{
			// avoid loading it during this IF
			$this->load->model('post_model', 'post');
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
					$this->load->model('theme_model', 'theme');
					$this->theme->set_layout('chan');
					$this->theme->set_partial('tools_search', 'tools_search');
					$this->theme->set_partial('tools_modal', 'tools_modal');
					$this->theme->bind('is_page', FALSE);
					$this->theme->bind('disable_headers', FALSE);
					$this->theme->bind('is_statistics', FALSE);
					$this->theme->bind('enabled_tools_modal', FALSE);
					$plugin_controller = $this->plugins->get_controller_function($this->uri->rsegment_array());
					return call_user_func_array(array($plugin_controller['plugin'], $plugin_controller['method']), $uri_array);
				}

				// not a plugin and not a board, let's send it higher
				$remap = parent::_remap($method, $params);

				// we want to use the 404 in this class
				if($remap === FALSE)
				{
					/* @todo get rid of these blocks of theme set */
					$this->load->helper('cookie');
					$this->load->helper('number');
					$this->load->model('theme_model', 'theme');
					$this->theme->set_layout('chan');
					$this->theme->set_partial('tools_search', 'tools_search');
					$this->theme->set_partial('tools_modal', 'tools_modal');
					$this->theme->bind('is_page', FALSE);
					$this->theme->bind('disable_headers', FALSE);
					$this->theme->bind('is_statistics', FALSE);
					$this->theme->bind('enabled_tools_modal', FALSE);
					$this->show_404();
				}

				// always return after the parent::_remap() is called
				return FALSE;
			}

			// converts the sub-domain correctly
			if (defined('FOOL_SUBDOMAINS_ENABLED'))
			{
				if($board->archive && strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_ARCHIVE) !== 0)
				{
					redirect('@archive/' . $board->shortname . '/' . implode('/', $params?:array()), 301);
				}

				if(!$board->archive && strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_BOARD) !== 0)
				{
					redirect('@board/' . $board->shortname . '/' . implode('/', $params?:array()), 301);
				}
			}

			// Load some default settings for the board.
			$this->load->model('post_model', 'post');
			$this->theme->bind('board', $board);

			$method = $params[0];
			array_shift($params);
		}

		// Load helpers and libraries and initialize public controller.
		$this->load->helper('cookie');
		$this->load->helper('number');
		$this->load->model('theme_model', 'theme');
		$this->theme->set_layout('chan');

		//PLUGINS: If available, allow plugins to override default functions.
		if ($this->plugins->is_controller_function($this->uri->rsegment_array()))
		{
			$uri_array = $this->uri->segment_array();
			array_shift($uri_array);
			array_shift($uri_array);

			$this->theme->set_partial('tools_search', 'tools_search');
			$this->theme->set_partial('tools_modal', 'tools_modal');
			$this->theme->bind('is_page', FALSE);
			$this->theme->bind('disable_headers', FALSE);
			$this->theme->bind('is_statistics', FALSE);
			$this->theme->bind('enabled_tools_modal', FALSE);

			$plugin_controller = $this->plugins->get_controller_function($this->uri->rsegment_array());
			return call_user_func_array(array($plugin_controller['plugin'], $plugin_controller['method']), $uri_array);
		}

		// trying to access an internal method, should never reach here, but safety is never enough
		if(substr($method, 0, 1) == '_')
		{
			return $this->show_404();
		}

		if (method_exists($this, 'p_' . $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}

		// ERROR: We reached the end of the _remap and failed to return anything.
		return $this->show_404();
	}


	/**
	 * @param array $variables
	 * @param array $partials
	 */
	function _set_parameters($variables = array(), $partials = array(), $backend_vars = array())
	{
		if ((!is_array($variables) || !is_array($partials)) || (empty($variables) && empty($partials)))
		{
			return $this->show_404();
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
				'tools_reply_box',
				'tools_modal',
				'tools_search'
			),				// use MY_Controller to make the plugin system easier to use
			'backend_vars' => MY_Controller::get_backend_vars() // for JSON on bottom of page, from MY_Controller
		);

		// include the board shortname in the backend_vars if it's set
		if (get_selected_radix())
		{
			$default['backend_vars']['board_shortname'] = get_selected_radix()->shortname;

			if($this->tank_auth->is_allowed())
				$default['backend_vars']['mod_url'] = get_selected_radix()->href . 'mod_post_actions/';
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

		if(isset($variables['@modifiers']))
		{
			$this->theme->bind('modifiers', $variables['@modifiers']);
		}

		// merge variables to hold all the JavaScript footer data
		$backend_vars = array_merge($default['backend_vars'], $backend_vars);
		$this->theme->bind('backend_vars', $backend_vars);

		// Set all of the variables and partials into the theme.
		foreach ($variables as $name => $value)
		{
			$this->theme->bind($name, $value);
		}

		foreach ($partials as $view => $params)
		{
			if (is_array($params) || is_object($params))
			{
				$this->theme->bind('enabled_' . $view, TRUE);
				$this->theme->set_partial($view, $view, $params);
			}
			else
			{
				// Enable/Disable Partials
				if ($params == FALSE)
				{
					$this->theme->bind('enabled_' . $view, FALSE);
				}
				else
				{
					$this->theme->bind('enabled_' . $view, TRUE);
				}

				// Set the Partials with information.
				if (is_bool($params))
				{
					$this->theme->set_partial($view, $view);
				}
				else
				{
					$this->theme->set_partial($view, $params);
				}
			}
		}
	}


	/**
	 * Display a simple index page listing all of the boards, the latest posts, the most popular
	 * threads and site statistics.
	 */
	public function p_index()
	{
		/**
		 * Set theme variables required to build the HTML.
		 */
		$this->theme->set_title(get_setting('fs_gen_site_title', FOOL_PREF_GEN_WEBSITE_TITLE));
		$this->_set_parameters(
			array(
				'disable_headers' => TRUE
			)
		);
		$this->theme->build('index');
	}

	/**
	 * Display the error page with some information and suggestion to use the search
	 */
	function p_show_404()
	{
		$this->theme->set_title(get_setting('fs_gen_site_title', FOOL_PREF_GEN_WEBSITE_TITLE));
		// call it as a static method to make it easier for plugins to call 404
		Chan::_set_parameters(
			array(
				'disable_headers' => TRUE,
				'error' => __('Page not found. You can use the search if you were looking for something!')
			),
			array(
				'tools_search' => TRUE
			)
		);
		$this->output->set_status_header(404);
		$this->theme->build('error');
	}

	public function p_rules()
	{
		if(!get_selected_radix()->rules)
		{
			return $this->show_404();
		}

		$this->load->library('Markdown_Parser');

		$this->_set_parameters(
			array(
				'content' => get_selected_radix()->rules
			)
		);

		$this->theme->build('markdown');
	}


	/**
	 * @param int   $page
	 * @param bool  $by_thread
	 * @param array $options
	 */
	public function p_page($page = 1, $by_thread = FALSE, $options = array())
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
				'per_page' => get_selected_radix()->threads_per_page,
				'type' => ($this->input->cookie('foolfuuka_default_theme_by_thread' .
					(get_selected_radix()->archive?'_archive':'_board')) ? 'by_thread' : 'by_post')
			);

		$posts = $this->post->get_latest(get_selected_radix(), $page, $options);

		// Set theme variables required to build the HTML.
		$this->theme->set_title(get_selected_radix()->formatted_title .
			(($page > 1 ) ? ' &raquo; ' . __('Page') . ' ' . $page : ''));
		$this->_set_parameters(
			array(
				'section_title' => (($page > 1) ?
					(($by_thread ? __('Latest by Thread') . ' - ' : '') . __('Page') . ' ' . $page) : NULL),
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
				'tools_reply_box' => TRUE,
				'tools_modal' => TRUE,
				'tools_search' => array('page' => $page)
			)
		);
		$this->theme->build('board');
	}


	public function p_by_thread()
	{
		$this->input->set_cookie(
			'foolfuuka_default_theme_by_thread' . (get_selected_radix()->archive?'_archive':'_board'),
			'1',
			60 * 60 * 24 * 30
		);
		redirect(get_selected_radix()->shortname);
	}


	public function p_by_post()
	{
		$this->input->set_cookie(
			'foolfuuka_default_theme_by_thread' . (get_selected_radix()->archive?'_archive':'_board'),
			'1', '');
		redirect(get_selected_radix()->shortname);
	}


	/**
	 * @param int $page
	 */
	public function p_ghost($page = 1)
	{
		// POST -> GET Redirection to provide URL presentable for sharing links.
		if ($this->input->post())
		{
			redirect(get_selected_radix()->shortname . '/ghost/' . $this->input->post('page'), 'location', 303);
		}

		//  Fetch the latest ghost posts.
		$page = intval($page);
		$posts = $this->post->get_latest(get_selected_radix(), $page,
			array('per_page' => get_selected_radix()->threads_per_page, 'type' => 'ghost'));

		// Set theme variables required to build the HTML.
		$this->theme->set_title(get_selected_radix()->formatted_title .
			(($page > 1 ) ? ' &raquo; ' . __('Ghost Page') . ' ' . $page : ''));
		$this->_set_parameters(
			array(
				'section_title' => (($page > 1) ? __('Ghost Page') . ' ' . $page : NULL),
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
				'tools_reply_box' => TRUE,
				'tools_modal' => TRUE,
				'tools_search' => array('page' => $page)
			)
		);
		$this->theme->build('board');
	}


	/**
	 * Display the last X created threads in a gallery view.
	 */
	public function p_gallery($type = 'by_thread', $page = 1)
	{
		// Disable GALLERY when thumbnails is disabled for normal users.
		if (get_selected_radix()->hide_thumbnails == 1 && !$this->tank_auth->is_allowed())
		{
			return $this->show_404();
		}

		// Fetch the last X created threads to generate the GALLERY.
		$result = $this->post->get_gallery(get_selected_radix(), $page, array('type' => $type));

		// Set theme variables required to build the HTML.
		$this->theme->set_title(get_selected_radix()->formatted_title . ' &raquo; ' . __('Gallery'));

		if ($type == 'by_image')
		{
			$title = __('Gallery: Showing Latest Submitted Images') . ' - ' .
				'<a href="' . site_url(array(get_selected_radix()->shortname, 'gallery', 'by_thread')) . '">' . 
				__('By Thread') . '</a>';
		}
		else
		{
			$title = __('Gallery: Showing Latest Created Threads') . ' - ' .
				'<a href="' . site_url(array(get_selected_radix()->shortname, 'gallery', 'by_image')) . '">' . 
				__('By Image') . '</a>';
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
				'tools_modal' => TRUE,
				'tools_search' => TRUE
			),
			array(
				'threads_data' => $result['threads'],
				'page_function' => 'gallery'
			)
		);
		$this->theme->build('gallery');
	}


	/**
	 * @param int $num
	 * @param int $limit
	 */
	public function p_thread($num = 0, $limit = 0)
	{
		// Check if the $num is a valid integer.
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			return $this->show_404();
		}

		// Fetch the THREAD specified and generate the THREAD.
		$num = intval($num);
		$thread_data = $this->post->get_thread(get_selected_radix(), $num);
		$thread = $thread_data['result'];
		$thread_check = $thread_data['thread_check'];

		// don't throw 404, try looking for such a post
		if (!is_array($thread))
		{
			return $this->post($num);
		}

		// the post references wasn't op but it's a thread for sure
		if (!isset($thread[$num]['op']))
		{
			return $this->post($num);
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

		// check if we can determine if posting is disabled
		$tools_reply_box = TRUE;
		$disable_image_upload = FALSE;

		// no image posting in archive, hide the file input
		if(get_selected_radix()->archive)
		{
			$disable_image_upload = TRUE;
		}

		// in the archive you can only ghostpost, so it's an easy check
		if(get_selected_radix()->archive && get_selected_radix()->disable_ghost)
		{
			$tools_reply_box = FALSE;
		}
		else
		{
			// we're only interested in knowing if we should display the reply box
			if(isset($thread_check['ghost_disabled']) && $thread_check['ghost_disabled'] == TRUE)
				$tools_reply_box = FALSE;

			if(isset($thread_check['disable_image_upload']) && $thread_check['disable_image_upload'] == TRUE)
				$disable_image_upload = TRUE;
		}

		// Set theme variables required to build the HTML.
		$this->theme->set_title(get_selected_radix()->formatted_title . ' &raquo; ' . __('Thread') . ' #' . $num);

		$second_array = array(
			'tools_modal' => TRUE,
			'tools_search' => TRUE
		);

		if($tools_reply_box)
		{
			$second_array['tools_reply_box'] = TRUE;
		}

		$this->_set_parameters(
			array(
				'thread_id' => $num,
				'posts' => $thread,
				'is_thread' => TRUE,
				'disable_image_upload' => $disable_image_upload
			),
			$second_array,
			array(
				'thread_id' => $num,
				'latest_doc_id' => $latest_doc_id,
				'latest_timestamp' => $latest_timestamp,
				'thread_op_data' => $thread[$num]['op']
			)
		);
		$this->theme->build('board');
	}


	/**
	 * @param int $num
	 */
	public function p_last50($num = 0)
	{
		// Check if the $num is a valid integer.
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			return $this->show_404();
		}

		// Fetch the THREAD specified and generate the THREAD.
		$num = intval($num);
		$thread_data = $this->post->get_thread(get_selected_radix(), $num,
			array('type' => 'last_x', 'type_extra' => array('last_limit' => 50)));

		$thread = $thread_data['result'];
		$thread_check = $thread_data['thread_check'];

		if (!is_array($thread))
		{
			return $this->show_404();
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

		// check if we can determine if posting is disabled
		$tools_reply_box = TRUE;
		$disable_image_upload = FALSE;

		// no image posting in archive, hide the file input
		if(get_selected_radix()->archive)
		{
			$disable_image_upload = TRUE;
		}

		// in the archive you can only ghostpost, so it's an easy check
		if(get_selected_radix()->archive && get_selected_radix()->disable_ghost)
		{
			$tools_reply_box = FALSE;
		}
		else
		{
			// we're only interested in knowing if we should display the reply box
			if(isset($thread_check['ghost_disabled']) && $thread_check['ghost_disabled'] == TRUE)
				$tools_reply_box = FALSE;

			if(isset($thread_check['disable_image_upload']) && $thread_check['disable_image_upload'] == TRUE)
				$disable_image_upload = TRUE;
		}

		$second_array = array(
			'tools_modal' => TRUE,
			'tools_search' => TRUE
		);

		if($tools_reply_box)
		{
			$second_array['tools_reply_box'] = TRUE;
		}

		// Set theme variables required to build the HTML.
		$this->theme->set_title(get_selected_radix()->formatted_title .
			' &raquo; ' . __('Thread') . ' #' . $num);
		$this->_set_parameters(
			array(
				'section_title' => __('Showing the last 50 posts for Thread No.') . $num,
				'is_last50' => TRUE,
				'thread_id' => $num,
				'posts' => $thread,
				'disable_image_upload' => $disable_image_upload
			),
			$second_array,
			array(
				'thread_id' => $num,
				'latest_doc_id' => $latest_doc_id
			)
		);
		$this->theme->build('board');
	}


	/**
	 * @param int $num
	 */
	public function p_post($num = 0)
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
				return $this->show_404();
			}

			$num = $post[0];
			$subnum = $post[1];
		}

		if ((!is_natural($num) || !$num > 0) && (!is_natural($subnum) || !$subnum > 0))
		{
			return $this->show_404();
		}

		// Fetch the THREAD specified and generate the THREAD with OP+LAST50.
		$num = intval($num);
		$subnum = intval($subnum);
		$thread = $this->post->get_post_thread(get_selected_radix(), $num, $subnum);

		if ($thread === FALSE)
		{
			return $this->show_404();
		}

		if ($thread->subnum > 0)
		{
			$url = site_url(array(get_selected_radix()->shortname, 'thread', $thread->thread_num)) .
				'#' . $thread->num . '_' . $thread->subnum;
		}
		else if ($thread->thread_num > 0)
		{
			$url = site_url(array(get_selected_radix()->shortname, 'thread', $thread->thread_num)) .
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
	 * As of 2012-05-17, fetching of posts with same media hash is done via search system.
	 * Due to backwards compatibility, this function will still be used for non-urlsafe and urlsafe hashes.
	 */
	public function p_image()
	{
		// support non-urlsafe hash
		$uri = $this->uri->segment_array();
		array_shift($uri);
		array_shift($uri);

		$imploded_uri = rawurldecode(implode('/', $uri));
		if (mb_strlen($imploded_uri) < 22)
		{
			return $this->show_404();
		}

		// obtain actual media hash (non-urlsafe)
		$hash = mb_substr($imploded_uri, 0, 22);
		if (strpos($hash, '/') !== FALSE || strpos($hash, '+') !== FALSE)
		{
			$hash = $this->post->get_media_hash($hash, TRUE);
		}


		// Obtain the PAGE from URI.
		$page = 1;
		if (mb_strlen($imploded_uri) > 28)
		{
			$page = substr($imploded_uri, 28);
		}

		// Fetch the POSTS with same media hash and generate the IMAGEPOSTS.
		$page = intval($page);
		redirect(site_url(array(get_selected_radix()->shortname, 'search', 'image', $hash, 'order', 'desc', 'page', $page)), 'location', 301);
	}


	/**
	 * @param $filename
	 */
	public function p_full_image($filename)
	{
		// Check if $filename is valid.
		if (!in_array(substr($filename, -3), array('gif', 'jpg', 'png')) || !is_natural(substr($filename, 0, 13)))
		{
			return $this->show_404();
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
				$this->theme->set_title(__('Error'));
				$this->_set_parameters(
					array(
						'error' => __('There is no record of the specified image in our database.')
					)
				);
				$this->theme->build('error');
				return FALSE;
			}

			// NOT AVAILABLE ON SERVER
			if ($image['error_type'] == 'not_on_server')
			{
				$this->output->set_status_header('404');
				$this->theme->set_title(get_selected_radix()->formatted_title . ' &raquo; ' . __('Image Pruned'));
				$this->_set_parameters(
					array(
						'section_title' => __('Error 404: The image has been pruned from the server.'),
						'modifiers' => array('post_show_single_post' => TRUE, 'post_show_view_button' => TRUE),
						'posts' => array('posts' => array('posts' => array($image['result'])))
					)
				);
				$this->theme->build('board');
				return FALSE;
			}
		}

		// we reached the end with nothing
		return $this->show_404();
	}


	/**
	 * @param null $image
	 */
	public function p_redirect($image = NULL)
	{
		$this->theme->set_layout('redirect');
		$this->_set_parameters(
			array(
				'url' => get_selected_radix()->images_url . $image
			)
		);
		$this->theme->build('redirection');
	}


	/**
	 * Display all results matching the search modifiers applied.
	 *
	 * @return bool
	 */
	public function p_search()
	{
		$radix = get_selected_radix();

		// just disable the radix to run a global search
		if($this->input->get_post('submit_search_global'))
		{
			$radix = FALSE;
		}

		$this->load->library('form_validation');
		$this->form_validation->set_rules('text', __('Searched text'), 'trim');
		$this->form_validation->run();

		// submit_post forces into $this->post()
		// if submit_undefined we check if it's a natural number or a
		// local or 4chan board
		if ($radix && ($this->input->get_post('submit_post')
			|| ($this->input->get_post('submit_undefined')
				&& (is_post_number($this->input->get_post('text'))
					|| strpos($this->input->get_post('text'), 'http://boards.4chan.org') !== FALSE
					|| strpos($this->input->get_post('text'), site_url()) !== FALSE))))
		{
			if (is_post_number($this->input->get_post('text')))
			{
				$text = str_replace(',', '_', $this->input->get_post('text'));
			}
			else
			{
				$text = $this->input->get_post('text');
			}
			// send it to post() that should take care of weird cases too
			// just return instead of redirect because post() itself redirects
			return $this->post($text);
		}

		// Check all allowed search modifiers and apply them only.
		$modifiers = array(
			'subject', 'text', 'username', 'tripcode', 'email', 'filename', 'capcode',
			'image', 'image_file', 'deleted', 'ghost', 'type', 'filter', 'start', 'end',
			'order', 'page');

		// POST -> GET Redirection to provide URL presentable for sharing links.
		if ($this->input->post() || $this->input->get())
		{
			if ($radix)
			{
				$redirect_url = array('@radix', $radix->shortname, 'search');
			}
			else
			{
				$redirect_url = array('search');
			}

			foreach ($modifiers as $modifier)
			{
				if ($this->input->get_post($modifier))
				{
					// catch special case
					if ($modifier == 'image_file')
					{
						array_push($redirect_url, 'image');
					}
					else
					{
						array_push($redirect_url, $modifier);
					}

					array_push($redirect_url, rawurlencode($this->input->get_post($modifier)));
				}
			}

			redirect(site_url($redirect_url), 'location', 303);
		}

		// put the search options in an associative array
		$search = $this->uri->ruri_to_assoc($radix?2:1, $modifiers);

		// get latest 5 searches for LATEST SEARCHES
		$cookie = $this->input->cookie('foolfuuka_search_latest_5');

		if(!is_array($cookie_array = @json_decode($cookie, TRUE)))
		{
			$cookie_array = array();
		}

		// keep this sanitization in sync with the one in the search view!
		// it's for safety, but avoids display errors if the user does silly things

		// a bit of sanitization
		foreach($cookie_array as $item)
		{
			// all subitems must be array, all must have 'board'
			if(!is_array($item) || !isset($item['board']))
			{
				$cookie_array = array();
				break;
			}
		}

		// get rid of empty search terms
		$search_opts = array_filter($this->uri->ruri_to_assoc($radix?4:3, $modifiers));
		// global search support
		$search_opts['board'] = !$radix?FALSE:$radix->shortname;
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
			array_pop($cookie_array);

		array_unshift($cookie_array, $search_opts);
		$cookie_array_json = json_encode($cookie_array);
		$this->input->set_cookie('foolfuuka_search_latest_5', $cookie_array_json, 60 * 60 * 24 * 30, '', '/');

		// actual search
		$result = $this->post->get_search($radix, $search);

		// Stop! We have reached an error and shouldn't proceed any further!
		if (isset($result['error']))
		{
			$this->theme->set_title(__('Error'));

			if ($radix)
			{
				$this->theme->set_title($radix->formatted_title);
			}

			$this->_set_parameters(
				array(
				'error' => $result['error']
				), array(
				'tools_search' => array('search' => $search, 'latest_searches' => $cookie_array)
				)
			);
			$this->theme->build('error');
			return FALSE;
		}

		// Generate the $title with all search modifiers enabled.
		$title = array();

		if ($search['text'])
			array_push($title,
				sprintf(__('that contain &lsquo;%s&rsquo;'),
					trim(fuuka_htmlescape(urldecode($search['text'])))));
		if ($search['subject'])
			array_push($title,
				sprintf(__('with the subject &lsquo;%s&rsquo;'),
					trim(fuuka_htmlescape(urldecode($search['subject'])))));
		if ($search['username'])
			array_push($title,
				sprintf(__('with the username &lsquo;%s&rsquo;'),
					trim(fuuka_htmlescape(urldecode($search['username'])))));
		if ($search['tripcode'])
			array_push($title,
				sprintf(__('with the tripcode &lsquo;%s&rsquo;'),
					trim(fuuka_htmlescape(urldecode($search['tripcode'])))));
		if ($search['filename'])
			array_push($title,
				sprintf(__('with the filename &lsquo;%s&rsquo;'),
					trim(fuuka_htmlescape(urldecode($search['filename'])))));
		if ($search['image'])
		{
			// non-urlsafe else urlsafe
			if (mb_strlen(urldecode($search['image'])) > 22)
			{
				array_push($title,
					sprintf(__('with the image hash &lsquo;%s&rsquo;'),
						trim(rawurldecode($search['image']))));
			}
			else
			{
				$search['image'] = $this->post->get_media_hash($search['image']);
				array_push($title,
					sprintf(__('with the image hash &lsquo;%s&rsquo;'),
						trim($search['image'])));
			}
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
			array_push($title, sprintf(__('posts after %s'), trim(fuuka_htmlescape($search['start']))));
		if ($search['end'])
			array_push($title, sprintf(__('posts before %s'), trim(fuuka_htmlescape($search['end']))));
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

		$page = (!$search['page'] || !intval($search['page'])) ? 1 : $search['page'];

		// Generate URI for pagination.
		$uri_array = $this->uri->ruri_to_assoc($radix?4:3, $modifiers);

                foreach ($uri_array as $key => $value)
                {
                        if ($uri_array[$key] == "")
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

		// Set theme variables required to build the HTML.
		//	$this->theme->set_title($radix->formatted_title .
		//		' &raquo; ' . $title);

		$this->_set_parameters(
			array(
				'section_title' => $title,
				'@modifiers' => array(
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
				'tools_modal' => TRUE,
				'tools_search' => array('search' => $search, 'latest_searches' => $cookie_array)
			)
		);

		if ($radix)
		{
			$this->theme->set_title($radix->formatted_title);
		}
		else
		{
			$this->theme->set_title(__('Global Search'));
		}

		$this->theme->set_metadata('<meta name="robots" content=""noindex" />');
		$this->theme->build('board');
	}


	/**
	 * @param string $mode
	 * @return bool
	 */
	function p_feeds($mode = 'rss_gallery_50')
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
				return $this->show_404();
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
	public function p_delete($num = 0)
	{
		$this->_map_tools('delete', $num);
	}


	/**
	 * @param int $num
	 */
	public function p_report($num = 0)
	{
		$this->_map_tools('report', $num);
	}


	/**
	 * @return bool
	 */
	public function p_submit()
	{
		// Determine if the invalid post fields are populated by bots.
		if (mb_strlen($this->input->post('name')) > 0
			|| mb_strlen($this->input->post('reply')) > 0
			|| mb_strlen($this->input->post('email')) > 0)
		{
			return $this->show_404();
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
				$this->theme->set_title(__('Error'));
				$this->_set_parameters(
					array(
						'error' => validation_errors()
					), array(
						'tools_modal' => TRUE,
						'tools_search' => TRUE
					)
				);
				$this->theme->build('error');
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
									'error' => __('This thread does not exist.'),
									'success' => '')
								));
						return FALSE;
					}

					$this->theme->set_title(__('Error'));
					$this->_set_parameters(
						array(
							'error' => __('This thread does not exist.')
						), array(
							'tools_modal' => TRUE,
							'tools_search' => TRUE
						)
					);
					$this->theme->build('error');
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

					if (isset($check['invalid_thread']) && $check['invalid_thread'] === TRUE)
					{
						if ($this->input->is_ajax_request())
						{
							$this->output
								->set_content_type('application/json')
								->set_output(json_encode(array('error' => __('This thread does not exist.'), 'success' => '')));
							return FALSE;
						}

						$this->theme->set_title(__('Error'));
						$this->_set_parameters(
							array(
								'error' => __('This thread does not exist.')
							),
							array(
								'tools_modal' => TRUE,
								'tools_search' => TRUE
							)
						);
						$this->theme->build('error');
						return FALSE;
					}

					// check if ghost posting is disabled
					if (isset($check['ghost_disabled']) && $check['ghost_disabled'] == TRUE)
					{
						if ($this->input->is_ajax_request())
						{
							$this->output
								->set_content_type('application/json')
								->set_output(json_encode(array('error' => __('This thread is closed.'), 'success' => '')));
							return FALSE;
						}

						$this->theme->set_title(__('Error'));
						$this->_set_parameters(
							array(
								'error' => __('This thread is closed.')
							),
							array(
								'tools_modal' => TRUE,
								'tools_search' => TRUE
							)
						);
						$this->theme->build('error');
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
						->set_output(json_encode(array('error' => __('You are required to upload an image when posting a new thread.'), 'success' => '')));
					return FALSE;
				}

				$this->theme->set_title(__('Error'));
				$this->_set_parameters(
					array(
						'error' => __('You are required to upload an image when posting a new thread.')
					), array(
						'tools_modal' => TRUE,
						'tools_search' => TRUE
					)
				);
				$this->theme->build('error');
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
						->set_output(json_encode(array('error' => __('You are required to write a comment when no image upload is present.'), 'success' => '')));
					return FALSE;
				}

				$this->theme->set_title(__('Error'));
				$this->_set_parameters(
					array(
						'error' => __('You are required to write a comment when no image upload is present.')
					), array(
						'tools_modal' => TRUE,
						'tools_search' => TRUE
					)
				);
				$this->theme->build('error');
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
						->set_output(json_encode(array('error' => __('The posting of images has been disabled for this thread.'), 'success' => '')));
					return FALSE;
				}

				$this->theme->set_title(__('Error'));
				$this->_set_parameters(
					array(
					'error' => __('The posting of images has been disabled for this thread.')
					), array(
					'tools_modal' => TRUE,
					'tools_search' => TRUE
					)
				);
				$this->theme->build('error');
				return FALSE;
			}

			// Process the IMAGE upload.
			if (isset($_FILES['file_image']) && $_FILES['file_image']['error'] != 4)
			{
				//Initialize the MEDIA CONFIG and load the UPLOAD library.
				$media_config['upload_path'] = 'content/cache/';
				$media_config['allowed_types'] = 'jpg|jpeg|png|gif';
				$media_config['max_size'] = get_selected_radix()->max_image_size_kilobytes;
				$media_config['max_width'] = get_selected_radix()->max_image_size_width;
				$media_config['max_height'] = get_selected_radix()->max_image_size_height;
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

					$this->theme->set_title(__('Error'));
					$this->_set_parameters(
						array(
							'error' => $this->upload->display_errors()
						), array(
							'tools_modal' => TRUE,
							'tools_search' => TRUE
						)
					);
					$this->theme->build('error');
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

				$this->theme->set_title(__('Error'));
				$this->_set_parameters(
					array(
						'error' => $result['error']
					), array(
						'tools_modal' => TRUE,
						'tools_search' => TRUE
					)
				);
				$this->theme->build('error');
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
				if ($result['posted']->thread_num == 0)
				{
					$callback = site_url(array(get_selected_radix()->shortname, 'thread',
						$result['posted']->num)) . '#' . $result['posted']->num;
				}
				else
				{
					$callback = site_url(array(get_selected_radix()->shortname, 'thread',
						$result['posted']->thread_num)) . '#' . $result['posted']->num .
						(($result['posted']->subnum > 0) ? '_' . $result['posted']->subnum : '');
				}

				redirect($callback, 'location', 301);
				return TRUE;
			}
		}
		else
		{
			return $this->show_404();
		}
	}


	function p_mod_post_actions()
	{
		// redirect to the one on MY_Controller since it's a function shared with the admin panel
		parent::mod_post_actions();
	}


}

