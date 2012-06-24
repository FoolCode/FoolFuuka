<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Fetch an item from the COOKIE array
 *
 * @access	public
 * @param	string
 * @param	bool
 * @return	mixed
 */
if ( ! function_exists('get_cookie'))
{
	function get_cookie($index = '', $xss_clean = FALSE)
	{
		$CI =& get_instance();

		return $CI->input->cookie($index, $xss_clean);
	}
}
