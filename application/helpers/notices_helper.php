<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!function_exists('get_notices'))
{
	/**
	 * Returns the notices with the Twitter Bootstrap notices formatting, and unsets
	 * the array lines from the flash
	 */
	function get_notices()
	{
		$CI = & get_instance();
		
		// sometimes is not available, like during installation
		if(isset($CI->session))
		{
			$flash_notices = is_array($CI->session->flashdata('notices'))?$CI->session->flashdata('notices'):array();
		}
		else
		{
			$flash_notices = array();
		}
		$merge = array_merge($CI->notices, $CI->flash_notice_data, $flash_notices);
		$CI->flash_notice_data = '';
		
		if(isset($CI->session))
			$CI->session->set_flashdata('notices', array());
		
		$echo = '';
		foreach ($merge as $key => $value)
		{
			$echo .= '<div class="alert alert-' . $value["type"] . ' fade in" data-alert="alert"><a class="close" data-dismiss="alert">×</a>' . $value["message"] . '</div>';
		}
		return $echo;
	}


}

if (!function_exists('clear_notices'))
{
	/**
	 * Flushes flashdata and standard notices
	 */
	function clear_notices()
	{
		$CI = & get_instance();
		unset($CI->notices);
		$CI->session->set_flashdata('notices', array());
	}


}

if (!function_exists('set_notice'))
{
	/**
	 * Sets a notice in the currently loading page. Can be used for multiple notices
	 * Notice types: error, warn, notice
	 */
	function set_notice($type, $message, $data = FALSE)
	{
		if ($type == 'warn')
			$type = 'warning';
		if ($type == 'notice')
			$type = 'success';

		$CI = & get_instance();
		$CI->notices[] = array("type" => $type, "message" => $message, "data" => $data);

		if ($CI->input->is_cli_request())
		{
			echo '[' . $type . '] ' . $message . PHP_EOL;
		}
	}


}

if (!function_exists('flash_notice'))
{
	/**
	 * Sets a notice in the next loaded page. Can be used for multiple notices
	 * Notice types: error, warn, notice
	 */
	function flash_notice($type, $message)
	{
		if ($type == 'warn')
			$type = 'warning';
		if ($type == 'notice')
			$type = 'success';

		$CI = & get_instance();
		$CI->flash_notice_data[] = array('type' => $type, 'message' => $message);
		$CI->session->set_flashdata('notices', $CI->flash_notice_data);
	}


}


if(!function_exists('cli_notice'))
{
	/**
	 * Sends a message to the command line if the user is running in command line mode
	 * 
	 * @param string $type can be notice/success, error, dberror
	 * @param string $message  the message to be printed
	 * @param boolean $newline if true a new line won't be created
	 * @return boolean false if not cli, true if sent
	 */
	function cli_notice($type, $message, $newline = TRUE)
	{
		$CI = & get_instance();
		
		if(!$CI->input->is_cli_request())
		{
			return FALSE;
		}

		if($type == 'error')
		{
			echo '[error] ';
		}
		
		if($type == 'dberror')
		{
			echo '[database error] ';
		}
		
		echo $message;
		
		if($newline)
		{
			echo PHP_EOL;
		}
		
		return TRUE;
	}
}