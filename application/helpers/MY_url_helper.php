<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


/**
 * This function generates full url used for sub-domains.
 * MODIFIED VERSION OF CODEIGNITER'S FUNCTION site_url()
 */
function site_url($uri = '')
{
	$CI = & get_instance();

	if ($uri == '')
	{
		return $CI->config->slash_item('base_url') . $CI->config->item('index_page');
	}
	
	$base_url = $CI->config->slash_item('base_url');

	if(!is_array($uri))
	{
		$uri = explode('/', $uri);
	}

	if (is_array($uri) && strpos($uri[0], '@') !== FALSE)
	{
		// checks used when sub-domain system is active
		if (defined('FOOL_SUBDOMAINS_ENABLE') && FOOL_SUBDOMAINS_ENABLE == TRUE)
		{
			$hostname = explode('.', $_SERVER['HTTP_HOST']);

			if (count($hostname) > 2)
			{
				// the following forces the suggested subdomain
				
				if (strpos('@system', $uri[0]) !== FALSE)
				{
					$hostname[0] = rtrim(FOOL_SUBDOMAINS_SYSTEM, '.');
				}

				if (strpos('@board', $uri[0]) !== FALSE)
				{
					$hostname[0] = rtrim(FOOL_SUBDOMAINS_BOARD, '.');
				}

				if (strpos('@archive', $uri[0]) !== FALSE)
				{
					$hostname[0] = rtrim(FOOL_SUBDOMAINS_ARCHIVE, '.');
				}
				
				// autodetection for board and archive
				if(strpos(('@radix'), $uri[0]) !== FALSE)
				{
					if(isset($uri[1]) && isset($CI->radix))
					{
						$found = FALSE;
						foreach($CI->radix->get_all() as $radix)
						{
							if($uri[1] == $radix->shortname)
							{
								if($radix->archive == 1)
								{
									$hostname[0] = rtrim(FOOL_SUBDOMAINS_ARCHIVE, '.');
								}
								else 
								{
									$hostname[0] = rtrim(FOOL_SUBDOMAINS_BOARD, '.');
								}

								$found = TRUE;
							}
						}

						if(!$found)
						{
							// plugins etc
							$hostname[0] = rtrim(FOOL_SUBDOMAINS_DEFAULT, '.');
						}
					}
					else
					{
						// homepage go to default
						$hostname[0] = rtrim(FOOL_SUBDOMAINS_DEFAULT, '.');
					}
				}
				
				if (strpos('@default', $uri[0]) !== FALSE)
				{
					$hostname[0] = rtrim(FOOL_SUBDOMAINS_DEFAULT, '.');
				}
			}

			$base_url = str_replace($_SERVER['HTTP_HOST'], implode('.', $hostname), $base_url);
		}

		// get rid of the @'d parts
		array_shift($uri);
	}
	else if(defined('FOOL_SUBDOMAINS_ENABLE') && FOOL_SUBDOMAINS_ENABLE == TRUE)
	{
		$hostname = explode('.', $_SERVER['HTTP_HOST']);
		
		if(count($hostname) > 2)
		{
			
			// inside the admin controller normal site_url will return SYSTEM subdomain unless otherwise specified
			if ($CI instanceof Admin_Controller || $CI instanceof API_Controller)
			{
				$hostname[0] = rtrim(FOOL_SUBDOMAINS_SYSTEM, '.');
			}
			else if ($CI instanceof Public_Controller)
			{
				// out of the admin controller normal site_url will return BOARD/ARCHIVE subdomain unless otherwise specified

				// we might be smart enough to guess if it's an archive or not!
				// site_url() is used also in radix, so until radix is constructed this section can't be used
				// or horrible things will happen

				if(isset($uri[1]) && isset($CI->radix))
				{
					$found = FALSE;
					foreach($CI->radix->get_all() as $radix)
					{
						if($uri[1] == $radix->shortname)
						{
							if($radix->archive == 1)
							{
								$hostname[0] = rtrim(FOOL_SUBDOMAINS_ARCHIVE, '.');
							}
							else 
							{
								$hostname[0] = rtrim(FOOL_SUBDOMAINS_BOARD, '.');
							}

							$found = TRUE;
						}
					}

					if(!$found)
					{
						// plugins etc
						$hostname[0] = rtrim(FOOL_SUBDOMAINS_DEFAULT, '.');
					}
				}
				else
				{
					// homepage go to default
					$hostname[0] = rtrim(FOOL_SUBDOMAINS_DEFAULT, '.');
				}
			}

			$base_url = str_replace($_SERVER['HTTP_HOST'], implode('.', $hostname), $base_url);
		}
	}

	if ($CI->config->item('enable_query_strings') == FALSE)
	{
		if(count($uri))
		{
			$suffix = ($CI->config->item('url_suffix') == FALSE) ? '' : $CI->config->item('url_suffix');
			return $base_url . $CI->config->slash_item('index_page') . _protected_uri_string($uri) . $suffix;
		}
		else
		{
			// empty $uri, but with an @ modifier, else we'd get an extra final slash
			return $base_url . $CI->config->item('index_page');
		}
	}
	else
	{
		return $base_url . $CI->config->slash_item('index_page') . '?' . _protected_uri_string($uri);
	}
}

function _protected_uri_string($uri)
{
	$CI = & get_instance();

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
