<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class MY_Input extends CI_Input
{
	
	function __construct()
	{
		parent::__construct();
	}
	
	function set_cookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = FALSE)
	{
		if (is_array($name))
		{
			// always leave 'name' in last place, as the loop will break otherwise, due to $$item
			foreach (array('value', 'expire', 'domain', 'path', 'prefix', 'secure', 'name') as $item)
			{
				if (isset($name[$item]))
				{
					$$item = $name[$item];
				}
			}
		}

		return parent::set_cookie($name, $value, $expire, ($domain)?:$this->get_cookie_domain(), $path, $prefix, $secure);
	}
	
	function get_cookie_domain()
	{
		// if we enable subdomain control we need the cookies to be settable across domains
		if(defined('FOOL_SUBDOMAINS_ENABLE'))
		{
			$pieces = explode('.', $_SERVER['HTTP_HOST']);

			if(!FOOL_SUBDOMAINS_DEFAULT)
			{
				// we're on a top level domain, nice
				return '.' . $_SERVER['HTTP_HOST'];
			}

			$subdomain_levels = explode('.', trim(FOOL_SUBDOMAINS_DEFAULT,'.'));
			foreach($subdomain_levels as $s)
			{
				array_shift($pieces);
			}

			return '.' . implode('.', $pieces);
		}

		// empty to use default codeigniter cookies solution
		return '';
	}
	
}