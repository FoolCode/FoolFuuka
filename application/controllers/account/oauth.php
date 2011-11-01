<?php

/**
 * OAuth 2.0 client for use with the included auth server
 *
 * @author              Alex Bilbie | www.alexbilbie.com | alex@alexbilbie.com
 * @copyright   		Copyright (c) 2011, Alex Bilbie.
 * @license             http://www.opensource.org/licenses/mit-license.php
 * @link                https://github.com/alexbilbie/CodeIgniter-OAuth-2.0-Server
 * @version             Version 0.2
 */
/*
  Copyright (c) 2011 Alex Bilbie | alex@alexbilbie.com

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE.
 */

class Oauth extends Account_Controller
{
	function __construct()
	{
		show_404();
		parent::__construct();
		$this->load->helper('url');
		$this->load->library('oauth_auth_server');
	}


	/**
	 * This is the function that users are sent to when they first enter the flow
	 */
	function index()
	{
		// Get query string parameters
		// ?response_type=code&client_id=XXX&redirect_uri=YYY&scope=ZZZ&state=123
		$params = $this->oauth_auth_server->validate_params(array('response_type' => array('code', 'token'), 'client_id' => TRUE, 'redirect_uri' => TRUE, 'scope' => FALSE, 'state' => FALSE)); // returns array or FALSE
		// If missing/bad parameter
		if ($params == FALSE)
		{
			$this->_fail('[OAuth client error: invalid_request] The request is missing a required parameter, includes an unsupported parameter or parameter value, or is otherwise malformed.', TRUE);
			return;
		}

		// Validate client_id and redirect_uri
		$client_details = $this->oauth_auth_server->validate_client($params['client_id'], NULL, $params['redirect_uri']); // returns object or FALSE
		if ($client_details === FALSE)
		{
			$this->_fail("[OAuth client error: unauthorized_client] The client is not authorised to request an authorization code using this method.", TRUE);
			return;
		}
		$this->session->set_userdata('client_details', $client_details);

		// Get the scope
		if (isset($params['scope']) && count($params['scope']) > 0)
		{
			$params['scope'] = explode(',', $params['scope']);
			if (!in_array('basic', $params['scope']))
			{
				// Add basic scope regardless
				$params['scope'][] = 'basic';
			}
		}
		else
		{
			// Add basic scope regardless
			$params['scope'] = array(
				'basic'
			);
		}

		// Save the params in the session
		$this->session->set_userdata(array('params' => $params));

		// Check if user is signed in already
		$user_id = $this->tank_auth->get_user_id(); // returns string or FALSE
		// If the user is already signed in and the app has the flag 'auto_approve'
		// Then generate a new auth code and redirect the user back to the application
		if ($user_id && $client_details->auto_approve == 1)
		{
			if ($params['response_type'] == 'token') // user agent flow
			{
				$this->fast_token_redirect($client_details->client_id, $user_id, $params['redirect_uri'], $params['scope'], $params['state']);
			}
			else // web server flow
			{
				$this->fast_code_redirect($client_details->client_id, $user_id, $params['redirect_uri'], $params['scope'], $params['state']);
			}
		}

		// Has the user authorised the application already?
		if ($user_id)
		{
			$authorised = $this->oauth_auth_server->access_token_exists($user_id, $params['client_id']); // return TRUE or FALSE
			// If there is already an access token then the user has authorised the application
			// Generate a new auth code and redirect the user back to the application
			if ($authorised)
			{
				if ($params['response_type'] == 'token') // user agent flow
				{
					$this->fast_token_redirect($client_details->client_id, $user_id, $params['redirect_uri'], $params['scope'], $params['state']);
				}
				else // web server flow
				{
					$this->fast_code_redirect($client_details->client_id, $user_id, $params['redirect_uri'], $params['scope'], $params['state']);
				}
			}

			// The user hasn't authorised the application. Send them to the authorise page.
			else
			{
				redirect(site_url(array('oauth', 'authorise')), 'location');
			}
		}

		// The user is not signed in, send them to sign in
		else
		{
			$this->session->set_userdata('sign_in_redirect', array('oauth', 'authorise'));
			redirect(site_url(array('oauth', 'sign_in')), 'location');
		}
	}


	/**
	 * If the user isn't signed in they will be redirect here
	 */
	function sign_in()
	{
		// Check if user is signed in, if so redirect them on to /authorise
		if ($this->tank_auth->is_logged_in())
		{   // logged in
			redirect(site_url($this->session->userdata('sign_in_redirect')), 'location');
		}

		// Check there is are client parameters are stored
		$client = $this->session->userdata('client_details'); // returns object or FALSE
		if ($client == FALSE)
		{
		//	$this->_fail('[OAuth user error: invalid_request] No client details have been saved. Have you deleted your cookies?', TRUE);
		//	return;
		}
		
		
		if ($this->tank_auth->is_logged_in(FALSE))
		{   // logged in, not activated
			redirect('/account/auth/send_again/');
		}
		else
		{
			$data['login_by_username'] = ($this->config->item('login_by_username', 'tank_auth') AND
					$this->config->item('use_username', 'tank_auth'));
			$data['login_by_email'] = $this->config->item('login_by_email', 'tank_auth');

			$this->form_validation->set_rules('login', 'Login', 'trim|required|xss_clean');
			$this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
			$this->form_validation->set_rules('remember', 'Remember me', 'integer');

			// Get login for counting attempts to login
			if ($this->config->item('login_count_attempts', 'tank_auth') AND
					($login = $this->input->post('login')))
			{
				$login = $this->security->xss_clean($login);
			}
			else
			{
				$login = '';
			}

			$data['use_recaptcha'] = $this->config->item('use_recaptcha', 'tank_auth');
			if ($this->tank_auth->is_max_login_attempts_exceeded($login))
			{
				if ($data['use_recaptcha'])
					$this->form_validation->set_rules('recaptcha_response_field', 'Confirmation Code', 'trim|xss_clean|required|callback__check_recaptcha');
				else
					$this->form_validation->set_rules('captcha', 'Confirmation Code', 'trim|xss_clean|required|callback__check_captcha');
			}
			$data['errors'] = array();

			if ($this->form_validation->run())
			{  // validation ok
				if ($this->tank_auth->login(
								$this->form_validation->set_value('login'), $this->form_validation->set_value('password'), $this->form_validation->set_value('remember'), $data['login_by_username'], $data['login_by_email']))
				{  // success
					redirect(site_url($this->session->userdata('sign_in_redirect')), 'location');
				}
				else
				{
					$errors = $this->tank_auth->get_error_message();
					if (isset($errors['banned']))
					{  // banned user
						$this->_show_message($this->lang->line('auth_message_banned') . ' ' . $errors['banned']);
					}
					elseif (isset($errors['not_activated']))
					{ // not activated user
						redirect('/account/auth/send_again/');
					}
					else
					{ // fail
						foreach ($errors as $k => $v)
							$data['errors'][$k] = $this->lang->line($v);
					}
				}
			}
			$data['show_captcha'] = FALSE;
			if ($this->tank_auth->is_max_login_attempts_exceeded($login))
			{
				$data['show_captcha'] = TRUE;
				if ($data['use_recaptcha'])
				{
					$data['recaptcha_html'] = $this->_create_recaptcha();
				}
				else
				{
					$data['captcha_html'] = $this->_create_captcha();
				}
			}

			$data["oauth"] = TRUE;
			$this->viewdata["function_title"] = _("Login");
			$this->viewdata["main_content_view"] = $this->load->view('auth/login_form', $data, TRUE);
			$this->load->view("auth/default.php", $this->viewdata);
		}
	}


	/**
	 * Sign the user out of the SSO service
	 * 
	 * @access public
	 * @return void
	 */
	function sign_out()
	{
		$this->session->sess_destroy();

		if ($redirect_uri = $this->input->get('redirect_uri'))
		{
			redirect($redirect_uri);
		}
		else
		{
			$this->load->view('oauth_auth_server/sign_out');
		}
	}


	/**
	 * When the user has signed in they will be redirected here to approve the application
	 */
	function authorise()
	{
		// Check if the user is signed in
		$user_id = $this->tank_auth->get_user_id();
		if ($user_id == FALSE)
		{
			$this->session->set_userdata('sign_in_redirect', array('oauth', 'authorise'));
			redirect(site_url(array('oauth', 'sign_in')), 'location');
		}

		// Check the client params are stored
		$client = $this->session->userdata('client_details');
		if ($client == FALSE)
		{
			$this->_fail('[OAuth user error: invalid_request] No client details have been saved. Have you deleted your cookies?', TRUE);
			return;
		}

		// The GET parameters
		$params = $this->session->userdata('params');
		if ($params == FALSE)
		{
			$this->_fail('[OAuth user error: invalid_request] No OAuth parameters have been saved. Have you deleted your cookies?', TRUE);
			return;
		}

		// If the user is signed in and the they have approved the application
		// Then generate a new auth code and redirect the user back to the application
		$authorised = $this->oauth_auth_server->access_token_exists($user_id, $client->client_id);
		if ($authorised)
		{
			if ($params['response_type'] == 'token') // user agent flow
			{
				$this->fast_token_redirect($client->client_id, $user_id, $params['redirect_uri'], $params['scope'], $params['state']);
			}
			else // web server flow
			{
				$this->fast_code_redirect($client->client_id, $user_id, $params['redirect_uri'], $params['scope'], $params['state']);
			}
		}

		// If the user is already signed in and the app has the flag 'auto_approve'
		// Then generate a new auth code and redirect the user back to the application
		elseif ($user_id && $client->auto_approve == 1)
		{
			if ($params['response_type'] == 'token') // user agent flow
			{
				$this->fast_token_redirect($client->client_id, $user_id, $params['redirect_uri'], $params['scope'], $params['state']);
			}
			else // web server flow
			{
				$this->fast_code_redirect($client->client_id, $user_id, $params['redirect_uri'], $params['scope'], $params['state']);
			}
		}

		// If we've not redirected already we need to show the user the approval form
		// Has the user clicked the authorise button
		$doauth = $this->input->post('doauth');

		if ($doauth)
		{
			switch ($doauth)
			{
				// The user has approved the application.
				// Generate a new auth code and redirect the user back to the application
				case "Approve":

					$code = $this->oauth_auth_server->new_auth_code($client->client_id, $user_id, $params['redirect_uri'], $params['scope']);
					$redirect_uri = $this->oauth_auth_server->redirect_uri($params['redirect_uri'], array('code=' . $code . '&state=' . $params['state']));
					break;

				// The user has denied the application
				// Do a low redirect back to the application with the error
				case "Deny":

					// Append the error code
					$redirect_uri = $this->oauth_auth_server->redirect_uri($params['redirect_uri'], array('error=access_denied&error_description=The+authorization+server+does+not+support+obtaining+an+authorization+code+using+this+method&state=' . $params['state']));
					break;
			}

			// Redirect back to app
			$this->session->unset_userdata(array('params' => '', 'client_details' => '', 'sign_in_redirect' => ''));
			$this->load->view('oauth_auth_server/redirect', array('redirect_uri' => $redirect_uri, 'client_name' => $client->name));
		}

		// The user hasn't approved the application before and it's not an internal application
		else
		{
			$vars = array(
				'client_name' => $client->name
			);

			$this->load->view('oauth_auth_server/authorise', $vars);
		}
	}


	/**
	 * Generate a new access token
	 */
	function access_token()
	{
		// Get query string parameters
		// ?grant_type=authorization_code&client_id=XXX&client_secret=YYY&redirect_uri=ZZZ&code=123
		$params = $this->oauth_auth_server->validate_params(array('code' => TRUE, 'client_id' => TRUE, 'client_secret' => TRUE, 'grant_type' => array('authorization_code'), 'redirect_uri' => TRUE));

		// If missing/bad param
		if ($params == FALSE)
		{
			$this->_fail($this->oauth_auth_server->param_error);
			return;
		}

		// Validate client_id and redirect_uri
		$client_details = $this->oauth_auth_server->validate_client($params['client_id'], NULL, $params['redirect_uri']); // returns object or FALSE
		if ($client_details === FALSE)
		{
			$this->_fail("[OAuth client error: unauthorized_client] The client is not authorised to request an authorization code using this method.", TRUE);
			return;
		}

		// Respond to the grant type
		switch ($params['grant_type'])
		{
			case "authorization_code":

				// Validate the auth code
				$session_id = $this->oauth_auth_server->validate_auth_code($params['code'], $params['client_id'], $params['redirect_uri']);
				if ($session_id === FALSE)
				{
					$this->_fail("[OAuth client error: invalid_request] Invalid authorization code");
					return;
				}

				// Generate a new access_token (and remove the authorise code from the session)
				$access_token = $this->oauth_auth_server->get_access_token($session_id);

				// Send the response back to the application
				$this->_response(array('access_token' => $access_token, 'token_type' => '', 'expires_in' => NULL, 'refresh_token' => NULL));
				return;

				break;

			// When refresh tokens are implemented the logic would go here
		}
	}


	/**
	 * Resource servers will make use of this URL to validate an access token
	 */
	function verify_access_token()
	{
		// Get query string parameters
		// ?grant_type=access_token=XXX&scope=YYY
		$params = $this->oauth_auth_server->validate_params(array('access_token' => TRUE, 'scope' => FALSE));

		// If missing/bad param
		if ($params == FALSE)
		{
			$this->_fail($this->oauth_auth_server->param_error);
			return;
		}

		// Get the scope
		$scopes = array('basic');
		if (isset($params['scope']))
		{
			$scopes = explode(',', $params['scope']);
		}

		// Test scope
		$result = $this->oauth_auth_server->validate_access_token($params['access_token'], $scopes);

		if ($result)
		{
			$resp = array(
				'access_token' => $params['access_token'],
			);

			$this->_response($resp);
		}
		else
		{
			$this->_fail('Invalid `access_token`', FALSE);
			return;
		}
	}


	/**
	 * Generates a new auth code and redirects the user
	 * Used in the web-server flow
	 * 
	 * @access private
	 * @param string $client_id
	 * @param string $user_id
	 * @param string $redirect_uri
	 * @param array $scope
	 * @param string $state
	 * @return void
	 */
	private function fast_code_redirect($client_id = "", $user_id = "", $redirect_uri = "", $scopes = array(), $state = "")
	{
		$code = $this->oauth_auth_server->new_auth_code($client_id, $user_id, $redirect_uri, $scopes);
		$redirect_uri = $this->oauth_auth_server->redirect_uri($redirect_uri, array('code=' . $code . "&state=" . $state));

		$this->session->unset_userdata(array('params' => '', 'client_details' => '', 'sign_in_redirect' => ''));
		redirect($redirect_uri, 'location');
	}


	/**
	 * Generates a new auth access token and redirects the user
	 * Used in the user-agent flow
	 * 
	 * @access private
	 * @param string $client_id
	 * @param string $user_id
	 * @param string $redirect_uri
	 * @param array $scope
	 * @param string $state
	 * @return void
	 */
	private function fast_token_redirect($client_id = "", $user_id = "", $redirect_uri = "", $scopes = array(), $state = "")
	{
		// Creates a limited access token due to lack of verification/authentication
		$token = $this->oauth_auth_server->new_auth_code($client_id, $user_id, $redirect_uri, $scopes, 1);
		$redirect_uri = $this->oauth_auth_server->redirect_uri($redirect_uri, array('code=' . $code . "&state=" . $state), '#');

		$this->session->unset_userdata(array('params' => '', 'client_details' => '', 'sign_in_redirect' => ''));
		redirect($redirect_uri, 'location');
	}


	/**
	 * Show an error message
	 * 
	 * @access private
	 * @param mixed $msg
	 * @return string
	 */
	private function _fail($msg, $friendly=FALSE)
	{
		if ($friendly)
		{
			show_error($msg, 500);
		}
		else
		{
			$this->output->set_status_header('500');
			$this->output->set_header('Content-type: text/plain');
			$this->output->set_output(json_encode(array('error' => 1, 'error_message' => $msg)));
		}
	}


	/**
	 * JSON response
	 * 
	 * @access private
	 * @param mixed $msg
	 * @return string
	 */
	private function _response($msg)
	{
		$msg['error'] = 0;
		$msg['error_message'] = '';
		$this->output->set_status_header('200');
		$this->output->set_header('Content-type: text/plain');
		$this->output->set_output(json_encode($msg));
	}


}