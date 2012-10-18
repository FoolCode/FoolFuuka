<?php

namespace Foolz\Foolfuuka\Model;

class CommentException extends \FuelException {}

class CommentDeleteWrongPassException extends CommentException {}

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
	protected $_prefetch_backlinks = true;
	protected static $_bbcode_parser = null;
	protected $_force_entries = false;
	protected $_forced_entries = array(
		'title_processed', 'name_processed', 'email_processed', 'trip_processed',
		'poster_hash_processed', 'original_timestamp', 'fourchan_date', 'comment_sanitized',
		'comment_processed', 'poster_country_name_processed'
	);

	public $recaptcha_challenge = null;
	public $recaptcha_response = null;

	public $radix = null;

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

		return new static($post, $board, $options);
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

		$comment = new static($post, $board, $options);

		$fields = $comment->_forced_entries;

		if (isset($api['theme']) && $api['theme'] !== null)
		{
			$fields[] = 'formatted';
		}

		foreach ($fields as $field)
		{
			$comment->{'get_'.$field}();
		}

		// also spawn media variables
		if ($comment->media !== null)
		{
			// backwards compatibility with 4chan X
			foreach (Media::$_fields as $field)
			{
				if ( ! isset($comment->$field))
				{
					$comment->$field = $comment->media->$field;
				}
			}

			// if we come across a banned image we set all the data to null. Normal users must not see this data.
			if (($comment->media->banned && ! \Auth::has_access('media.see_banned'))
				|| ($comment->media->board->hide_thumbnails && ! \Auth::has_access('media.see_hidden')))
			{
				$banned = array(
					'media_id' => 0,
					'spoiler' => false,
					'preview_orig' => null,
					'preview_w' => 0,
					'preview_h' => 0,
					'media_filename' => null,
					'media_w' => 0,
					'media_h' => 0,
					'media_size' => 0,
					'media_hash' => null,
					'media_orig' => null,
					'exif' => null,
					'total' => 0,
					'banned' => 0,
					'media' => null,
					'preview_op' => null,
					'preview_reply' => null,

					// optionals
					'safe_media_hash' => null,
					'remote_media_link' => null,
					'media_link' => null,
					'thumb_link' => null,
				);

				foreach ($banned as $key => $item)
				{
					$comment->media->$key = $item;
				}
			}

			// startup variables and put them also in the lower level for compatibility with older 4chan X
			foreach (array(
				'safe_media_hash',
				'preview_orig_processed',
				'media_filename_processed',
				'media_hash_processed',
				'media_link',
				'remote_media_link',
				'thumb_link'
			) as $field)
			{
				$comment->$field = $comment->media->{'get_'.$field}();
			}

			unset($comment->media->board);
		}


		if (isset($api['board']) && !$api['board'])
		{
			unset($comment->board);
		}

		// remove controller method
		unset($comment->_controller_method);

		// remove radix data
		unset($comment->extra->_radix);

		// we don't have captcha in use in api
		unset($comment->recaptcha_challenge, $comment->recaptcha_response);

		return $comment;
	}


	public function __construct($post, $board, $options = array())
	{
		//parent::__construct();

		$this->radix = $board;

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
			$this->media = Media::forge_from_comment($media, $this->radix, $this->op);
		}
		else
		{
			$this->media = null;
		}

		$this->extra = Extra::forge($extra, $this->radix);

		foreach ($options as $key => $value)
		{
			$this->{'_'.$key} = $value;
		}

		// format 4chan archive timestamp
		if ($this->radix->archive)
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
			$this->get_comment_processed();
		}

		if ($this->poster_country !== null)
		{
			$this->poster_country_name = \Config::get('geoip_codes.codes.'.strtoupper($this->poster_country));
		}

		$num = $this->num.($this->subnum ? ',' . $this->subnum : '');
		static::$_posts[$this->thread_num][] = $num;
	}


	public function get_original_timestamp()
	{
		return $this->timestamp;
	}


	public function get_fourchan_date()
	{
		if ( ! isset($this->fourchan_date))
		{
			$this->fourchan_date = gmdate('n/j/y(D)G:i', $this->get_original_timestamp());
		}

		return $this->fourchan_date;
	}


	public function get_comment_sanitized()
	{
		if ( ! isset($this->comment_sanitized))
		{
			$this->comment_sanitized = @iconv('UTF-8', 'UTF-8//IGNORE', $this->comment);
		}

		return $this->comment_sanitized;
	}


	public function get_comment_processed()
	{
		if ( ! isset($this->comment_processed))
		{
			$this->comment_processed = @iconv('UTF-8', 'UTF-8//IGNORE', $this->process_comment());
		}

		return $this->comment_processed;
	}


	public function get_formatted()
	{
		if ( ! isset($this->formatted))
		{
			$this->formatted = $this->build_comment();
		}

		return $this->formatted;
	}


	public function get_reports()
	{
		if ( ! isset($this->reports))
		{
			if (\Auth::has_access('comment.reports'))
			{
				$reports = \Report::getByDocId($this->radix, $this->doc_id);

				if ($this->media)
				{
					$reports += \Report::getByMediaId($this->radix, $this->media->media_id);
				}

				$this->reports = $reports;
			}
			else
			{
				$this->reports = array();
			}
		}

		return $this->reports;

	}


	public static function process($string)
	{
		return e(@iconv('UTF-8', 'UTF-8//IGNORE', $string));
	}


	public function get_title_processed()
	{
		if ( ! isset($this->title_processed))
		{
			$this->title_processed = static::process($this->title);
		}

		return $this->title_processed;
	}


	public function get_name_processed()
	{
		if ( ! isset($this->name_processed))
		{
			$this->name_processed = static::process($this->name);
		}

		return $this->name_processed;
	}


	public function get_email_processed()
	{
		if ( ! isset($this->email_processed))
		{
			$this->email_processed = static::process($this->email);
		}

		return $this->email_processed;
	}


	public function get_trip_processed()
	{
		if ( ! isset($this->trip_processed))
		{
			$this->trip_processed = static::process($this->trip);
		}

		return $this->trip_processed;
	}


	public function get_poster_hash_processed()
	{
		if ( ! isset($this->poster_hash_processed))
		{
			$this->poster_hash_processed = static::process($this->poster_hash);
		}

		return $this->poster_hash_processed;
	}


	public function get_poster_country_name_processed()
	{
		if ( ! isset($this->poster_country_name_processed))
		{
			if ( ! isset($this->poster_country_name))
			{
				$this->poster_country_name_processed = null;
			}
			else
			{
				$this->poster_country_name_processed = static::process($this->poster_country_name);
			}
		}

		return $this->poster_country_name_processed;
	}


	/**
	 * Processes the comment, strips annoying data from moot, converts BBCode,
	 * converts > to greentext, >> to internal link, and >>> to external link
	 *
	 * @param object $board
	 * @param object $post the database row for the post
	 * @return string the processed comment
	 */
	public function process_comment()
	{
		// default variables
		$find = "'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i";
		$html = '\\1<span class="greentext">\\2</span>\\3';

		$html = \Foolz\Plugin\Hook::forge('fu.comment_model.process_comment.greentext_result')
			->setParam('html', $html)
			->execute()
			->get($html);

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
		$this->current_board_for_prc = $this->radix;

		// format entire comment
		$comment = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i",
			array(get_class($this), 'process_internal_links'), $comment);

		$comment = preg_replace_callback("'(&gt;&gt;&gt;(\/(\w+)\/([\w-]+(?:,\d+)?)?(\/?)))'i",
			array(get_class($this), 'process_external_links'), $comment);

		$comment = preg_replace($find, $html, $comment);
		$comment = static::parse_bbcode($comment, ($this->radix->archive && !$this->subnum));
		$comment = static::auto_linkify($comment, 'url', true);

		// additional formatting
		if ($this->radix->archive && !$this->subnum)
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
		if (static::$_bbcode_parser === null)
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

			static::$_bbcode_parser = $bbcode;
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
				static::$_bbcode_parser->addCode('moot', 'simple_replace', null, array('start_tag' => '', 'end_tag' => ''), 'inline',
					array('block', 'inline'), array());
			}
		}

		return static::$_bbcode_parser->parse($str);
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

		// limit nesting level
		$parent_count = 0;
		$temp_node_object = $node_object;
		while ($temp_node_object->_parent !== null)
		{
			$parent_count++;
			$temp_node_object = $temp_node_object->_parent;

			if (in_array($params['start_tag'], array('<sub>', '<sup>')) && $parent_count > 1)
			{
				return $content;
			}
			else if ($parent_count > 4)
			{
				return $content;
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
	public function process_internal_links($matches)
	{
		$num = $matches[2];

		// create link object with all relevant information
		$data = new \stdClass();
		$data->num = str_replace(',', '_', $matches[2]);
		$data->board = $this->radix;
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

		$build_url = \Foolz\Plugin\Hook::forge('fu.comment_model.process_internal_links.html_result')
			->setObject($this)
			->setParam('data', $data)
			->setParam('build_url', $build_url)
			->execute()
			->get($build_url);

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
			return implode('<a href="' . \Uri::create(array($data->board->shortname, $this->_controller_method, $this->thread_num)) . '#' . $build_url['hash'] . $data->num . '" '
				. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);
		}

		return implode('<a href="' . \Uri::create(array($data->board->shortname, 'post', $data->num)) . '" '
			. $build_url['attr'] . '>&gt;&gt;' . $num . '</a>', $build_url['tags']);

		// return un-altered
		return $matches[0];
	}


	public function get_backlinks()
	{
		if (isset(static::$_backlinks_arr[$this->num . ($this->subnum ? '_' . $this->subnum : '')]))
		{
			ksort(static::$_backlinks_arr[$this->num . ($this->subnum ? '_' . $this->subnum : '')]);
			return static::$_backlinks_arr[$this->num . ($this->subnum ? '_' . $this->subnum : '')];
		}

		return array();
	}


	/**
	 * A callback function for preg_replace_callback for external links (>>>//)
	 * Notice: this function generates some class variables
	 *
	 * @param array $matches the matches sent by preg_replace_callback
	 * @return string the complete anchor
	 */
	public function process_external_links($matches)
	{
		// create $data object with all results from $matches
		$data = new \stdClass();
		$data->link = $matches[2];
		$data->shortname = $matches[3];
		$data->board = \Radix::getByShortname($data->shortname);
		$data->query = $matches[4];

		$build_href = array(
			// this will wrap the <a> element with a container element [open, close]
			'tags' => array('open' => '', 'close' => ''),

			// external links; defaults to 4chan
			'short_link' => '//boards.4chan.org/'.$data->shortname.'/',
			'query_link' => '//boards.4chan.org/'.$data->shortname.'/res/'.$data->query,

			// additional attributes + backlinking attributes
			'attributes' => '',
			'backlink_attr' => ' class="backlink" data-function="highlight" data-backlink="true" data-board="'
				.(($data->board)?$data->board->shortname:$data->shortname).'" data-post="'.$data->query.'"'
		);

		$build_href = \Foolz\Plugin\Hook::forge('fu.comment_model.process_external_links.html_result')
			->setObject($this)
			->setParam('data', $data)
			->setParam('build_href', $build_href)
			->execute()
			->get($build_href);

		if ( ! $data->board)
		{
			if ($data->query)
			{
				return implode('<a href="'.$build_href['query_link'].'"'.$build_href['attributes'].'>&gt;&gt;&gt;'.$data->link.'</a>', $build_href['tags']);
			}

			return implode('<a href="'.$build_href['short_link'].'">&gt;&gt;&gt;'.$data->link.'</a>', $build_href['tags']);
		}

		if ($data->query)
		{
			return implode('<a href="'.\Uri::create(array($data->board->shortname, 'post', $data->query)).'"'
				.$build_href['attributes'].$build_href['backlink_attr'].'>&gt;&gt;&gt;'.$data->link.'</a>', $build_href['tags']);
		}

		return implode('<a href="' . \Uri::create($data->board->shortname) . '">&gt;&gt;&gt;' . $data->link . '</a>', $build_href['tags']);

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
	public function build_comment()
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
	public static function auto_linkify($str, $type = 'both', $popup = false)
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

		return $str;
	}


	public function clean_fields()
	{
		\Foolz\Plugin\Hook::forge('foolfuuka\\model\\comment.clean_fields.call.before')
			->setObject($this)
			->execute();

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

			if($this->delpass !== $hashed)
			{
				throw new CommentDeleteWrongPassException(__('The password you inserted didn\'t match the deletion password.'));
			}
		}

		\DB::start_transaction();

		// remove message
		\DB::delete(\DB::expr($this->radix->getTable()))->where('doc_id', $this->doc_id)->execute();

		// remove its extras
		\DB::delete(\DB::expr($this->radix->getTable('_extra')))->where('extra_id', $this->doc_id)->execute();

		// remove message search entry
		if($this->radix->myisam_search)
		{
			\DB::delete(\DB::expr($this->radix->getTable('_search')))->where('doc_id', $this->doc_id)->execute();
		}

		// remove message reports
		$reports_affected = \DB::delete('reports')->where('board_id', $this->radix->id)->where('doc_id', $this->doc_id)->execute();
		if ($reports_affected > 0)
		{
			\Report::clearCache();
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
				->from(\DB::expr($this->radix->getTable()))
				->where('thread_num', $this->thread_num)
				->as_object()
				->execute()
				->as_array();

			foreach ($replies as $reply)
			{
				$comments = \Board::forge()
					->get_post()
					->set_options('doc_id', $reply->doc_id)
					->set_radix($this->radix)
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
}