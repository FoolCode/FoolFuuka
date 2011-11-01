<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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


/*
 *	Change these to suit your needs
 */
$config['oauth_client_id'] = '';
$config['oauth_client_secret'] = '';
$config['oauth_scope'] = array('basic');
$config['oauth_redirect_uri'] = site_url().'signin/redirect';
$config['oauth_signout_redirect_uri'] = site_url();
$config['oauth_base'] = 'http://foolrulez.com/oauth/';

/*
 *	Don't touch these
 */
 
// Sign in URL
$config['oauth_signin_params'] = array(
	'response_type=code',
	'scope='.implode(',', $config['oauth_scope']),
	'client_id='.$config['oauth_client_id'],
	'redirect_uri='.$config['oauth_redirect_uri']
);
$config['oauth_signin_url'] = $config['oauth_base'].'?'.implode('&', $config['oauth_signin_params']);

// Sign out URL
$config['oauth_signout_params'] = array(
	'redirect_uri='.$config['oauth_signout_redirect_uri']
);
$config['oauth_signout_url'] = $config['oauth_base'].'signout?'.implode('&', $config['oauth_signout_params']);

// Access token URL
$config['oauth_access_token_params'] = array(
	'grant_type=authorization_code',
	'client_id='.$config['oauth_client_id'],
	'client_secret='.$config['oauth_client_secret'],
	'redirect_uri='.$config['oauth_redirect_uri']
);
$config['oauth_access_token_uri'] = $config['oauth_base'].'access_token?'.implode('&', $config['oauth_access_token_params']).'&code=';