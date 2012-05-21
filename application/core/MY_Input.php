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
	* Fetch the IP Address
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
	
	
	/**
	 * Validate IP Address
	 *
	 * @access	public
	 * @param	string
	 * @param	string	ipv4 or ipv6
	 * @return	bool
	 */
	public function valid_ip($ip, $which = '')
	{
		$which = strtolower($which);

		if ($which != 'ipv6' OR $which != 'ipv4')
		{
			if (strpos($ip, ':') !== FALSE)
			{
				$which = 'ipv6';
			}
			elseif (strpos($ip, '.') !== FALSE)
			{
				$which = 'ipv4';
			}
			else
			{
				return FALSE;
			}
		}

		$func = '_valid_'.$which;
		return $this->$func($ip);
	}

	// --------------------------------------------------------------------

	/**
	 * Validate IPv4 Address
	 *
	 * Updated version suggested by Geert De Deckere
	 *
	 * @access	protected
	 * @param	string
	 * @return	bool
	 */
	protected function _valid_ipv4($ip)
	{
		$ip_segments = explode('.', $ip);

		// Always 4 segments needed
		if (count($ip_segments) != 4)
		{
			return FALSE;
		}
		// IP can not start with 0
		if ($ip_segments[0][0] == '0')
		{
			return FALSE;
		}

		// Check each segment
		foreach ($ip_segments as $segment)
		{
			// IP segments must be digits and can not be
			// longer than 3 digits or greater then 255
			if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
			{
				return FALSE;
			}
		}

		return TRUE;
	}
	
	
	/**
	 * Validate IPv6 Address
	 *
	 * @access	protected
	 * @param	string
	 * @return	bool
	 */
	protected function _valid_ipv6($str)
	{
		// 8 groups, separated by :
		// 0-ffff per group
		// one set of consecutive 0 groups can be collapsed to ::

		$groups = 8;
		$collapsed = FALSE;

		$chunks = $this->_chunk_ipv6($str);

		// Rule out easy nonsense
		if (current($chunks) == ':' OR end($chunks) == ':')
		{
			return FALSE;
		}

		// PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as well
		if (strpos(end($chunks), '.') !== FALSE)
		{
			$ipv4 = array_pop($chunks);

			if ( ! $this->_valid_ipv4($ipv4))
			{
				return FALSE;
			}

			$groups--;
		}

		while ($seg = array_pop($chunks))
		{
			if ($seg[0] == ':')
			{
				if (--$groups == 0)
				{
					return FALSE;	// too many groups
				}

				if (strlen($seg) > 2)
				{
					return FALSE;	// long separator
				}

				if ($seg == '::')
				{
					if ($collapsed)
					{
						return FALSE;	// multiple collapsed
					}

					$collapsed = TRUE;
				}
			}
			elseif (preg_match("/[^0-9a-f]/i", $seg) OR strlen($seg) > 4)
			{
				return FALSE; // invalid segment
			}
		}

		return $collapsed OR $groups == 1;
	}

	// --------------------------------------------------------------------

	/**
	 * Break apart IPv6 Address
	 *
	 * Breaks address into blocks of consecutive colons and other strings
	 * I have a weird feeling this could be better.
	 *
	 * @access	protected
	 * @param	string
	 * @return	array
	 */
	protected function _chunk_ipv6($str)
	{
		$chunk_str = '';
		$chunks = array();

		$i = 0;
		$l = strlen($str);

		$in_sep = FALSE;	// in a separator or in a segment?

		while ($i < $l)
		{
			$chr = $str[$i++];

			if (($chr == ':') != $in_sep)
			{
				$chunks[] = $chunk_str;
				$chunk_str = '';

				$in_sep = ! $in_sep;
			}

			$chunk_str .= $chr;
		}

		$chunks[] = $chunk_str;

		return array_filter($chunks);
	}
	
	
}