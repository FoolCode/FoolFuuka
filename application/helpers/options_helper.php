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
 * Set selected boards' shortname
 */
if (!function_exists('set_selected_board'))
{
	function set_selected_board($name)
	{
		$CI = & get_instance();
		if (isset($CI->fu_board_obj))
			return $CI->fu_board_obj;
		$board = new Board();
		$board->where('shortname', $name)->get();
		if($board->result_count() == 0)
		{
			// what the hell is going on? abort
			show_404();
		}
		$CI->fu_board_obj = $board;
		return $board;
	}
}


if (!function_exists('generate_file_path'))
{
	function generate_file_path($path)
	{
		$path = explode("/", $path);
		$depth = 0; $recursive = array();

		while ($depth < count($path))
		{
			$recursive[] = $path[$depth];

			if ($path[$depth] != "")
			{
				if (!file_exists(implode("/", $recursive)))
				{
					mkdir(implode("/", $recursive));
				}
			}
			$depth++;
		}
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


/**
 * Parse BBCode
 */
function parse_bbcode($string)
{
	$CI = & get_instance();
	require_once(FCPATH . "assets/stringparser-bbcode/library/stringparser_bbcode.class.php");

	$bbcode = new StringParser_BBCode();
	$bbcode->addCode('spoiler', 'simple_replace', NULL, array('start_tag' => '<span class="spoiler">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('code', 'simple_replace', NULL, array('start_tag' => '<code>', 'end_tag' => '</code>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('sub', 'simple_replace', NULL, array('start_tag' => '<sub>', 'end_tag' => '</sub>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('sup', 'simple_replace', NULL, array('start_tag' => '<sup>', 'end_tag' => '</sup>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('b', 'simple_replace', NULL, array('start_tag' => '<b>', 'end_tag' => '</b>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('i', 'simple_replace', NULL, array('start_tag' => '<em>', 'end_tag' => '</em>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('m', 'simple_replace', NULL, array('start_tag' => '<tt class="code">', 'end_tag' => '</tt>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('o', 'simple_replace', NULL, array('start_tag' => '<span class="overline">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('s', 'simple_replace', NULL, array('start_tag' => '<span class="strikethrough">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('u', 'simple_replace', NULL, array('start_tag' => '<span class="underline">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'), array());
	$bbcode->addCode('banned:lit', 'simple_replace', NULL, array('start_tag' => '', 'end_tag' => ''), 'inline', array('block', 'inline'), array());

	if($CI->fu_theme == 'fuuka' || $CI->fu_theme == 'yotsuba')
	{
		$bbcode->addCode('moot', 'simple_replace', NULL, array('start_tag' => '<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">', 'end_tag' => '</div>'), 'inline', array('block', 'inline'), array());
	}
	else
	{
		$bbcode->addCode('moot', 'simple_replace', NULL, array('start_tag' => '', 'end_tag' => ''), 'inline', array('block', 'inline'), array());		
	}
	
	
	return $bbcode->parse($string);
}


/**
 * Parse Posted Data
 */
function check_commentdata($data = array(), $hash = FALSE)
{
	require_once(FCPATH . "assets/anti-spam/nospam.php");

	$nospam = new NoSpam();
	$nospam->compile_spam_database();

	return $nospam->is_spam($data, $hash);
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


function compress_html()
{
	$CI =& get_instance();
	$buffer = $CI->output->get_output();

	$search = array(
		'/\>[^\S ]+/s',    //strip whitespaces after tags, except space
		'/[^\S ]+\</s',    //strip whitespaces before tags, except space
		'/(\s)+/s'    // shorten multiple whitespace sequences
		);
	$replace = array(
		'> ',
		' <',
		'\\1'
		);
	$buffer = preg_replace($search, $replace, $buffer);

	$CI->output->set_output($buffer);
	$CI->output->_display();
}