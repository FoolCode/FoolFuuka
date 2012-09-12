<?php

namespace Foolfuuka\Model;

class CommentException extends \FuelException {}

class CommentDeleteWrongPassException extends CommentException {}

class CommentSendingException extends CommentException {}
class CommentSendingDuplicateException extends CommentSendingException {}
class CommentSendingThreadWithoutMediaException extends CommentSendingException {}
class CommentSendingUnallowedCapcodeException extends CommentSendingException {}
class CommentSendingNoDelPassException extends CommentSendingException {}
class CommentSendingDisplaysEmptyException extends CommentSendingException {}
class CommentSendingTooManyLinesException extends CommentSendingException {}
class CommentSendingTooManyCharactersException extends CommentSendingException {}
class CommentSendingSpamException extends CommentSendingException {}
class CommentSendingTimeLimitException extends CommentSendingException {}
class CommentSendingSameCommentException extends CommentSendingException {}
class CommentSendingImageInGhostException extends CommentSendingException {}
class CommentSendingBannedException extends CommentSendingException {}

class Comment extends \Model\Model_Base
{

	/**
	 * Array of post numbers found in the database
	 *
	 * @var array
	 */
	protected static $_posts = array();

	/**
	 * Array of backlinks found in the posts
	 *
	 * @var type
	 */
	public static $_backlinks_arr = array();

	// global variables used for processing due to callbacks

	/**
	 * If the backlinks must be full URLs or just the hash
	 * Notice: this is global because it's used in a PHP callback
	 *
	 * @var bool
	 */
	protected $_backlinks_hash_only_url = false;

	protected $current_board_for_prc = null;


	public $_controller_method = 'thread';

	/**
	 * Sets the callbacks so they return URLs good for realtime updates
	 * Notice: this is global because it's used in a PHP callback
	 *
	 * @var type
	 */
	protected $_realtime = false;
	protected $_clean = true;
	protected $_force_entries = false;
	protected $_forced_entries = array(
		'title_processed', 'name_processed', 'email_processed', 'trip_processed', 'media_orig_processed',
		'poster_hash_processed', 'original_timestamp', 'fourchan_date', 'comment_sanitized', 
		'comment_processed', 'poster_country_name_processed'
	);

	public $board = null;

	public $doc_id = 0;
	public $poster_ip = null;
	public $num = 0;
	public $subnum = 0;
	public $thread_num = 0;
	public $op = 0;
	public $timestamp = 0;
	public $timestamp_expired = 0;
	public $capcode = 'N';
	public $email = null;
	public $name = null;
	public $trip = null;
	public $title = null;
	public $comment = null;
	public $delpass = null;
	public $poster_hash = null;
	public $poster_country = null;

	public $media = null;
	public $extra = null;


	public function __get($name)
	{
		if ($name != 'comment_processed' && substr($name, -10) === '_processed')
		{
			$processing_name = substr($name, 0, strlen($name) - 10);
			return $this->$name = e(@iconv('UTF-8', 'UTF-8//IGNORE', $this->$processing_name));
		}

		switch ($name)
		{
			case 'original_timestamp':
				$this->original_timestamp = $this->timestamp;
				return $this->original_timestamp;
			case 'fourchan_date':
				return $this->fourchan_date = gmdate('n/j/y(D)G:i', $this->original_timestamp);
			case 'comment':
				// we always used comment_processed, this is an exception
				return $this->comment_sanitized = @iconv('UTF-8', 'UTF-8//IGNORE', $this->comment);
			case 'comment_processed':
				return $this->comment_processed = @iconv('UTF-8', 'UTF-8//IGNORE', $this->process_comment());
			case 'backlinks':
				return $this->backlinks = $this->get_backlinks();
			case 'formatted':
				return $this->formatted = $this->build_comment();
			case 'reports':
				if (\Auth::has_access('comment.reports'))
				{
					return $this->reports = \Report::get_by_doc_id($this->board, $this->doc_id);
				}
		}

		return null;
	}


	public static function forge($post, $board, $options = array())
	{
		if (is_array($post))
		{
			$array = array();
			foreach ($post as $p)
			{
				$array[] = static::forge($p, $board, $options);
			}

			return $array;
		}

		return new Comment($post, $board, $options);
	}


	public static function forge_for_api($post, $board, $api, $options = array())
	{
		if (is_array($post))
		{
			$array = array();
			foreach ($post as $p)
			{
				$array[] = static::forge_for_api($p, $board, $api, $options);
			}

			return $array;
		}

		$comment = new Comment($post, $board, $options);

		$fields = $comment->_forced_entries;

		if (isset($api['theme']) && $api['theme'] !== null)
		{
			$fields[] = 'formatted';
		}

		foreach ($fields as $field)
		{
			$comment->$field;
		}
		
		// also spawn media variables
		if ($comment->media !== null)
		{
			// backwards compatibility with 4chan X
			foreach (Media::$_fields as $field)
			{
				if (!isset($comment->$field))
				{
					$comment->$field = $comment->media->$field;
				}
			}
			
			foreach (array(
				'preview_orig_processed', 
				'media_filename_processed', 
				'media_hash_processed',
				'media_link',
				'thumb_link'
			) as $field)
			{
				$comment->$field = $comment->media->{$field};
			}
		}
		

		if (isset($api['board']) && !$api['board'])
		{
			unset($comment->board);
		}

		// remove controller method
		unset($comment->_controller_method);

		// remove radix data
		unset($comment->extra->_radix);

		return $comment;
	}


	public function __construct($post, $board, $options = array())
	{
		//parent::__construct();

		$this->board = $board;

		if (\Auth::has_access('comment.reports'))
		{
			$this->_forced_entries[] = 'report_reason_processed';
		}

		$media_fields = Media::get_fields();
		$extra_fields = Extra::get_fields();
		$media = new \stdClass();
		$extra = new \stdClass();
		$do_media = false;
		$do_extra = false;

		foreach ($post as $key => $value)
		{
			if( ! in_array($key, $media_fields) && ! in_array($key, $extra_fields))
			{
				$this->$key = $value;
			}
			else if (in_array($key, $extra_fields))
			{
				$do_extra = true;
				$extra->$key = $value;
			}
			else
			{
				$do_media = true;
				$media->$key = $value;
			}
		}

		if ($do_media)
		{
			$this->media = Media::forge_from_comment($media, $this->board, $this->op);
		}
		else
		{
			$this->media = null;
		}

		$this->extra = Extra::forge($extra, $this->board);

		foreach ($options as $key => $value)
		{
			$this->{'_'.$key} = $value;
		}

		// format 4chan archive timestamp
		if ($this->board->archive)
		{
			// archives are in new york time
			$newyork = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('America/New_York'));
			$utc = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('UTC'));
			$diff = $newyork->diff($utc)->h;
			$this->timestamp = $this->timestamp + ($diff * 60 * 60);
		}

		if ($this->_clean)
		{
			$this->clean_fields();
		}

		if ($this->_prefetch_backlinks)
		{
			// to get the backlinks we need to get the comment processed
			$this->comment_processed;
		}

		if ($this->poster_country !== null)
		{
			$this->poster_country_name = \Config::get('geoip_codes.codes.'.strtoupper($this->poster_country));
		}

		$num = $this->num.($this->subnum ? ',' . $this->subnum : '');
		static::$_posts[$this->thread_num][] = $num;
	}


	/**
	 * Processes the comment, strips annoying data from moot, converts BBCode,
	 * converts > to greentext, >> to internal link, and >>> to crossboard link
	 *
	 * @param object $board
	 * @param object $post the database row for the post
	 * @return string the processed comment
	 */
	protected function p_process_comment()
	{
		// default variables
		$find = "'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i";
		$html = '\\1<span class="greentext">\\2</span>\\3';

		$html = \Plugins::run_hook('fu.comment_model.process_comment.greentext_result', array($html), 'simple');

		$comment = $this->comment;

		// this stores an array of moot's formatting that must be removed
		$special = array(
			'<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">',
			'<span style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">'
		);

		// remove moot's special formatting
		if ($this->capcode == 'A' && mb_strpos($comment, $special[0]) == 0)
		{
			$comment = str_replace($special[0], '', $comment);

			if (mb_substr($comment, -6, 6) == '</div>')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 6);
			}
		}

		if ($this->capcode == 'A' && mb_strpos($comment, $special[1]) == 0)
		{
			$comment = str_replace($special[1], '', $comment);

			if (mb_substr($comment, -10, 10) == '[/spoiler]')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 10);
			}
		}

		$comment = htmlentities($comment, ENT_COMPAT | ENT_IGNORE, 'UTF-8', false);

		// preg_replace_callback handle
		$this->current_board_for_prc = $this->board;

		// format entire comment
		$comment = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i",
			array(get_class($this), 'process_internal_links'), $comment);

		$comment = preg_replace_callback("'(&gt;&gt;&gt;(\/(\w+)\/(\d+(?:,\d+)?)?(\/?)))'i",
			array(get_class($this), 'process_crossboard_links'), $comment);

		$comment = preg_replace($find, $html, $comment);
		$comment = static::parse_bbcode($comment, ($this->board->archive && !$this->subnum));
		$comment = static::auto_linkify($comment, 'url', true);

		// additional formatting
		if ($this->board->archive && !$this->subnum)
		{
			// admin bbcode
			$admin_find = "'\[banned\](.*?)\[/banned\]'i";
			$admin_html = '<span class="banned">\\1</span>';

			$comment = preg_replace($admin_find, $admin_html, $comment);

			// literal bbcode
			$lit_find = array(
				"'\[banned:lit\]'i", "'\[/banned:lit\]'i",
				"'\[moot:lit\]'i", "'\[/moot:lit\]'i"
			);

			$lit_html = array(
				'[banned]', '[/banned]',
				'[moot]', '[/moot]'
			);

			$comment = preg_replace($lit_find, $lit_html, $comment);
		}

		return $this->comment_processed = nl2br(trim($comment));
	}


	protected static function parse_bbcode($str, $special_code, $strip = true)
	{
		$bbcode = new \StringParser_BBCode();

		$codes = array();

		// add list of bbcode for formatting
		$codes[] = array('code', 'simple_replace', null, array('start_tag' => '<code>', 'end_tag' => '</code>'), 'code',
			array('block', 'inline'), array());
		$codes[] = array('spoiler', 'simple_replace', null,
			array('start_tag' => '<span class="spoiler">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));
		$codes[] = array('sub', 'simple_replace', null, array('start_tag' => '<sub>', 'end_tag' => '</sub>'), 'inline',
			array('block', 'inline'), array('code'));
		$codes[] = array('sup', 'simple_replace', null, array('start_tag' => '<sup>', 'end_tag' => '</sup>'), 'inline',
			array('block', 'inline'), array('code'));
		$codes[] = array('b', 'simple_replace', null, array('start_tag' => '<b>', 'end_tag' => '</b>'), 'inline',
			array('block', 'inline'), array('code'));
		$codes[] = array('i', 'simple_replace', null, array('start_tag' => '<em>', 'end_tag' => '</em>'), 'inline',
			array('block', 'inline'), array('code'));
		$codes[] = array('m', 'simple_replace', null, array('start_tag' => '<tt class="code">', 'end_tag' => '</tt>'),
			'inline', array('block', 'inline'), array('code'));
		$codes[] = array('o', 'simple_replace', null, array('start_tag' => '<span class="overline">', 'end_tag' => '</span>'),
			'inline', array('block', 'inline'), array('code'));
		$codes[] = array('s', 'simple_replace', null,
			array('start_tag' => '<span class="strikethrough">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));
		$codes[] = array('u', 'simple_replace', null,
			array('start_tag' => '<span class="underline">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));
		$codes[] = array('EXPERT', 'simple_replace', null,
			array('start_tag' => '<span class="expert">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));

		foreach($codes as $code)
		{
			if($strip)
			{
				$code[1] = 'callback_replace';
				$code[2] = '\\Comment::strip_unused_bbcode'; // this also fixes pre/code
			}

			$bbcode->addCode($code[0], $code[1], $code[2], $code[3], $code[4], $code[5], $code[6]);
		}

		// if $special == true, add special bbcode
		if ($special_code === true)
		{
			/* @todo put this into theme bootstrap
			if ($CI->theme->get_selected_theme() == 'fuuka')
			{
				$bbcode->addCode('moot', 'simple_replace', null,
					array('start_tag' => '<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">', 'end_tag' => '</div>'),
					'inline', array('block', 'inline'), array());
			}
			else*/
			{
				$bbcode->addCode('moot', 'simple_replace', null, array('start_tag' => '', 'end_tag' => ''), 'inline',
					array('block', 'inline'), array());
			}
		}

		return $bbcode->parse($str);
	}

	public static function strip_unused_bbcode($action, $attributes, $content, $params, &$node_object)
	{
		if($content === '' || $content === false)
			return '';

		// if <code> has multiple lines, wrap it in <pre> instead
		if($params['start_tag'] == '<code>')
		{
			if(count(array_filter(preg_split('/\r\n|\r|\n/', $content))) > 1)
			{
				return '<pre>' . $content . '</pre>';
			}
		}

		return $params['start_tag'] . $content . $params['end_tag'];
	}


	/**
	 * A callback function for preg_replace_callback for internal links (>>)
	 * Notice: this function generates some class variables
	 *
	 * @param array $matches the matches sent by preg_replace_callback
	 * @return string the complete anchor
	 */
	protected function p_process_internal_links($matches)
	{
		$num = $matches[2];

		// create link object with all relevant information
		$data = new \stdClass();
		$data->num = str_replace(',', '_', $matches[2]);
		$data->board = $this->board;
		$data->post = $this;

		$current_p_num_c = $this->num . ($this->subnum ? ',' . $this->subnum : '');
		$current_p_num_u = $this->num . ($this->subnum ? '_' . $this->subnum : '');

		$build_url = array(
			'tags' => array('', ''),
			'hash' => '',
			'attr' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
			'attr_op' => 'class="backlink op" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $data->num . '"',
			'attr_backlink' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . $data->board->shortname . '" data-post="' . $current_p_num_u . '"',
		);

		$build_url = \Plugins::run_hook('fu.comment_model.process_internal_links.html_result', array($data, $build_url), 'simple');

		static::$_backlinks_arr[$data->num][$current_p_num_u] = implode(
			'<a href="' . \Uri::create(array($data->board->shortname, $this->_controller_method, $data->post->thread_num)) . '#' . $build_url['hash'] . $current_p_num_u . '" ' .
			$build_url['attr_backlink'] . '>&gt;&gt;' . $current_p_num_c . '</a>'
		, $build_url['tags']);

		if (array_key_exists($num, static::$_posts))
		{
			if ($this->_backlinks_hash_only_url)
			{
				return implode('<a href="#' . $build_url['hash'] . $data->num . '" '
					. $build_url['attr_op'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
			}

			return implode('<a href="' . \Uri::create(array($data->board->shortname, $this->_controller_method, $num)) . '#' . $data->num . '" '
				. $build_url['attr_op'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
		}

		foreach (static::$_posts as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				if ($this->_backlinks_hash_only_url)
				{
					return implode('<a href="#' . $build_url['hash'] . $data->num . '" '
						. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
				}

				return implode('<a href="' . \Uri::create(array($data->board->shortname, $this->_controller_method, $key)) . '#' . $build_url['hash'] . $data->num . '" '
					. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
			}
		}

		if ($this->_realtime === true)
		{
			return implode('<a href="' . \Uri::create(array($data->board->shortname, $this->_controller_method, $key)) . '#' . $build_url['hash'] . $data->num . '" '
				. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
		}

		return implode('<a href="' . \Uri::create(array($data->board->shortname, 'post', $data->num)) . '" '
			. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);

		// return un-altered
		return $matches[0];
	}


	public function p_get_backlinks()
	{
		if (isset(static::$_backlinks_arr[$this->num . ($this->subnum ? '_' . $this->subnum : '')]))
		{
			return static::$_backlinks_arr[$this->num . ($this->subnum ? '_' . $this->subnum : '')];
		}

		return array();
	}


	/**
	 * A callback function for preg_replace_callback for crossboard links (>>>//)
	 * Notice: this function generates some class variables
	 *
	 * @param array $matches the matches sent by preg_replace_callback
	 * @return string the complete anchor
	 */
	protected function p_process_crossboard_links($matches)
	{
		// create link object with all relevant information
		$data = new \stdClass();
		$data->url = $matches[2];
		$data->num = $matches[4];
		$data->shortname = $matches[3];
		$data->board = \Radix::get_by_shortname($data->shortname);

		$build_url = array(
			'tags' => array('', ''),
			'backlink' => 'class="backlink" data-function="highlight" data-backlink="true" data-board="' . (($data->board) ? $data->board->shortname : $data->shortname) . '" data-post="' . $data->num . '"'
		);

		$build_url = \Plugins::run_hook('fu.comment_model.process_crossboard_links.html_result', array($data, $build_url), 'simple');

		if (!$data->board)
		{
			if ($data->num)
			{
				return implode('<a href="//boards.4chan.org/' . $data->shortname . '/res/' . $data->num . '">&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);
			}

			return implode('<a href="//boards.4chan.org/' . $data->shortname . '/">&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);
		}

		if ($data->num)
		{
			return implode('<a href="' . \Uri::create(array($data->board->shortname, 'post', $data->num)) . '" ' . $build_url['backlink'] . '>&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);
		}

		return implode('<a href="' . \Uri::create($data->board->shortname) . '">&gt;&gt;&gt;' . $data->url . '</a>', $build_url['tags']);

		// return un-altered
		return $matches[0];
	}


	/**
	 * Returns the HTML for the post with the currently selected theme
	 *
	 * @param object $board
	 * @param object $post database row for the post
	 * @return string the post box HTML with the selected theme
	 */
	protected function p_build_comment()
	{
		$theme = \Theme::instance('foolfuuka');
		return $theme->build('board_comment', array('p' => $this), true, true);
	}



	/**
	 * This function is grabbed from Codeigniter Framework on which
	 * the original FoOlFuuka was coded on: http://codeigniter.com
	 * The function is modified tu support multiple subdomains and https
	 *
	 * @param type $str
	 * @param type $type
	 * @param type $popup
	 * @return type
	 */
	protected static function p_auto_linkify($str, $type = 'both', $popup = false)
	{
		if ($type != 'email')
		{
			if (preg_match_all("#(^|\s|\(|\])((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $str, $matches))
			{
				$pop = ($popup == true) ? " target=\"_blank\" " : "";

				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$period = '';
					if (preg_match("|\.$|", $matches['6'][$i]))
					{
						$period = '.';
						$matches['6'][$i] = substr($matches['6'][$i], 0, -1);
					}

					$internal = (strpos($matches['6'][$i], $_SERVER['HTTP_HOST']) === 0);

					if (!$internal && defined('FOOL_SUBDOMAINS_ENABLED') && FOOL_SUBDOMAINS_ENABLED == true)
					{
						$subdomains = array(
							FOOL_SUBDOMAINS_SYSTEM,
							FOOL_SUBDOMAINS_BOARD,
							FOOL_SUBDOMAINS_ARCHIVE,
							FOOL_SUBDOMAINS_DEFAULT
						);

						foreach ($subdomains as $subdomain)
						{
							if (strpos($matches['6'][$i], rtrim($subdomain, '.')) === 0)
							{
								$host_array = explode('.', $_SERVER['HTTP_HOST']);
								array_shift($host_array);
								array_unshift($host_array, $subdomain);
								$host = implode('.', $host_array);
								if (strpos($matches['6'][$i], $host) === 0)
								{
									$internal = true;
									break;
								}
							}
						}
					}

					if ($internal)
					{
						$str = str_replace($matches['0'][$i],
							$matches['1'][$i] . '<a href="//' .
							$matches['5'][$i] .
							preg_replace('/[[\/\!]*?[^\[\]]*?]/si', '', $matches['6'][$i]) . '"' . $pop . '>http' .
							$matches['4'][$i] . '://' .
							$matches['5'][$i] .
							$matches['6'][$i] . '</a>' .
							$period, $str);
					}
					else
					{
						$str = str_replace($matches['0'][$i],
							$matches['1'][$i] . '<a href="http' .
							$matches['4'][$i] . '://' .
							$matches['5'][$i] .
							preg_replace('/[[\/\!]*?[^\[\]]*?]/si', '', $matches['6'][$i]) . '"' . $pop . '>http' .
							$matches['4'][$i] . '://' .
							$matches['5'][$i] .
							$matches['6'][$i] . '</a>' .
							$period, $str);
					}
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

					$str = str_replace($matches['0'][$i],
						safe_mailto($matches['1'][$i] . '@' . $matches['2'][$i] . '.' . $matches['3'][$i]) . $period, $str);
				}
			}
		}

		return $str;
	}


	public function p_clean_fields()
	{
		if ( ! \Auth::has_access('comment.see_ip'))
		{
			unset($this->poster_ip);
		}

		unset($this->delpass);
	}


	/**
	 * Delete the post and eventually the entire thread if it's OP
	 * Also deletes the images when it's the only post with that image
	 *
	 * @return array|bool
	 */
	protected function p_delete($password = null, $force = false)
	{
		if( ! \Auth::has_access('comment.passwordless_deletion') && $force !== true)
		{
			if ( ! class_exists('PHPSecLib\\Crypt_Hash', false))
			{
				import('phpseclib/Crypt/Hash', 'vendor');
			}

			$hasher = new \PHPSecLib\Crypt_Hash();

			$hashed = base64_encode($hasher->pbkdf2($password, \Config::get('auth.salt'), 10000, 32));

			if($this->delpass != $hashed)
			{
				throw new CommentDeleteWrongPassException(__('The password you inserted didn\'t match the password deletion password.'));
			}
		}

		\DB::start_transaction();

		// remove message
		\DB::delete(\DB::expr(Radix::get_table($this->board)))->where('doc_id', $this->doc_id)->execute();

		// remove its extras
		\DB::delete(\DB::expr(Radix::get_table($this->board, '_extra')))->where('extra_id', $this->doc_id)->execute();

		// remove message search entry
		if($this->board->myisam_search)
		{
			\DB::delete(\DB::expr(Radix::get_table($this->board, '_search')))->where('doc_id', $this->doc_id)->execute();
		}

		// remove message reports
		$reports_affected = \DB::delete('reports')->where('board_id', $this->board->id)->where('doc_id', $this->doc_id)->execute();
		if ($reports_affected > 0)
		{
			\Report::clear_cache();
		}

		// remove its image file
		if (isset($this->media))
		{
			$this->media->delete();
		}

		// if it's OP delete all other comments
		if ($this->op)
		{
			$replies = \DB::select('doc_id')
				->from(\DB::expr(Radix::get_table($this->board)))
				->where('thread_num', $this->thread_num)
				->as_object()
				->execute()
				->as_array();

			foreach ($replies as $reply)
			{
				$comments = \Board::forge()
					->get_post()
					->set_options('doc_id', $reply->doc_id)
					->set_radix($this->board)
					->get_comments();

				$comment = current($comments);
				$comment->delete(null, true);
			}
		}

		\DB::commit_transaction();
	}


	/**
	 * Processes the name with unprocessed tripcode and returns name and processed tripcode
	 *
	 * @return array name without tripcode and processed tripcode concatenated with processed secure tripcode
	 */
	protected function p_process_name()
	{
		$name = $this->name;

		// define variables
		$matches = array();
		$normal_trip = '';
		$secure_trip = '';

		if (preg_match("'^(.*?)(#)(.*)$'", $this->name, $matches))
		{
			$matches_trip = array();
			$name = trim($matches[1]);

			preg_match("'^(.*?)(?:#+(.*))?$'", $matches[3], $matches_trip);

			if (count($matches_trip) > 1)
			{
				$normal_trip = static::process_tripcode($matches_trip[1]);
				$normal_trip = $normal_trip ? '!' . $normal_trip : '';
			}

			if (count($matches_trip) > 2)
			{
				$secure_trip = '!!' . static::process_secure_tripcode($matches_trip[2]);
			}
		}

		$this->name = $name;
		$this->trip = $normal_trip . $secure_trip;

		return array('name' => $name, 'trip' => $normal_trip . $secure_trip);
	}


	/**
	 * Processes the tripcode
	 *
	 * @param string $plain the word to generate the tripcode from
	 * @return string the processed tripcode
	 */
	protected static function p_process_tripcode($plain)
	{
		if (trim($plain) == '')
		{
			return '';
		}

		$trip = mb_convert_encoding($plain, 'SJIS', 'UTF-8');

		$salt = substr($trip . 'H.', 1, 2);
		$salt = preg_replace('/[^.-z]/', '.', $salt);
		$salt = strtr($salt, ':;<=>?@[\]^_`', 'ABCDEFGabcdef');

		return substr(crypt($trip, $salt), -10);
	}


	/**
	 * Process the secure tripcode
	 *
	 * @param string $plain the word to generate the secure tripcode from
	 * @return string the processed secure tripcode
	 */
	protected function p_process_secure_tripcode($plain)
	{
		return substr(base64_encode(sha1($plain . base64_decode(\Config::get('foolframe.preferences.comment.secure_tripcode_salt')), true)), 0, 11);
	}


	/**
	 * Send the comment and attached media to database
	 *
	 * @param object $board
	 * @param array $data the comment data
	 * @param array $options modifiers
	 * @return array error key with explanation to show to user, or success and post row
	 */
	protected function p_insert()
	{
		// some users don't need to be limited, in here go all the ban and posting limitators
		if( ! \Auth::has_access('comment.limitless_comment'))
		{
			// check if the user is banned
			if ($ban = \Ban::is_banned(\Input::ip_decimal(), $this->board))
			{
				if ($ban->board_id == 0)
				{
					$banned_string = __('It looks like you were banned on all boards.');
				}
				else
				{
					$banned_string = __('It looks like you were banned on /'.$this->board->shortname.'/.');
				}

				if ($ban->length)
				{
					$banned_string .= ' '.__('This ban will last until:').' '.date(DATE_COOKIE, $ban->start + $ban->length).'.';
				}
				else
				{
					$banned_string .= ' '.__('This ban will last forever.');
				}

				if ($this->reason)
				{
					$banned_string .= ' '.__('The reason for this ban is:').' «'.$this->reason.'».';
				}

				throw new CommentSendingBannedException($banned_string);
			}

			if ($this->thread_num < 1)
			{
				// one can create a new thread only once every 5 minutes
				$check_op = \DB::select()->from(\DB::expr(Radix::get_table($this->board)))
					->where('poster_ip', \Input::ip_decimal())->where('timestamp', '>', time() - 300)
					->where('op', 1)->limit(1)->execute();

				if(count($check_op))
				{
					throw new CommentSendingTimeLimitException(__('You must wait up to 5 minutes to make another new thread.'));
				}
			}

			// check the latest posts by the user to see if he's posting the same message or if he's posting too fast
			$check = \DB::select()->from(\DB::expr(Radix::get_table($this->board)))
				->where('poster_ip', \Input::ip_decimal())->order_by('timestamp', 'desc')->limit(1)
				->as_object()->execute();

			if (count($check))
			{
				$row = $check->current();

				if ($this->comment != null && $row->comment == $this->comment)
				{
					throw new CommentSendingSameCommentException(__('You\'re sending the same comment as the last time'));
				}

				if (time() - $row->timestamp < 10 && time() - $row->timestamp > 0)
				{
					throw new CommentSendingTimeLimitException(__('You must wait up to 10 seconds to post again.'));
				}
			}

			// load the spam list and check comment, name, subject and email
			$spam = array_filter(preg_split('/\r\n|\r|\n/', file_get_contents(DOCROOT.'assets/anti-spam/databases')));
			foreach($spam as $s)
			{
				if(strpos($comment, $s) !== false || strpos($name, $s) !== false
					|| strpos($subject, $s) !== false || strpos($email, $s) !== false)
				{
					throw new CommentSendingSpamException(__('Your post has undesidered content.'));
				}
			}

			// check entire length of comment
			if (mb_strlen($this->comment) > 4096)
			{
				throw new CommentSendingTooManyCharactersException(__('Your comment has too many characters'));
			}

			// check total numbers of lines in comment
			if (count(explode("\n", $this->comment)) > 20)
			{
				throw new CommentSendingTooManyLinesException(__('Your comment has too many lines.'));
			}
		}

		$this->ghost = false;
		$this->allow_media = true;

		// check if it's a thread and its status
		if ($this->thread_num > 0)
		{
			try
			{
				$thread = Board::forge()->get_thread($this->thread_num)->set_radix($this->board);
				$thread->get_comments();
				$status = $thread->check_thread_status();
			}
			catch (Model\BoardException $e)
			{
				throw new Model\CommentSendingException($e->getMessage());
			}

			$this->ghost = $status['dead'];
			$this->allow_media = ! $status['disable_image_upload'];
		}

		foreach(array('name', 'email', 'title', 'delpass', 'comment', 'capcode') as $key)
		{
			$this->$key = (string) $this->$key;
		}

		\Plugins::run_hook('fu.comment.insert.alter_input_after_checks', array(&$this), 'simple');

		// process comment name+trip
		if ($this->name === '')
		{
			$this->name = $this->board->anonymous_default_name;
			$this->trip = null;
		}
		else
		{
			$this->process_name();
			if ($this->trip === '')
			{
				$this->trip = null;
			}
		}

		foreach(array('email', 'title', 'delpass', 'comment') as $key)
		{
			if ($this->$key === '')
			{
				$this->$key = null;
			}
		}

		// we want to know if the comment will display empty, and in case we won't let it pass
		if($this->comment !== null)
		{
			$comment_parsed = $this->process_comment();
			if(!$comment_parsed)
			{
				throw new CommentSendingDisplaysEmptyException(__('This comment would display empty.'));
			}

			// clean up to reset eventual auto-built entries
			foreach ($this->_forced_entries as $field)
			{
				unset($this->$field);
			}

		}

		// process comment password
		if ($this->delpass === '')
		{
			throw new CommentSendingNoDelPassException(__('You must submit a deletion password.'));
		}

		if (!class_exists('PHPSecLib\\Crypt_Hash', false))
		{
			import('phpseclib/Crypt/Hash', 'vendor');
		}

		$hasher = new \PHPSecLib\Crypt_Hash();
		$this->delpass = base64_encode($hasher->pbkdf2($this->delpass, \Config::get('auth.salt'), 10000, 32));

		if ($this->capcode != '')
		{
			$allowed_capcodes = array('N');

			if(\Auth::has_access('comment.mod_capcode'))
			{
				$allowed_capcodes[] = 'M';
			}

			if(\Auth::has_access('comment.admin_capcode'))
			{
				$allowed_capcodes[] = 'A';
			}

			if(\Auth::has_access('comment.dev_capcode'))
			{
				$allowed_capcodes[] = 'D';
			}

			if(!in_array($this->capcode, $allowed_capcodes))
			{
				throw new CommentSendingUnallowedCapcodeException(__('You\'re not allowed to use this capcode.'));
			}
		}
		else
		{
			$this->capcode = 'N';
		}

		$microtime = str_replace('.', '', (string) microtime(true));
		$this->timestamp = substr($microtime, 0, 10);
		$this->op = (bool) ! $this->thread_num;

		if ($this->poster_ip === null)
		{
			$this->poster_ip = \Input::ip_decimal();
		}

		if ($this->board->enable_flags && function_exists('geoip_country_code_by_name'))
		{
			$this->poster_country = \geoip_country_code_by_name(\Inet::dtop($this->poster_ip));
		}

		// process comment media
		if ($this->media !== null)
		{
			if ( ! $this->allow_media)
			{
				throw new CommentSendingImageInGhostException(__('You can\'t post images when the thread is in ghost mode.'));
			}

			try
			{
				$this->media->insert($microtime, $this->op);
			}
			catch (MediaInsertException $e)
			{
				throw new CommentSendingException($e->getMessage());
			}
		}
		else
		{
			// if no media is present and post is op, stop processing
			if (!$this->thread_num)
			{
				throw new CommentSendingThreadWithoutMediaException(__('You can\'t start a new thread without an image.'));
			}

			// in case of no media, check comment field again for null
			if ($this->comment === null)
			{
				throw new CommentSendingDisplaysEmptyException(__('This comment would display empty.'));
			}

			$this->media = Media::forge_empty($this->board);
		}

		// 2ch-style codes, only if enabled
		if($this->thread_num && $this->board->enable_poster_hash)
		{
			$this->poster_hash = substr(substr(crypt(md5(\Input::ip_decimal().'id'.$this->thread_num),'id'),+3), 0, 8);
		}

		if($this->board->archive)
		{
			// archives are in new york time
			$newyork = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('America/New_York'));
			$utc = new \DateTime(date('Y-m-d H:i:s', time()), new \DateTimeZone('UTC'));
			$diff = $newyork->diff($utc)->h;
			$this->timestamp = $this->timestamp - ($diff * 60 * 60);
		}

		\Plugins::run_hook('fu.comment.insert.alter_input_before_sql', array(&$this), 'simple');

		\DB::start_transaction();

		// being processing insert...

		if($this->ghost)
		{
			$num = \DB::expr('
				(SELECT MAX(num)
				FROM
				(
					SELECT num
					FROM '.Radix::get_table($this->board).'
					WHERE thread_num = '.intval($this->thread_num).'
				) AS x)
			');

			$subnum = \DB::expr('
				(SELECT MAX(subnum)+1
				FROM
				(
					SELECT subnum
					FROM ' . \Radix::get_table($this->board) . '
					WHERE
						num = (
							SELECT MAX(num)
							FROM ' . \Radix::get_table($this->board) . '
							WHERE thread_num = '.intval($this->thread_num).'

						)
				) AS x)
			');

			$thread_num = $this->thread_num;
		}
		else
		{
			$num = \DB::expr('
				(SELECT COALESCE(MAX(num), 0)+1 AS num
				FROM
				(
					SELECT num
					FROM '.Radix::get_table($this->board).'
				) AS x)
			');

			$subnum = 0;

			if($this->thread_num > 0)
			{
				$thread_num = $this->thread_num;
			}
			else
			{
				$thread_num = \DB::expr('
					(SELECT COALESCE(MAX(num), 0)+1 AS thread_num
					FROM
					(
						SELECT num
						FROM '.Radix::get_table($this->board).'
					) AS x)
				');
			}
		}

		list($last_id, $num_affected) =
			\DB::insert(\DB::expr(Radix::get_table($this->board)))
			->set(array(
				'num' => $num,
				'subnum' => $subnum,
				'thread_num' => $thread_num,
				'op' => $this->op,
				'timestamp' => $this->timestamp,
				'capcode' => $this->capcode,
				'email' => $this->email,
				'name' => $this->name,
				'trip' => $this->trip,
				'title' => $this->title,
				'comment' => $this->comment,
				'delpass' => $this->delpass,
				'spoiler' => $this->media->spoiler,
				'poster_ip' => $this->poster_ip,
				'poster_hash' => $this->poster_hash,
				'poster_country' => $this->poster_country,
				'preview_orig' => $this->media->preview_orig,
				'preview_w' => $this->media->preview_w,
				'preview_h' => $this->media->preview_h,
				'media_filename' => $this->media->media_filename,
				'media_w' => $this->media->media_w,
				'media_h' => $this->media->media_h,
				'media_size' => $this->media->media_size,
				'media_hash' => $this->media->media_hash,
				'media_orig' => $this->media->media_orig,
				'exif' => $this->media->exif !== null ? json_encode($this->media->exif) : null,
			))->execute();

		// check that it wasn't posted multiple times
		$check_duplicate = \DB::select()->from(\DB::expr(Radix::get_table($this->board)))
			->where('poster_ip', \Input::ip_decimal())->where('comment', $this->comment)
			->where('timestamp', '>=', $this->timestamp)->as_object()->execute();

		if(count($check_duplicate) > 1)
		{
			\DB::rollback_transaction();
			throw new CommentSendingDuplicateException(__('You are sending the same post twice.'));
		}

		$comment = $check_duplicate->current();

		$media_fields = Media::get_fields();
		// refresh the current comment object with the one finalized fetched from DB
		foreach ($comment as $key => $item)
		{
			if (in_array($key, $media_fields))
			{
				$this->media->$key = $item;
			}
			else
			{
				$this->$key = $item;
			}
		}

		// update poster_hash for non-ghost posts
		if ( ! $this->ghost && $this->op && $this->board->enable_poster_hash)
		{
			$this->poster_hash = substr(substr(crypt(md5(\Input::ip_decimal().'id'.$comment->thread_num),'id'),+3), 0, 8);

			\DB::update(\DB::expr(\Radix::get_table($this->board)))
				->value('poster_hash', $this->poster_hash)->where('doc_id', $comment->doc_id)->execute();
		}

		// set data for extra fields
		\Plugins::run_hook('fu.comment.insert.extra_json_array', array(&$this), 'simple');

		// insert the extra row DURING A TRANSACTION
		$this->extra->extra_id = $last_id;
		$this->extra->insert();

		\DB::commit_transaction();

		// success, now check if there's extra work to do

		// we might be using the local MyISAM search table which doesn't support transactions
		// so we must be really careful with the insertion
		if($this->board->myisam_search)
		{
			\DB::insert(\DB::expr(Radix::get_table($this->board, '_search')))
				->set(array(
					'doc_id' => $comment->doc_id,
					'num' => $comment->num,
					'subnum' => $comment->subnum,
					'thread_num' => $comment->thread_num,
					'media_filename' => $comment->media_filename,
					'comment' => $comment->comment
				))->execute();
		}

		return $this;
	}

}