<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class REST_Controller extends MY_Controller
{

	protected $rest_format = NULL; // Set this in a controller to use a default format
	protected $methods = array(); // contains a list of method properties such as limit, log and level
	protected $request = NULL; // Stores accept, language, body, headers, etc
	protected $response = NULL; // What is gonna happen in output?
	protected $rest = NULL; // Stores DB, keys, key level, etc
	protected $_get_args = array();
	protected $_post_args = array();
	protected $_put_args = array();
	protected $_delete_args = array();
	protected $_args = array();
	protected $_allow = TRUE;
	// List all supported methods, the first will be the default format
	protected $_supported_formats = array(
		'xml' => 'application/xml',
		'rawxml' => 'application/xml',
		'json' => 'application/json',
		'jsonp' => 'application/javascript',
		'serialize' => 'application/vnd.php.serialized',
		'php' => 'text/plain',
		'html' => 'text/html',
		'csv' => 'application/csv'
	);

	// Constructor function
	public function __construct()
	{
		parent::__construct();

		// if this is a load balancer FoOlSlide, disable the public interface
		if (get_setting('fs_balancer_master_url'))
		{
			show_404();
		}

		// Lets grab the config and get ready to party
		$this->load->config('rest');

		// How is this request being made? POST, DELETE, GET, PUT?
		$this->request->method = $this->_detect_method();

		// Set up our GET variables
		$this->_get_args = array_merge($this->_get_args, $this->uri->ruri_to_assoc());

		$this->load->library('security');

		// This library is bundled with REST_Controller 2.5+, but will eventually be part of CodeIgniter itself
		$this->load->library('format');

		// Try to find a format for the request (means we have a request body)
		$this->request->format = $this->_detect_input_format();

		// Some Methods cant have a body
		$this->request->body = NULL;

		switch ($this->request->method)
		{
			case 'get':
				// Grab proper GET variables
				/**
				 *  @author: Woxxy
				 */
				if (!$this->input->is_cli_request())
					parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $get);

				// If there are any, populate $this->_get_args
				empty($get) OR $this->_get_args = $get;
				break;

			case 'post':
				$this->_post_args = $_POST;

				$this->request->format and $this->request->body = file_get_contents('php://input');
				break;

			case 'put':
				// It might be a HTTP body
				if ($this->request->format)
				{
					$this->request->body = file_get_contents('php://input');
				}

				// If no file type is provided, this is probably just arguments
				else
				{
					parse_str(file_get_contents('php://input'), $this->_put_args);
				}

				break;

			case 'delete':
				// Set up out DELETE variables (which shouldn't really exist, but sssh!)
				parse_str(file_get_contents('php://input'), $this->_delete_args);
				break;
		}

		// Now we know all about our request, let's try and parse the body if it exists
		if ($this->request->format and $this->request->body)
		{
			$this->request->body = $this->format->factory($this->request->body, $this->request->format)->to_array();
		}

		// Merge both for one mega-args variable
		$this->_args = array_merge($this->_get_args, $this->_put_args, $this->_post_args, $this->_delete_args);

		// Which format should the data be returned in?
		$this->response->format = $this->_detect_output_format();

		// Which format should the data be returned in?
		$this->response->lang = $this->_detect_lang();

		// Check if there is a specific auth type for the current class/method
		$this->auth_override = $this->_auth_override_check();

		// When there is no specific override for the current class/method, use the default auth value set in the config
		if ($this->auth_override !== TRUE)
		{
			if ($this->config->item('rest_auth') == 'basic')
			{
				$this->_prepare_basic_auth();
			}
			elseif ($this->config->item('rest_auth') == 'digest')
			{
				$this->_prepare_digest_auth();
			}
			elseif ($this->config->item('rest_auth') == 'oauth')
			{
				$this->_prepare_oauth();
			}
		}

		// Load DB if its enabled
		if (config_item('rest_database_group') AND (config_item('rest_enable_keys') OR config_item('rest_enable_logging')))
		{
			$this->rest->db = $this->load->database(config_item('rest_database_group'), TRUE);
		}

		// Checking for keys? GET TO WORK!
		if (config_item('rest_enable_keys'))
		{
			$this->_allow = $this->_detect_api_key();
		}

		// only allow ajax requests
		if (!$this->input->is_ajax_request() AND config_item('rest_ajax_only'))
		{
			$this->response(array('status' => false, 'error' => 'Only AJAX requests are accepted.'), 505);
		}
	}


	/*
	 * Remap
	 *
	 * Requests are not made to methods directly The request will be for an "object".
	 * this simply maps the object and method to the correct Controller method.
	 */
	public function _remap($object_called, $arguments)
	{
		$pattern = '/^(.*)\.(' . implode('|', array_keys($this->_supported_formats)) . ')$/';
		if (preg_match($pattern, $object_called, $matches))
		{
			$object_called = $matches[1];
		}

		$controller_method = $object_called . '_' . $this->request->method;

		// Do we want to log this method (if allowed by config)?
		$log_method = !(isset($this->methods[$controller_method]['log']) AND $this->methods[$controller_method]['log'] == FALSE);

		// Use keys for this method?
		$use_key = !(isset($this->methods[$controller_method]['key']) AND $this->methods[$controller_method]['key'] == FALSE);

		// Get that useless shitty key out of here
		if (config_item('rest_enable_keys') AND $use_key AND $this->_allow === FALSE)
		{
			$this->response(array('status' => false, 'error' => 'Invalid API Key.'), 403);
		}

		// Sure it exists, but can they do anything with it?
		if (!method_exists($this, $controller_method))
		{
			$this->response(array('status' => false, 'error' => 'Unknown method.'), 404);
		}

		// Doing key related stuff? Can only do it if they have a key right?
		if (config_item('rest_enable_keys') AND !empty($this->rest->key))
		{
			// Check the limit
			if (config_item('rest_enable_limits') AND !$this->_check_limit($controller_method))
			{
				$this->response(array('status' => false, 'error' => 'This API key has reached the hourly limit for this method.'), 401);
			}

			// If no level is set use 0, they probably aren't using permissions
			$level = isset($this->methods[$controller_method]['level']) ? $this->methods[$controller_method]['level'] : 0;

			// If no level is set, or it is lower than/equal to the key's level
			$authorized = $level <= $this->rest->level;

			// IM TELLIN!
			if (config_item('rest_enable_logging') AND $log_method)
			{
				$this->_log_request($authorized);
			}

			// They don't have good enough perms
			$authorized OR $this->response(array('status' => false, 'error' => 'This API key does not have enough permissions.'), 401);
		}

		// No key stuff, but record that stuff is happening
		else if (config_item('rest_enable_logging') AND $log_method)
		{
			$this->_log_request($authorized = TRUE);
		}

		// And...... GO!
		call_user_func_array(array($this, $controller_method), $arguments);
	}


	/*
	 * response
	 *
	 * Takes pure data and optionally a status code, then creates the response
	 */
	public function response($data = array(), $http_code = null)
	{
		// If data is empty and not code provide, error and bail
		if (empty($data) && $http_code === null)
		{
			$http_code = 404;
		}

		// Otherwise (if no data but 200 provided) or some data, carry on camping!
		else
		{
			is_numeric($http_code) OR $http_code = 200;

			// If the format method exists, call and return the output in that format
			if (method_exists($this, '_format_' . $this->response->format))
			{
				// Set the correct format header
				/**
				 *  @author: Woxxy
				 */
				if (!$this->input->is_cli_request())
					header('Content-Type: ' . $this->_supported_formats[$this->response->format]);

				$output = $this->{'_format_' . $this->response->format}($data);
			}

			// If the format method exists, call and return the output in that format
			elseif (method_exists($this->format, 'to_' . $this->response->format))
			{
				// Set the correct format header
				/**
				 *  @author: Woxxy
				 */
				if (!$this->input->is_cli_request())
					header('Content-Type: ' . $this->_supported_formats[$this->response->format]);

				$output = $this->format->factory($data)->{'to_' . $this->response->format}();
			}

			// Format not supported, output directly
			else
			{
				$output = $data;
			}
		}

		/**
		 *  @author: Woxxy
		 */
		if (!$this->input->is_cli_request())
		{
			header('HTTP/1.1: ' . $http_code);
			// fake last modified!
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header('Status: ' . $http_code);
			header('Content-Length: ' . strlen($output));
		}
		else
		{
			$output .= PHP_EOL;
		}
		exit($output);
	}


	/*
	 * Detect input format
	 *
	 * Detect which format the HTTP Body is provided in
	 */
	protected function _detect_input_format()
	{
		if ($this->input->server('CONTENT_TYPE'))
		{
			// Check all formats against the HTTP_ACCEPT header
			foreach ($this->_supported_formats as $format => $mime)
			{
				if (strpos($match = $this->input->server('CONTENT_TYPE'), ';'))
				{
					$match = current(explode(';', $match));
				}

				if ($match == $mime)
				{
					return $format;
				}
			}
		}

		return NULL;
	}


	/*
	 * Detect format
	 *
	 * Detect which format should be used to output the data
	 */
	protected function _detect_output_format()
	{
		$pattern = '/\.(' . implode('|', array_keys($this->_supported_formats)) . ')$/';

		// Check if a file extension is used
		if (preg_match($pattern, $this->uri->uri_string(), $matches))
		{
			return $matches[1];
		}

		// Check if a file extension is used
		elseif ($this->_get_args AND !is_array(end($this->_get_args)) AND preg_match($pattern, end($this->_get_args), $matches))
		{
			// The key of the last argument
			$last_key = end(array_keys($this->_get_args));

			// Remove the extension from arguments too
			$this->_get_args[$last_key] = preg_replace($pattern, '', $this->_get_args[$last_key]);
			$this->_args[$last_key] = preg_replace($pattern, '', $this->_args[$last_key]);

			return $matches[1];
		}

		// A format has been passed as an argument in the URL and it is supported
		if (isset($this->_get_args['format']) AND array_key_exists($this->_get_args['format'], $this->_supported_formats))
		{
			return $this->_get_args['format'];
		}

		// Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
		if ($this->config->item('rest_ignore_http_accept') === FALSE AND $this->input->server('HTTP_ACCEPT'))
		{
			// Check all formats against the HTTP_ACCEPT header
			foreach (array_keys($this->_supported_formats) as $format)
			{
				// Has this format been requested?
				if (strpos($this->input->server('HTTP_ACCEPT'), $format) !== FALSE)
				{
					// If not HTML or XML assume its right and send it on its way
					if ($format != 'html' AND $format != 'xml')
					{

						return $format;
					}

					// HTML or XML have shown up as a match
					else
					{
						// If it is truely HTML, it wont want any XML
						if ($format == 'html' AND strpos($this->input->server('HTTP_ACCEPT'), 'xml') === FALSE)
						{
							return $format;
						}

						// If it is truely XML, it wont want any HTML
						elseif ($format == 'xml' AND strpos($this->input->server('HTTP_ACCEPT'), 'html') === FALSE)
						{
							return $format;
						}
					}
				}
			}
		} // End HTTP_ACCEPT checking
		// Well, none of that has worked! Let's see if the controller has a default
		if (!empty($this->rest_format))
		{
			return $this->rest_format;
		}

		// Just use the default format
		return config_item('rest_default_format');
	}


	/*
	 * Detect method
	 *
	 * Detect which method (POST, PUT, GET, DELETE) is being used
	 */
	protected function _detect_method()
	{
		$method = strtolower($this->input->server('REQUEST_METHOD'));

		if ($this->config->item('enable_emulate_request') && $this->input->post('_method'))
		{
			$method = $this->input->post('_method');
		}

		if (in_array($method, array('get', 'delete', 'post', 'put')))
		{
			return $method;
		}

		return 'get';
	}


	/*
	 * Detect API Key
	 *
	 * See if the user has provided an API key
	 */
	protected function _detect_api_key()
	{
		// Work out the name of the SERVER entry based on config
		$key_name = 'HTTP_' . strtoupper(str_replace('-', '_', config_item('rest_key_name')));

		$this->rest->key = NULL;
		$this->rest->level = NULL;
		$this->rest->ignore_limits = FALSE;

		// Find the key from server or arguments
		if ($key = isset($this->_args['API-Key']) ? $this->_args['API-Key'] : $this->input->server($key_name))
		{
			if (!$row = $this->rest->db->where('key', $key)->get(config_item('rest_keys_table'))->row())
			{
				return FALSE;
			}

			$this->rest->key = $row->key;

			isset($row->level) AND $this->rest->level = $row->level;
			isset($row->ignore_limits) AND $this->rest->ignore_limits = $row->ignore_limits;

			return TRUE;
		}

		// No key has been sent
		return FALSE;
	}


	/*
	 * Detect language(s)
	 *
	 * What language do they want it in?
	 */
	protected function _detect_lang()
	{
		if (!$lang = $this->input->server('HTTP_ACCEPT_LANGUAGE'))
		{
			return NULL;
		}

		// They might have sent a few, make it an array
		if (strpos($lang, ',') !== FALSE)
		{
			$langs = explode(',', $lang);

			$return_langs = array();
			$i = 1;
			foreach ($langs as $lang)
			{
				// Remove weight and strip space
				list($lang) = explode(';', $lang);
				$return_langs[] = trim($lang);
			}

			return $return_langs;
		}

		// Nope, just return the string
		return $lang;
	}


	/*
	 * Log request
	 *
	 * Record the entry for awesomeness purposes
	 */
	protected function _log_request($authorized = FALSE)
	{
		return $this->rest->db->insert(config_item('rest_logs_table'), array(
					'uri' => $this->uri->uri_string(),
					'method' => $this->request->method,
					'params' => serialize($this->_args),
					'api_key' => isset($this->rest->key) ? $this->rest->key : '',
					'ip_address' => $this->input->ip_address(),
					'time' => function_exists('now') ? now() : time(),
					'authorized' => $authorized
				));
	}


	/*
	 * Log request
	 *
	 * Record the entry for awesomeness purposes
	 */
	protected function _check_limit($controller_method)
	{
		// They are special, or it might not even have a limit
		if (!empty($this->rest->ignore_limits) OR !isset($this->methods[$controller_method]['limit']))
		{
			// On your way sonny-jim.
			return TRUE;
		}

		// How many times can you get to this method an hour?
		$limit = $this->methods[$controller_method]['limit'];

		// Get data on a keys usage
		$result = $this->rest->db
				->where('uri', $this->uri->uri_string())
				->where('api_key', $this->rest->key)
				->get(config_item('rest_limits_table'))
				->row();

		// No calls yet, or been an hour since they called
		if (!$result OR $result->hour_started < time() - (60 * 60))
		{
			// Right, set one up from scratch
			$this->rest->db->insert(config_item('rest_limits_table'), array(
				'uri' => $this->uri->uri_string(),
				'api_key' => isset($this->rest->key) ? $this->rest->key : '',
				'count' => 1,
				'hour_started' => time()
			));
		}

		// They have called within the hour, so lets update
		else
		{
			// Your luck is out, you've called too many times!
			if ($result->count > $limit)
			{
				return FALSE;
			}

			$this->rest->db
					->where('uri', $this->uri->uri_string())
					->where('api_key', $this->rest->key)
					->set('count', 'count + 1', FALSE)
					->update(config_item('rest_limits_table'));
		}

		return TRUE;
	}


	/*
	 * Auth override check
	 *
	 * Check if there is a specific auth type set for the current class/method being called
	 */
	protected function _auth_override_check()
	{

		// Assign the class/method auth type override array from the config
		$this->overrides_array = $this->config->item('auth_override_class_method');

		// Check to see if the override array is even populated, otherwise return false
		if (empty($this->overrides_array))
		{
			return false;
		}

		// Check to see if there's an override value set for the current class/method being called
		if (empty($this->overrides_array[$this->router->class][$this->router->method]))
		{
			return false;
		}

		// None auth override found, prepare nothing but send back a true override flag
		if ($this->overrides_array[$this->router->class][$this->router->method] == 'none')
		{
			return true;
		}

		// Basic auth override found, prepare basic
		if ($this->overrides_array[$this->router->class][$this->router->method] == 'basic')
		{
			$this->_prepare_basic_auth();
			return true;
		}

		// Digest auth override found, prepare digest
		if ($this->overrides_array[$this->router->class][$this->router->method] == 'digest')
		{
			$this->_prepare_digest_auth();
			return true;
		}

		// Return false when there is an override value set but it doesn't match 'basic', 'digest', or 'none'.  (the value was misspelled)
		return false;
	}


	// INPUT FUNCTION --------------------------------------------------------------

	public function get($key = NULL, $xss_clean = TRUE)
	{
		if ($key === NULL)
		{
			return $this->_get_args;
		}

		return array_key_exists($key, $this->_get_args) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : FALSE;
	}


	public function post($key = NULL, $xss_clean = TRUE)
	{
		if ($key === NULL)
		{
			return $this->_post_args;
		}

		return $this->input->post($key, $xss_clean);
	}


	public function put($key = NULL, $xss_clean = TRUE)
	{
		if ($key === NULL)
		{
			return $this->_put_args;
		}

		return array_key_exists($key, $this->_put_args) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : FALSE;
	}


	public function delete($key = NULL, $xss_clean = TRUE)
	{
		if ($key === NULL)
		{
			return $this->_delete_args;
		}

		return array_key_exists($key, $this->_delete_args) ? $this->_xss_clean($this->_delete_args[$key], $xss_clean) : FALSE;
	}


	protected function _xss_clean($val, $bool)
	{
		if (CI_VERSION < 2)
		{
			return $bool ? $this->input->xss_clean($val) : $val;
		}
		else
		{
			return $bool ? $this->security->xss_clean($val) : $val;
		}
	}


	public function validation_errors()
	{
		$string = strip_tags($this->form_validation->error_string());

		return explode("\n", trim($string, "\n"));
	}


	// SECURITY FUNCTIONS ---------------------------------------------------------

	protected function _check_login($username = '', $password = NULL)
	{
		if (empty($username))
		{
			return FALSE;
		}

		$valid_logins = & $this->config->item('rest_valid_logins');

		if (!array_key_exists($username, $valid_logins))
		{
			return FALSE;
		}

		// If actually NULL (not empty string) then do not check it
		if ($password !== NULL AND $valid_logins[$username] != $password)
		{
			return FALSE;
		}

		return TRUE;
	}


	protected function _prepare_basic_auth()
	{
		$username = NULL;
		$password = NULL;

		// mod_php
		if ($this->input->server('PHP_AUTH_USER'))
		{
			$username = $this->input->server('PHP_AUTH_USER');
			$password = $this->input->server('PHP_AUTH_PW');
		}

		// most other servers
		elseif ($this->input->server('HTTP_AUTHENTICATION'))
		{
			if (strpos(strtolower($this->input->server('HTTP_AUTHENTICATION')), 'basic') === 0)
			{
				list($username, $password) = explode(':', base64_decode(substr($this->input->server('HTTP_AUTHORIZATION'), 6)));
			}
		}

		if (!$this->_check_login($username, $password))
		{
			$this->_force_login();
		}
	}


	protected function _prepare_digest_auth()
	{
		$uniqid = uniqid(""); // Empty argument for backward compatibility
		// We need to test which server authentication variable to use
		// because the PHP ISAPI module in IIS acts different from CGI
		if ($this->input->server('PHP_AUTH_DIGEST'))
		{
			$digest_string = $this->input->server('PHP_AUTH_DIGEST');
		}
		elseif ($this->input->server('HTTP_AUTHORIZATION'))
		{
			$digest_string = $this->input->server('HTTP_AUTHORIZATION');
		}
		else
		{
			$digest_string = "";
		}

		/* The $_SESSION['error_prompted'] variabile is used to ask
		  the password again if none given or if the user enters
		  a wrong auth. informations. */
		if (empty($digest_string))
		{
			$this->_force_login($uniqid);
		}

		// We need to retrieve authentication informations from the $auth_data variable
		preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
		$digest = array_combine($matches[1], $matches[2]);

		if (!array_key_exists('username', $digest) OR !$this->_check_login($digest['username']))
		{
			$this->_force_login($uniqid);
		}

		$valid_logins = & $this->config->item('rest_valid_logins');
		$valid_pass = $valid_logins[$digest['username']];

		// This is the valid response expected
		$A1 = md5($digest['username'] . ':' . $this->config->item('rest_realm') . ':' . $valid_pass);
		$A2 = md5(strtoupper($this->request->method) . ':' . $digest['uri']);
		$valid_response = md5($A1 . ':' . $digest['nonce'] . ':' . $digest['nc'] . ':' . $digest['cnonce'] . ':' . $digest['qop'] . ':' . $A2);

		if ($digest['response'] != $valid_response)
		{
			header('HTTP/1.0 401 Unauthorized');
			header('HTTP/1.1 401 Unauthorized');
			exit;
		}
	}


	private function _prepare_oauth()
	{
		$this->load->config('oauth_resource_server');

		// Get the access token
		$access_token = trim($this->input->get($this->config->item('oauth_access_token_param')));

		if (!$access_token || $access_token == "")
		{
			header('HTTP/1.0 401 Unauthorized');
			header('HTTP/1.1 401 Unauthorized');
			exit;
		}

		// Verify the access token
		$this->load->library('curl');

		// Build the verify url
		$verify_params = $this->config->item('oauth_verify_uri_params');
		$verify_params['access_token'] = $access_token;

		$verify_uri = $this->config->item('oauth_verify_uri') . '?' . http_build_query($verify_params);

		$verify = $this->CI->curl->simple_get($verify_url, array(), array(CURLOPT_FAILONERROR => FALSE));

		// Successful response from the auth server				
		if ($verify)
		{
			$verify = json_decode($verify);

			// Access token was invalid	
			if (isset($verify->error))
			{
				header('HTTP/1.0 401 Unauthorized');
				header('HTTP/1.1 401 Unauthorized');
				exit;
			}
			else
			{
				// Looks like the token is okay, allow the request
			}
		}

		// Auth server failed to reply / bad network connection
		else
		{
			header('HTTP/1.0 502 Bad Gateway');
			header('HTTP/1.1 502 Bad Gateway');
			exit;
		}
	}


	protected function _force_login($nonce = '')
	{
		if ($this->config->item('rest_auth') == 'basic')
		{
			header('WWW-Authenticate: Basic realm="' . $this->config->item('rest_realm') . '"');
		}
		elseif ($this->config->item('rest_auth') == 'digest')
		{
			header('WWW-Authenticate: Digest realm="' . $this->config->item('rest_realm') . '" qop="auth" nonce="' . $nonce . '" opaque="' . md5($this->config->item('rest_realm')) . '"');
		}

		$this->response(array('status' => false, 'error' => 'Not authorized'), 401);
	}


	// Force it into an array
	protected function _force_loopable($data)
	{
		// Force it to be something useful
		if (!is_array($data) AND !is_object($data))
		{
			$data = (array) $data;
		}

		return $data;
	}


	// FORMATING FUNCTIONS ---------------------------------------------------------
	// Many of these have been moved to the Format class for better separation, but these methods will be checked too
	// Encode as JSONP
	protected function _format_jsonp($data = array())
	{
		return $this->get('callback') . '(' . json_encode($data) . ')';
	}


	/*
	 * 
	 * FUNCTIONS ADDED FOR FOOLSLIDE
	 * 
	 */

	/*
	 * Commodity to check that the ID is not wrong and return a coherent error
	 * 
	 * @author Woxxy
	 */
	function _check_id()
	{
		if (!$this->get('id'))
		{
			$this->response(array('error' => _('ID not set')), 400);
			return FALSE;
		}

		if (!is_numeric($this->get('id')) || ($this->get('id') < 1))
		{
			$this->response(array('error' => _('ID is not a valid number')), 400);
			return FALSE;
		}

		return TRUE;
	}


	/*
	 * Checks that the orderby method is correct or ignores it if wrong.
	 * 
	 * @author Woxxy
	 * @param DataMapper $object database query
	 * @param array $add add accepted values for search
	 * @param array $remove removes default accepted values
	 * @param array $default default accepted values
	 */
	function _orderby($object, $add = array(), $remove = array(), $default = array('id', 'name', 'created', 'updated'))
	{
		// return TRUE if there's no orderby set
		if (!$this->get('orderby'))
			return TRUE;

		// the tag is set
		$order = $this->get('orderby');

		
		// add and remove from default array
		$default = array_merge($default, $add);
		$default = array_diff($default, $remove);

		// neutralize those caps
		$order = strtolower($order);

		// determine if ASC or DESC
		$asc = substr($order, 0, 3);
		if ($asc == "asc")
		{
			$asc = "ASC";
			$order = substr($order, 4);
		}
		else if ($asc == "des")
		{
			$asc = "DESC";
			$order = substr($order, 5);
		}
		else
		{
			// DESC or ASC was not set, return the error
			$this->response(array('error' => _('"orderby" tag wasn\'t correctly used.')), 400);
			return FALSE;
		}

		// fix for compatibility FoOlSlide 0.8.4 (05/10/2011)
		if($order == 'edited')
			$order = 'updated';
		
		// check that the orderby method exists
		if (in_array($order, $default))
		{
			$object->order_by($order, $asc);
			return TRUE;
		}

		// there's no such method, return an error
		$this->response(array('error' => _('"orderby" tag wasn\'t correctly used')), 400);
		return FALSE;
	}


	/*
	 * Retrieves the page tag and returns the correct limit for DataMapper
	 * 
	 * @author Woxxy
	 * @param Datamapper $object
	 * @param int $limit entries per page
	 * @return $entry the entry from which the Datamapper limit function gets results
	 */
	function _page_to_offset($object, $default = 30, $max = 100)
	{
		// Give at least the first page if the page tag is not set or wrongly set
		if (!$this->get('page'))
		{
			$page = 1;
		}
		else
		{
			// wrong page values
			if (!is_numeric($this->get('page')) || $this->get('page') < 1)
			{
				$this->response(array('error' => _("The \"page\" tag was not a valid number.")), 400);
				return FALSE;
			}
			// or use the page tag
			$page = (int) $this->get('page');
		}

		// Give at least the first page if the page tag is not set or wrongly set
		if (!$this->get('per_page') || !is_numeric($this->get('per_page')))
		{
			$per_page = $default;
		}
		else
		{
			// too large per_page number
			if ($this->get('per_page') > $max)
			{
				$this->response(array('error' => _("You're trying to get a larger number of \"per_page\" results per request than allowed.")), 400);
				return FALSE;
			}
			// or use the page tag
			$per_page = (int) $this->get('per_page');
		}

		// the incredible algorithm to convert page to the right entry number
		$entry = ($page * $per_page) - $per_page;

		// trigger the limit
		$object->limit($per_page, $entry);
	}


}