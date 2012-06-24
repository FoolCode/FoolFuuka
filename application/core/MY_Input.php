<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class MY_Input extends CI_Input
{

	function __construct()
	{
		parent::__construct();
	}


	/**
	 * Fetch an item from the COOKIE array
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function cookie($index = '', $xss_clean = FALSE)
	{
		$prefix = '';

		if (!isset($_COOKIE[$index]) && config_item('cookie_prefix') != '')
		{
			$prefix = config_item('cookie_prefix');
		}

		return $this->_fetch_from_array($_COOKIE, $prefix . $index, $xss_clean);
	}


	/**
	 * Enable the post array to work with associative arrays
	 *
	 * @param type $index
	 * @param type $xss_clean
	 */
	function post($index = NULL, $xss_clean = FALSE)
	{
		if(substr($index, -1, 1) == ']' && substr($index, -2, 1) != '[')
		{
			// we have an associative array
			$pos = strrpos($index, '[');
			$key = substr($index, $pos+1, -1);
			$index = substr($index, 0, $pos);
			$post = parent::post($index, $xss_clean);
			if(!isset($post[$key]))
				return FALSE;
			return $post[$key];
		}
		return parent::post($index, $xss_clean);
	}


	/**
	 * Fetch the IP Address, modified to output numeric (decimal) IP
	 *
	 * @access	public
	 * @return	string
	 */
	function ip_address()
	{
		if ($this->ip_address !== FALSE)
		{
			return $this->ip_address;
		}

		if (config_item('proxy_ips') != '' && $this->server('HTTP_X_FORWARDED_FOR') && $this->server('REMOTE_ADDR'))
		{
			$proxies = preg_split('/[\s,]/', config_item('proxy_ips'), -1, PREG_SPLIT_NO_EMPTY);
			$proxies = is_array($proxies) ? $proxies : array($proxies);

			$this->ip_address = in_array($_SERVER['REMOTE_ADDR'], $proxies) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		}
		elseif ($this->server('REMOTE_ADDR') AND $this->server('HTTP_CLIENT_IP'))
		{
			$this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif ($this->server('REMOTE_ADDR'))
		{
			$this->ip_address = $_SERVER['REMOTE_ADDR'];
		}
		elseif ($this->server('HTTP_CLIENT_IP'))
		{
			$this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif ($this->server('HTTP_X_FORWARDED_FOR'))
		{
			$this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		if ($this->ip_address === FALSE)
		{
			$this->ip_address = '0.0.0.0';
			return $this->ip_address;
		}

		if (strpos($this->ip_address, ',') !== FALSE)
		{
			$x = explode(',', $this->ip_address);
			$this->ip_address = trim(end($x));
		}

		if ( ! $this->valid_ip($this->ip_address))
		{
			$this->ip_address = '0.0.0.0';
		}

		$this->ip_address = inet_ptod($this->ip_address);

		return $this->ip_address;
	}


}