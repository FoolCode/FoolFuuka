<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


/**
 * This function generates full url used for sub-domains.
 * MODIFIED VERSION OF CODEIGNITER'S FUNCTION site_url()
 */
function site_url($uri = '')
{
	$CI =& get_instance();

	if ($uri == '')
	{
		return $CI->config->slash_item('base_url') . $CI->config->item('index_page');
	}

	$base_url = $CI->config->slash_item('base_url');


	if (is_array($uri) && strpos($uri[0], '@') !== FALSE)
	{
		// checks used when sub-domain system is active
		if (defined('FOOL_SUBDOMAINS_ENABLE') && FOOL_SUBDOMAINS_ENABLE == TRUE)
		{
			$hostname = explode('.', $_SERVER['HTTP_HOST']);

			if (count($hostname) > 2)
			{
				if (strpos('@'.FOOL_SUBDOMAINS_SYSTEM, $uri[0]) !== FALSE)
				{
					$hostname[0] = rtrim(FOOL_SUBDOMAINS_SYSTEM, '.');
				}

				if (strpos('@'.FOOL_SUBDOMAINS_BOARD, $uri[0]) !== FALSE)
				{
					$hostname[0] = rtrim(FOOL_SUBDOMAINS_BOARD, '.');
				}

				if (strpos('@'.FOOL_SUBDOMAINS_ARCHIVE, $uri[0]) !== FALSE)
				{
					$hostname[0] = rtrim(FOOL_SUBDOMAINS_ARCHIVE, '.');
				}
			}

			$base_url = str_replace($_SERVER['HTTP_HOST'], implode('.', $hostname), $base_url);
		}

		array_shift($uri);
	}

	if ($CI->config->item('enable_query_strings') == FALSE)
	{
		$suffix = ($CI->config->item('url_suffix') == FALSE) ? '' : $CI->config->item('url_suffix');
		return $base_url . $CI->config->slash_item('index_page') . _protected_uri_string($uri) . $suffix;
	}
	else
	{
		return $base_url . $CI->config->slash_item('index_page') . '?' . _protected_uri_string($uri);
	}
}

function _protected_uri_string($uri)
{
	$CI =& get_instance();

	if ($CI->config->item('enable_query_strings') == FALSE)
	{
		if (is_array($uri))
		{
			$uri = implode('/', $uri);
		}

		$uri = trim($uri, '/');
	}
	else
	{
		if (is_array($uri))
		{
			$i = 0;
			$str = '';
			foreach ($uri as $key => $val)
			{
				$prefix = ($i == 0) ? '' : '&';
				$str .= $prefix . $key . '=' . $val;
				$i++;
			}
			$uri = $str;
		}
	}

	return $uri;
}
