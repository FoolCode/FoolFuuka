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
	
}