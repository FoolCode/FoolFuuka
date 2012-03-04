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


if (!function_exists('urlsafe_b64encode'))
{
	function urlsafe_b64encode($string)
	{
		$result = base64_encode($string);
		return str_replace(array('+', '/'), array('-', '_'), $result);
	}
}


if (!function_exists('urlsafe_b64decode'))
{
	function urlsafe_b64decode($string)
	{
		$result = str_replace(array('-', '_'), array('+', '/'), $string);
		return base64_decode($result);
	}
}


/**
 * Return selected radix' shortname
 *
 * @author Woxxy
 */
if (!function_exists('get_selected_radix'))
{

	function get_selected_radix()
	{
		$CI = & get_instance();
		return $CI->radix->get_selected();
	}


}


if (!function_exists('generate_file_path'))
{
	function generate_file_path($path)
	{
		$path = explode("/", $path);
		$depth = 0;
		$recursive = array();

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
function parse_bbcode($string, $archive = FALSE)
{
	$CI = & get_instance();
	require_once(FCPATH . "assets/stringparser-bbcode/library/stringparser_bbcode.class.php");

	$bbcode = new StringParser_BBCode();
	$bbcode->addCode('code', 'simple_replace', NULL, array('start_tag' => '<code>', 'end_tag' => '</code>'), 'code', array('block', 'inline'), array());
	$bbcode->addCode('spoiler', 'simple_replace', NULL, array('start_tag' => '<span class="spoiler">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'), array('code'));
	$bbcode->addCode('sub', 'simple_replace', NULL, array('start_tag' => '<sub>', 'end_tag' => '</sub>'), 'inline', array('block', 'inline'), array('code'));
	$bbcode->addCode('sup', 'simple_replace', NULL, array('start_tag' => '<sup>', 'end_tag' => '</sup>'), 'inline', array('block', 'inline'), array('code'));
	$bbcode->addCode('b', 'simple_replace', NULL, array('start_tag' => '<b>', 'end_tag' => '</b>'), 'inline', array('block', 'inline'), array('code'));
	$bbcode->addCode('i', 'simple_replace', NULL, array('start_tag' => '<em>', 'end_tag' => '</em>'), 'inline', array('block', 'inline'), array('code'));
	$bbcode->addCode('m', 'simple_replace', NULL, array('start_tag' => '<tt class="code">', 'end_tag' => '</tt>'), 'inline', array('block', 'inline'), array('code'));
	$bbcode->addCode('o', 'simple_replace', NULL, array('start_tag' => '<span class="overline">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'), array('code'));
	$bbcode->addCode('s', 'simple_replace', NULL, array('start_tag' => '<span class="strikethrough">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'), array('code'));
	$bbcode->addCode('u', 'simple_replace', NULL, array('start_tag' => '<span class="underline">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'), array('code'));

	if($archive)
	{
		if ($CI->fu_theme == 'fuuka' || $CI->fu_theme == 'yotsuba')
		{
			$bbcode->addCode('moot', 'simple_replace', NULL, array('start_tag' => '<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">', 'end_tag' => '</div>'), 'inline', array('block', 'inline'), array());
		}
		else
		{
			$bbcode->addCode('moot', 'simple_replace', NULL, array('start_tag' => '', 'end_tag' => ''), 'inline', array('block', 'inline'), array());
		}
	}

	return $bbcode->parse($string);
}


if ( ! function_exists('auto_linkify'))
{
	function auto_linkify($str, $type = 'both', $popup = FALSE)
	{
		if ($type != 'email')
		{
			if (preg_match_all("#(^|\s|\(|\])((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $str, $matches))
			{
				$pop = ($popup == TRUE) ? " target=\"_blank\" " : "";

				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$period = '';
					if (preg_match("|\.$|", $matches['6'][$i]))
					{
						$period = '.';
						$matches['6'][$i] = substr($matches['6'][$i], 0, -1);
					}

					$str = str_replace($matches['0'][$i],
										$matches['1'][$i].'<a href="http'.
										$matches['4'][$i].'://'.
										$matches['5'][$i].
										preg_replace('/[[\/\!]*?[^\[\]]*?]/si', '', $matches['6'][$i]).'"'.$pop.'>http'.
										$matches['4'][$i].'://'.
										$matches['5'][$i].
										$matches['6'][$i].'</a>'.
										$period, $str);
				}
			}
		}

		if ($type != 'url')
		{
			if (preg_match_all("/([a-zA-Z0-9_\.\-\+]+)@([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-\.]*)/i", $str, $matches))
			{
				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$period = '';
					if (preg_match("|\.$|", $matches['3'][$i]))
					{
						$period = '.';
						$matches['3'][$i] = substr($matches['3'][$i], 0, -1);
					}

					$str = str_replace($matches['0'][$i], safe_mailto($matches['1'][$i].'@'.$matches['2'][$i].'.'.$matches['3'][$i]).$period, $str);
				}
			}
		}

		return $str;
	}
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
		WHERE ip = INET_ATON(' . $CI->db->escape($ip) . ')
		LIMIT 0,1;
	');
	if ($query->num_rows() > 0)
		print_r($query->result());
	return $query->num_rows() > 0;
}


function compress_html()
{
	$CI = & get_instance();
	$buffer = $CI->output->get_output();

	$search = array(
		'/\>[^\S ]+/s', //strip whitespaces after tags, except space
		'/[^\S ]+\</s', //strip whitespaces before tags, except space
		'/(\s)+/s'	// shorten multiple whitespace sequences
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


/**
 * Convert an IP address from presentation to decimal(39,0) format suitable for storage in MySQL
 *
 * @param string $ip_address An IP address in IPv4, IPv6 or decimal notation
 * @return string The IP address in decimal notation
 */
function inet_ptod($ip_address)
{
    // IPv4 address
    if (strpos($ip_address, ':') === false && strpos($ip_address, '.') !== false) {
        $ip_address = '::' . $ip_address;
    }

    // IPv6 address
    if (strpos($ip_address, ':') !== false) {
        $network = inet_pton($ip_address);
        $parts = unpack('N*', $network);

        foreach ($parts as &$part) {
                if ($part < 0) {
                        $part = bcadd((string) $part, '4294967296');
                }

                if (!is_string($part)) {
                        $part = (string) $part;
                }
        }

        $decimal = $parts[4];
        $decimal = bcadd($decimal, bcmul($parts[3], '4294967296'));
        $decimal = bcadd($decimal, bcmul($parts[2], '18446744073709551616'));
        $decimal = bcadd($decimal, bcmul($parts[1], '79228162514264337593543950336'));

        return $decimal;
    }

    // Decimal address
    return $ip_address;
}

/**
 * Convert an IP address from decimal format to presentation format
 *
 * @param string $decimal An IP address in IPv4, IPv6 or decimal notation
 * @return string The IP address in presentation format
 */
function inet_dtop($decimal)
{
    // IPv4 or IPv6 format
    if (strpos($decimal, ':') !== false || strpos($decimal, '.') !== false) {
        return $decimal;
    }

    // Decimal format
    $parts = array();
    $parts[1] = bcdiv($decimal, '79228162514264337593543950336', 0);
    $decimal = bcsub($decimal, bcmul($parts[1], '79228162514264337593543950336'));
    $parts[2] = bcdiv($decimal, '18446744073709551616', 0);
    $decimal = bcsub($decimal, bcmul($parts[2], '18446744073709551616'));
    $parts[3] = bcdiv($decimal, '4294967296', 0);
    $decimal = bcsub($decimal, bcmul($parts[3], '4294967296'));
    $parts[4] = $decimal;

    foreach ($parts as &$part) {
        if (bccomp($part, '2147483647') == 1) {
                $part = bcsub($part, '4294967296');
        }

        $part = (int) $part;
    }

    $network = pack('N4', $parts[1], $parts[2], $parts[3], $parts[4]);
    $ip_address = inet_ntop($network);

    // Turn IPv6 to IPv4 if it's IPv4
    if (preg_match('/^::\d+.\d+.\d+.\d+$/', $ip_address)) {
        return substr($ip_address, 2);
    }

    return $ip_address;
}