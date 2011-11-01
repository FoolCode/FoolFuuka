<?php

/**
 * OAuth 2.0 client for use with the included auth server
 * *
 * @package             CodeIgniter
 * @author              Alex Bilbie | www.alexbilbie.com | alex@alexbilbie.com
 * @copyright   		Copyright (c) 2010, Alex Bilbie.
 * @license             http://codeigniter.com/user_guide/license.html
 * @link                http://alexbilbie.com
 * @version             Version 0.2
 */

class Oauth_client {

	private $ci;
	public $error = '';
	
	function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->config('oauth_client');
	}
	
	/**
	 * Redirects the user to the OAuth sign in page
	 *
	 * $state is an key value array of parameters which will be contactenated with the redirect_uri (for example session IDs)
	 * $csrf is a flag to enable CSRF (cross site request forgery) protection. Highly recommended you leave it enabled
	 *
	 * @access public
	 * @param array state
	 * @param bool csrf
	 */
	public function sign_in($state_params = array(), $csrf = TRUE)
	{
		if ($csrf)
		{
			$token = md5(uniqid());
			$this->ci->session->set_userdata('oauth_csrf', $token);
			
			$state_params['oauth_csrf'] = $token;
		}
		
		if (count($state_params) > 0)
		{
			$states = array();
			foreach ($state_params as $k=>$v)
			{
				$states[] = $k . '=' . $v;
			}
			
			$state = implode('&', $states);
			$state = urlencode($state);
		}
		
		redirect($this->ci->config->item('oauth_signin_url') . $state);
	}
	
	/**
	 * Redirects the user to the OAuth sign out page
	 */
	public function sign_out()
	{
		redirect($this->ci->config->item('oauth_signout_url'));
	}
	
	/**
	 * Exchanges an auth code for an access token
	 * 
	 * @access public
	 * @param string $code
	 * @return string|bool
	 */
	public function get_access_token($code = '')
	{
		$this->ci->load->library('curl');
		$this->ci->curl->option('returntransfer', TRUE);
		
		try
		{
			$url = $this->ci->config->item('oauth_access_token_uri').$code;
			$access = $this->ci->curl->simple_post($url);
			
			if($access)
			{
				$access = json_decode($access);
				
				if(isset($access->error))
				{
					throw new Exception('[OAuth error] '.$access->message);
				}
				
				else
				{				
					return $access->access_token;
				}
			}
			
			else
			{
				throw new Exception('[OAuth cURL error] ' . $this->ci->curl->error_string);
			}
		}
		
		catch (Exception $e)
		{
			$this->error = $e->getMessage();
			return FALSE;
		}
					
	}
}