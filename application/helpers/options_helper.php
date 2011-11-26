<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Function to get single options from the preferences database
 * 
 * @param string $option the code of the option
 * @author Woxxy
 * @return string the option
 */
if (!function_exists('get_setting'))
{

	function get_setting($option)
	{
		$CI = & get_instance();
		$array = $CI->fs_options;
		if (isset($array[$option]))
			return $array[$option];
		return FALSE;
	}


}

/**
 * Loads variables from database for get_setting()
 * 
 * @author Woxxy
 */
if (!function_exists('load_settings'))
{

	function load_settings()
	{
		$CI = & get_instance();
		$array = $CI->db->get('preferences')->result_array();
		$result = array();
		foreach ($array as $item)
		{
			$result[$item['name']] = $item['value'];
		}
		$CI->fs_options = $result;
	}


}

if (!function_exists('fuuka_htmlescape'))
{
	function fuuka_htmlescape($input)
	{
		$input = preg_replace('/[\x80]+/S', '', $input);
		$result = remove_invisible_characters($input);
		return htmlentities($input, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
	}
}


/**
 * Return selected boards' shortname
 * 
 * @author Woxxy
 */
if (!function_exists('get_selected_board'))
{

	function get_selected_board()
	{
		$CI = & get_instance();
		if (isset($CI->fu_board_obj))
			return $CI->fu_board_obj;
		$board = new Board();
		$board->where('shortname', $CI->fu_board)->get();
		if($board->result_count() == 0)
		{
			// what the hell is going on? abort
			show_404();
		}
		$CI->fu_board_obj = $board;
		return $board;
	}


}

/**
 * Caches in a variable and returns the home team's object
 * 
 * @author Woxxy
 * @return object home team
 */
if (!function_exists('get_home_team'))
{

	function get_home_team()
	{
		$CI = & get_instance();
		if (isset($CI->fs_loaded->home_team))
			return $CI->fs_loaded->home_team;
		$hometeam = get_setting('fs_gen_default_team');
		$team = new Team();
		$team->where('name', $hometeam)->limit(1)->get();
		if ($team->result_count() < 1)
		{
			$team = new Team();
			$team->limit(1)->get();
		}

		$CI->fs_loaded->home_team = $team;
		return $team;
	}


}

if (!function_exists('parse_irc'))
{

	function parse_irc($string)
	{
		if (substr($string, 0, 1) == '#')
		{
			$echo = 'irc://';
			$at = strpos($string, '@');
			$echo .= substr($string, $at + 1);
			$echo .= '/' . substr($string, 1, $at - 1);
			return $echo;
		}
		return $string;
	}


}


/**
 * Locate ImageMagick and determine if it has been installed or not. 
 */
function find_imagick()
{
	$CI = & get_instance();
	if (isset($CI->fs_imagick->available))
	{
		return $CI->fs_imagick->available;
	}

	$CI->fs_imagick->exec = FALSE;
	$CI->fs_imagick->found = FALSE;
	$CI->fs_imagick->available = FALSE;
	$ini_disabled = explode(',', ini_get('disable_functions'));
	if (ini_get('safe_mode') || !in_array('exec', $ini_disabled))
	{
		$CI->fs_imagick->exec = TRUE;
		$imagick_path = get_setting('fs_serv_imagick_path') ? get_setting('fs_serv_imagick_path') : '/usr/bin';

		if (!preg_match("/convert$/i", $imagick_path))
		{
			$imagick_path = rtrim($imagick_path, '/') . '/';

			$imagick_path .= 'convert';
		}

		if (@file_exists($imagick_path) || @file_exists($imagick_path . '.exe'))
		{
			$CI->fs_imagick->found = $imagick_path;
		}
		else
		{
			return FALSE;
		}

		exec($imagick_path . ' -version', $result);
		if (preg_match('/ImageMagick/i', $result[0]))
		{
			$CI->fs_imagick->available = TRUE;
			return TRUE;
		}
	}
	return FALSE;
}


/**
 * Checks that the call is made from Ajax
 * 
 * @author Woxxy
 * @return bool true if ajax request
 */
function isAjax()
{
	return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
}


function current_url_real()
{
	$pageURL = (isset($_SERVER["HTTPS"]) && @$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
	if ($_SERVER["SERVER_PORT"] != "80")
	{
		$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
	}
	else
	{
		$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}


/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 80px [ 1 - 512 ]
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param boole $img True to return a complete IMG tag False for just the URL
 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
 * @return String containing either just a URL or a complete image tag
 * @source http://gravatar.com/site/implement/images/php/
 */
function get_gravatar($email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array())
{
	$url = 'http://www.gravatar.com/avatar/';
	$url .= md5(strtolower(trim($email)));
	$url .= "?s=$s&d=$d&r=$r";
	if ($img)
	{
		$url = '<img src="' . $url . '"';
		foreach ($atts as $key => $val)
			$url .= ' ' . $key . '="' . $val . '"';
		$url .= ' />';
	}
	return $url;
}


function icons($num, $size = '32', $icons = 'sweeticons2')
{
	return site_url() . 'assets/icons/' . $icons . '/' . $size . '/' . $num . '.png';
}


function is_natural($str)
{
	return (bool) preg_match('/^[0-9]+$/', $str);
}


function relative_date($time)
{

	$today = strtotime(date('M j, Y'));

	$reldays = ($time - $today) / 86400;

	if ($reldays >= 0 && $reldays < 1)
	{
		return _('Today');
	}
	else if ($reldays >= 1 && $reldays < 2)
	{
		return _('Tomorrow');
	}
	else if ($reldays >= -1 && $reldays < 0)
	{
		return _('Yesterday');
	}

	/* THIS SCREWS UP WITH THE GETTEXT
	 * @todo fix the relative days' gettext
	  if (abs($reldays) < 7) {
	  if ($reldays > 0) {
	  $reldays = floor($reldays);
	  return 'In ' . $reldays . ' day' . ($reldays != 1 ? 's' : '');
	  }
	  else {
	  $reldays = abs(floor($reldays));
	  return $reldays . ' day' . ($reldays != 1 ? 's' : '') . ' ago';
	  }
	  }
	 */

	if (abs($reldays) < 182)
	{
		return date('jS F', $time ? $time : time());
	}
	else
	{
		return date('jS F, Y', $time ? $time : time());
	}
}


/**
 * 
 */
function HTMLpurify($dirty_html, $set = 'default')
{
	if (is_array($dirty_html))
	{
		foreach ($dirty_html as $key => $val)
		{
			$dirty_html[$key] = purify($val);
		}

		return $dirty_html;
	}

	if (trim($dirty_html) === '')
	{
		return $dirty_html;
	}

	require_once(FCPATH . "assets/htmlpurifier/library/HTMLPurifier.auto.php");
	require_once(FCPATH . "assets/htmlpurifier/library/HTMLPurifier.func.php");

	$config = HTMLPurifier_Config::createDefault();
	if (!file_exists('content/cache/HTMLPurifier'))
		mkdir('content/cache/HTMLPurifier');
	$config->set('HTML.Doctype', 'XHTML 1.0 Strict');
	$config->set('Cache.SerializerPath', FCPATH . 'content/cache/HTMLPurifier');



	switch ($set)
	{
		case 'default':
			break;
		case 'unallowed':
			$config->set('HTML.AllowedElements', '');
			break;
	}
	return HTMLPurifier($dirty_html, $config);
}



/**
 * Compare with the local IP list if the IP is a possible spammer
 * 
 * @param string $ip
 * @return bool  Returns true if the IP is in the stopforumspam repository
 */
function check_stopforumspam_ip($ip)
{
	$CI = & get_instance();
	$query = $CI->db->query('
		SELECT ip FROM ' . $CI->db->protect_identifiers('stopforumspam', TRUE) . ' 
		WHERE ip = INET_ATON('.$CI->db->escape($ip).')
		LIMIT 0,1;
	');
	if($query->num_rows() > 0)
		print_r($query->result());
	return $query->num_rows() > 0;
}