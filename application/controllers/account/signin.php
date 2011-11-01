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

class Signin extends Account_Controller {
	
	function __construct()
	{
		show_404();
		parent::__construct();
	}
		
	function index()
	{
		$this->load->library('oauth_client');
		$this->oauth_client->sign_in();
	}
	
	function signout()
	{
		$this->load->library('oauth_client');
		$this->session->sess_destroy();
		$this->oauth_client->sign_out();
	}
	
	function redirect()
	{
		$this->load->library('oauth_client');
		
		// If there was a problem with the auth server or the user declined your application there will be an error
		if ($this->input->get('error'))
		{
			show_error('[OAuth error] '.$this->input->get('error'), 500);
		}
						
		elseif ($this->input->get('code'))
		{
			$code = trim($this->input->get('code'));
			$state = trim($this->input->get('state'));
			
			// Convert the states back to an array and validate the CSRF token
			if ($state !== "")
			{
				$states = explode('&', urldecode($state));
				$state_params = array();
				
				foreach($states as $v)
				{
					$s = explode('=', $v);
					$state_params[$s[0]] = $s[1];
				}
				
				// Validate the CSRF token
				if (isset($state_params['oauth_csrf']))
				{
					if ($state_params['oauth_csrf'] !== $this->session->userdata('oauth_csrf'))
					{
						show_error('The state does not match. You may be a victim of CSRF.');
					}
				}
			}
			
			$access_token = $this->oauth_client->get_access_token($code);
			
			if ($access_token)
			{
				// Check to see a user already exists in your app's database based by searching for the access token - line below will not work
				$user_exists = $this->session->access_token_exists($access_token);
				
				if ($user_exists)
				{
					// The user exists already, set up their sessions and redirect them to the app
				}
				
				else
				{
					// Get the user's details from the resource server using the access token
					
					// Insert the user's details into your app's database (remember to store the access token!)
					
					// Set up their sessions and redirect them to the app
				}
			}
			
			else
			{
				show_error($this->oauth_client->error, 500);
			}
		
		}
		
		else
		{
			// No authorise code or error code redirect them for trying to be a fool
			$this->load->helper('url');
			redirect(site_url() . 'signin');
		}
	}

} // EOF