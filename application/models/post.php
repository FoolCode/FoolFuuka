<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Post extends CI_Model
{
	// store all relavent data regarding posts displayed
	var $posts_arr = array();
	var $backlinks = array();

	// global variables used for processing due to callbacks
	var $backlinks_hash_only_url = FALSE;
	var $current_p = NULL;
	var $features = TRUE;
	var $realtime = FALSE;


	function __construct()
	{
		parent::__construct();
	}


	/**
	 * @param object $board
	 * @param null|string $join_on
	 * @return string
	 */
	function sql_report_join($board, $join_on = NULL)
	{
		// only show report notifications to certain users
		if (!$this->tank_auth->is_allowed())
		{
			return '';
		}

		return '
			LEFT JOIN
			(
				SELECT
					id AS report_id, doc_id AS report_doc_id, reason AS report_reason, ip_reporter as report_ip_reporter,
					status AS report_status, created AS report_created
				FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
				WHERE `board_id` = ' . $board->id . '
			) AS r
			ON
			' . ($join_on ? $join_on : $this->radix->get_table($board)) . '.`doc_id`
			=
			' . $this->db->protect_identifiers('r') . '.`report_doc_id`
		';
	}


	/**
	 * @param object $board
	 * @param null|string $join_on
	 * @return string
	 */
	function sql_media_join($board, $join_on = NULL)
	{
		return '
			LEFT JOIN
				(' . $this->radix->get_table($board, '_images') . ' AS `m`)
			ON
			' . ($join_on ? $join_on : $this->radix->get_table($board)) . '.`media_id`
			=
			' . $this->db->protect_identifiers('m') . '.`media_id`
		';
	}


	/**
	 * @param array|object $posts
	 */
	function populate_posts_arr($post)
	{
		if (is_array($post))
		{
			foreach ($post as $p)
			{
				$this->populate_posts_arr($p);
			}
		}

		if (is_object($post))
		{
			if ($post->parent == 0)
			{
				$this->posts_arr[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->posts_arr[$post->parent][] = $post->num;
				else
					$this->posts_arr[$post->parent][] = $post->num . ',' . $post->subnum;
			}
		}
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @param bool $thumbnail
	 * @return bool|string
	 */
	function get_media_dir($board, $post, $thumbnail = FALSE)
	{
		if (!$post->media_filename && !$post->media_hash)
		{
			return FALSE;
		}

		if ($thumbnail === TRUE)
		{
			if (isset($post->parent))
			{
				$image = $post->preview_op ? $post->preview_op : $post->preview_reply;
			}
			else
			{
				$image = $post->preview_reply ? $post->preview_reply : $post->preview_op;
			}
		}
		else
		{
			$image = $post->media_filename;
		}

		return get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/'
			. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
	}


	/**
	 * @param object $board
	 * @param object $row
	 * @param bool $thumbnail
	 * @return bool|string
	 */
	function get_media_link($board, $post, $thumbnail = FALSE)
	{
		if (!$post->media_filename && !$post->media_hash)
		{
			return FALSE;
		}

		// these features will only affect guest users
		if (!$this->tank_auth->is_allowed())
		{
			// hide all thumbnails for the board
			if (!$board->hide_thumbnails)
			{
				if ($thumbnail === TRUE)
				{
					// we need to define the size of the image
					$post->preview_h = 150;
					$post->preview_w = 150;
					return site_url() . 'content/themes/default/images/null-image.png';
				}

				return FALSE;
			}

			// add a delay of 1 day to all thumbnails
			if ($board->delay_thumbnails)
			{
				if (isset($post->timestamp) && ($post->timestamp + 86400) > time())
				{
					if ($thumbnail === TRUE)
					{
						// we need to define the size of the image
						$post->preview_h = 150;
						$post->preview_w = 150;
						return site_url() . 'content/themes/default/images/null-image.png';
					}

					return FALSE;
				}
			}
		}

		// this post contain's a banned media, do not display
		if ($post->banned == 1)
		{
			if ($thumbnail === TRUE)
			{
				// we need to define the size of the image
				$post->preview_h = 150;
				$post->preview_w = 150;
				return site_url() . 'content/themes/default/images/banned-image.png';
			}

			return FALSE;
		}

		// locate the image
		if (file_exists($this->get_media_dir($board, $post, $thumbnail)) !== FALSE)
		{
			if ($thumbnail === TRUE)
			{
				if (isset($post->parent))
				{
					$image = $post->preview_op ? $post->preview_op : $post->preview_reply;
				}
				else
				{
					$image = $post->preview_reply ? $post->preview_reply : $post->preview_op;
				}
			}
			else
			{
				$image = $post->media_filename;
			}

			// output the url on another server
			if (strlen(get_setting('fs_balancer_clients')) > 10)
			{
				preg_match('/([\d]+)/', $post->media_filename, $matches);

				if (isset($matches[1]))
				{
					$balancer_servers = get_setting('fs_fuuka_boards_url', site_url()) . '/' . $board->shortname . '/'
						. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
				}
			}

			return get_setting('fs_fuuka_boards_url', site_url()) . '/' . $board->shortname . '/'
				. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
		}

		if ($thumbnail === TRUE)
		{
			$post->preview_h = 150;
			$post->preview_w = 150;
			return site_url() . 'content/themes/default/images/image_missing.jpg';
		}

		return FALSE;
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @return bool|string
	 */
	function get_remote_media_link($board, $post)
	{
		if (!$post->media_filename && !$post->media_hash)
		{
			return FALSE;
		}

		if ($board->archive)
		{
			// ignore webkit and opera user agents
			if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(opera|webkit)/i', $_SERVER['HTTP_USER_AGENT']))
			{
				return $board->images_url . $post->media_filename;
			}

			return site_url(array($board->shortname, 'redirect')) . $post->media_filename;
		}
		else
		{
			if (file_exists($this->get_media_dir($board, $post)) !== FALSE)
			{
				return $this->get_media_link($board, $post);
			}
			else
			{
				return FALSE;
			}
		}
	}


	/**
	 * @param mixed $media_hash
	 * @param bool $urlsafe
	 * @return bool|string
	 */
	function get_media_hash($media_hash, $urlsafe = FALSE)
	{
		if (is_object($media_hash) || is_array($media_hash))
		{
			if (!$media_hash->media_filename)
			{
				return FALSE;
			}

			$media_hash = $media_hash->media_hash;
		}
		else
		{
			if (strlen(trim($media_hash)) == 0)
			{
				return FALSE;
			}
		}

		// return a safely escaped media hash for urls or un-altered media hash
		if ($urlsafe === TRUE)
		{
			return substr(urlsafe_b64encode(urlsafe_b64decode($media_hash)), 0, -2);
		}
		else
		{
			return base64_encode(urlsafe_b64decode($media_hash));
		}
	}


	/**
	 * @param string $name
	 * @return array
	 */
	function process_name($name)
	{
		// define variables
		$matches = array();
		$normal_trip = '';
		$secure_trip = '';

		if (preg_match("'^(.*?)(#)(.*)$'", $name, $matches))
		{
			$matches_trip = array();
			$name = trim($matches[1]);

			preg_match("'^(.*?)(?:#+(.*))?$'", $matches[3], $matches_trip);

			if (count($matches_trip) > 1)
			{
				$normal_trip = $this->process_tripcode($matches_trip[1]);
				$normal_trip = $normal_trip ? '!' . $normal_trip : '';
			}

			if (count($matches_trip) > 2)
			{
				$secure_trip = '!!' . $this->process_secure_tripcode($matches_trip[2]);
			}
		}

		return array($name, $normal_trip . $secure_trip);
	}


	/**
	 * @param string $plain
	 * @return string
	 */
	function process_tripcode($plain)
	{
		if (trim($plain) == '')
		{
			return '';
		}

		$trip = mb_convert_encoding($plain, 'SJIS', 'UTF-8');
		$trip = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&#39;', '&lt;', '&gt;'), $trip);

		$salt = substr($pass . 'H.', 1, 2);
		$salt = preg_replace('/[^.-z]/', '.', $salt);

		return substr(crypt($trip, $salt), -10);
	}


	/**
	 * @param string $plain
	 * @return string
	 */
	function process_secure_tripcode($plain)
	{
		return substr(base64_encode(sha1($plain . base64_decode(FOOLFUUKA_SECURE_TRIPCODE_SALT), TRUE)), 0, 11);
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @param bool $clean
	 * @param bool $build
	 */
	function process_post($board, $post, $clean = TRUE, $build = FALSE)
	{
		$this->load->helper('text');
		$this->current_p = $post;

		$post->safe_media_hash = $this->get_media_hash($post, TRUE);
		$post->remote_media_link = $this->get_remote_media_link($board, $post);
		$post->media_link = $this->get_media_link($board, $post);
		$post->thumb_link = $this->get_media_link($board, $post, TRUE);
		$post->comment_processed = @iconv('UTF-8', 'UTF-8//IGNORE', $this->process_comment($board, $post));
		$post->comment = @iconv('UTF-8', 'UTF-8//IGNORE', $post->comment);
		
		// gotta change the timestamp of the archives to GMT
		if($board->archive)
		{
			$post->original_timestamp = $post->timestamp;
			$date = new DateTime();
			$date = $date->createFromFormat(
				'Y-m-d H:i:s', 
				date('Y-m-d H:i:s', $post->timestamp), 
				new DateTimeZone('America/New_York')
			);
			$date->setTimezone(new DateTimeZone('UTC'));
			$post->timestamp = $date->getTimestamp();
		}

		$elements = array('title', 'name', 'email', 'trip', 'media', 'preview', 'media_filename', 'media_hash');

		foreach($elements as $element)
		{
			$element_processed = $element . '_processed';

			$post->$element_processed = @iconv('UTF-8', 'UTF-8//IGNORE', fuuka_htmlescape($post->$element));
			$post->$element = @iconv('UTF-8', 'UTF-8//IGNORE', $post->$element);
		}

		// remove both ip and delpass from public view
		if ($clean === TRUE)
		{
			if (!$this->tank_auth->is_allowed())
			{
				unset($post->id);
			}

			unset($post->delpass);
		}

		if ($build === TRUE)
		{
			$post->format = $this->build_board_comment($board, $post);
		}
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @param array $media
	 * @param string $media_hash
	 * @return array|bool
	 */
	function process_media($board, $post_id, $file, $media_hash, $duplicate = NULL)
	{
		// only allow media on internal boards
		if ($board->archive)
		{
			return FALSE;
		}

		// default variables
		$media_exists = FALSE;
		$thumb_exists = FALSE;

		// only run the check when iterated with duplicate
		if ($duplicate === NULL)
		{
			// check *_images table for media hash
			$check = $this->db->query('
				SELECT * FROM ' . $this->radix->get_table($board, '_images') . '
				WHERE media_hash = ? LIMIT 0, 1
			',
				array($hash)
			);

			// if exists, re-run process with duplicate set
			if ($check->num_rows() > 0)
			{
				return $this->process_media($board, $post_id, $file, $hash, $check->row());
			}
		}

		// generate unique filename with timestamp, this will be stored with the post
		$media_unixtime = time() . rand(1000, 9999);
		$media_filename = $media_unixtime . strtolower($file['file_ext']);
		$thumb_filename = $media_unixtime . 's' . strtolower($file['file_ext']);

		// set default locations of media directories and image directory structure
		$board_directory = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/';
		$thumb_filepath = $board_directory . 'thumb/' . substr($media_unixtime, 0, 4) . '/' . substr($media_unixtime, 4, 2) . '/';
		$media_filepath = $board_directory . 'image/' . substr($media_unixtime, 0, 4) . '/' . substr($media_unixtime, 4, 2) . '/';

		// check for any type of duplicate records or information and override default locations
		if ($duplicate !== NULL)
		{
			// handle full media
			if ($duplicate->media_filename !== NULL)
			{
				$media_exists = TRUE;

				$media_existing = $duplicate->media_filename;
				$media_filepath = $board_directory . 'image/'
					. substr($duplicate->media_filename, 0, 4) . '/' . substr($duplicate->media_filename, 4, 2) . '/';
			}

			// generate full file paths for missing files only
			if ($duplicate->media_filename === NULL || file_exists($media_filepath . $duplicate->media_filename) === FALSE)
			{
				mkdir($media_filepath, 0700, TRUE);
			}

			// handle thumbs
			if ($post_id == 0)
			{
				// thumb op
				if ($duplicate->preview_op !== NULL)
				{
					$thumb_exists = TRUE;

					$thumb_existing = $duplicate->preview_op;
					$thumb_filepath = $board_directory . 'thumb/'
						. substr($duplicate->preview_op, 0, 4) . '/' . substr($duplicate->preview_op, 4, 2) . '/';
				}

				// generate full file paths for missing files only
				if ($duplicate->preview_op === NULL || file_exists($media_filepath . $duplicate->preview_op) === FALSE)
				{
					mkdir($thumb_filepath, 0700, TRUE);
				}
			}
			else
			{
				// thumb re
				if ($duplicate->preview_reply !== NULL)
				{
					$thumb_exists = TRUE;

					$thumb_existing = $duplicate->preview_reply;
					$thumb_filepath = $board_directory . 'thumb/'
						. substr($duplicate->preview_reply, 0, 4) . '/' . substr($duplicate->preview_reply, 4, 2) . '/';
				}

				// generate full file paths for missing files only
				if ($duplicate->preview_reply === NULL || file_exists($media_filepath . $duplicate->preview_reply) === FALSE)
				{
					mkdir($thumb_filepath, 0700, TRUE);
				}
			}
		}
		else
		{
			// generate full file paths for everything
			mkdir($media_filepath, 0700, TRUE);
			mkdir($thumb_filepath, 0700, TRUE);
		}

		// relocate the media file to proper location
		if (!copy($file['full_path'], $media_filepath . (($media_exists) ? $media_existing : $media_filename)))
		{
			log_message('error', 'post.php/process_media: failed to move media file');
			return FALSE;
		}

		// remove the media file
		if (!unlink($file['full_path']))
		{
			log_message('error', 'post.php/process_media: failed to remove media file from cache directory');
		}

		// determine the correct thumbnail dimensions
		if ($post_id == 0)
		{
			$thumb_ratio = 250;
		}
		else
		{
			$thumb_ratio = 125;
		}

		// generate thumbnail
		if ($file['image_width'] > $thumb_ratio || $file['image_height'] > $thumb_ratio)
		{
			$media_config = array(
				'image_library' => (find_imagick()) ? 'ImageMagick' : 'GD2',
				'library_path'  => (find_imagick()) ? get_setting('fs_serv_imagick_path', '/usr/bin') : '',
				'source_image'  => $media_filepath . (($media_exists) ? $media_existing : $media_filename),
				'new_image'     => $thumb_filepath . (($thumb_exists) ? $thumb_existing : $thumb_filename),
				'width'         => ($file['image_width'] > $thumb_ratio) ? $thumb_ratio : $file['image_width'],
				'height'        => ($file['image_height'] > $thumb_ratio) ? $thumb_ratio : $file['image_height'],
			);

			$CI = & get_instance();
			$CI->load->library('image_lib');

			$CI->image_lib->initialize($media_config);
			if (!$CI->image_lib->resize())
			{
				log_message('error', 'post.php/process_media: failed to generate thumbnail');
				return FALSE;
			}

			$CI->image_lib->clear();
			$thumb_dimensions = @getimagesize($thumb_filepath . (($thumb_exists) ? $thumb_existing : $thumb_filename));
		}
		else
		{
			$thumb_filename = $media_filename;
			$thumb_dimensions = array($file['image_width'], $file['image_height']);
		}

		return array(
			$thumb_filename, $thumb_dimensions[0], $thumb_dimensions[1],
			$file['file_name'], $file['image_width'], $file['image_height'],
			floor($file['file_size'] * 1024), $media_hash, $media_filename, $media_unixtime
		);
	}


	/**
	 * @param object $board
	 * @param object $row
	 * @return string
	 */
	function process_comment($board, $post)
	{
		$CI = & get_instance();

		// default variables
		$find = "'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i";
		$html = '\\1<span class="greentext">\\2</span>\\3';

		if ($this->features === FALSE)
		{
			if ($this->fu_theme == 'fuuka')
			{
				$html = '\\1<span class="greentext">\\2</span>\\3';
			}

			if ($this->fu_theme == 'yotsuba')
			{
				$html = '\\1<font class="unkfunc">\\2</font>\\3';
			}
		}

		$comment = $post->comment;

		// this stores an array of moot's formatting that must be removed
		$special = array(
			'<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">',
			'<span style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">'
		);

		// remove moot's special formatting
		if ($post->capcode == 'A' && mb_strpos($comment, $special[0]) == 0)
		{
			$comment = str_replace($special[0], '', $comment);

			if (mb_substr($comment, -6, 6) == '</div>')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 6);
			}
		}

		if ($post->capcode == 'A' && mb_strpos($comment, $special[1]) == 0)
		{
			$comment = str_replace($special[1], '', $comment);

			if (mb_substr($comment, -10, 10) == '[/spoiler]')
			{
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 10);
			}
		}

		$comment = htmlentities($comment, ENT_COMPAT | ENT_IGNORE, 'UTF-8', FALSE);

		// preg_replace_callback handle
		$this->current_board_for_prc = $board;

		// format entire comment
		$comment = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i",
			array(get_class($this), 'process_internal_links'), $comment);

		$comment = preg_replace_callback("'(&gt;&gt;&gt;(\/(\w+)\/(\d+(?:,\d+)?)?(\/?)))'i",
			array(get_class($this), 'process_crossboard_links'), $comment);

		$comment = auto_linkify($comment, 'url', TRUE);
		$comment = preg_replace($find, $html, $comment);
		$comment = parse_bbcode($comment, ($board->archive && $post->subnum) ? TRUE : FALSE);

		// additional formatting
		if ($board->archive && $post->subnum)
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

		return nl2br(trim($comment));
	}


	/**
	 * @param array $matches
	 * @return string
	 */
	function process_internal_links($matches)
	{
		$num = $matches[2];
		$num_id = str_replace(',', '_', $num);

		$html = array(
			'prefix' => '',
			'suffix' => '',
			'urltag' => '#',
			'option' => ' class="backlink" data-function="highlight" data-backlink="true" data-post="' . $num_id . '"',
			'option_op' => ' class="backlink op" data-function="highlight" data-backlink="true" data-post="' . $num_id . '"',
			'option_backlink' => ' class="backlink" data-function="highlight" data-backlink="true" data-post="'
				. $this->current_p->num . (($this->current_p->subnum == 0) ? '' : '_' . $this->current_p->subnum) . '"',
		);

		if ($this->features === FALSE)
		{
			if ($this->fu_theme == 'fuuka')
			{
				$html = array(
					'prefix' => '<span class="unkfunc">',
					'suffix' => '</span>',
					'urltag' => '#',
					'option' => ' onclick=replyhighlight(\'p' . $num_id . '\');"',
					'option_op' => '',
					'option_backlink' => '',
				);
			}

			if ($this->fu_theme == 'yotsuba')
			{
				$html = array(
					'prefix' => '<font class="unkfunc">',
					'suffix' => '</font>',
					'urltag' => '#',
					'option' => ' class="quotelink" onclick=replyhl(\'' . $num_id . '\');"',
					'option_op' => '',
					'option_backlink' => '',
				);
			}
		}

		$this->backlinks[$num_id][$this->current_p->num] = $html['prefix']
			. '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread',
			($this->current_p->parent == 0) ? $this->current_p->num : $this->current_p->parent)) . $html['urltag']
			. $this->current_p->num . (($this->current_p->subnum == 0) ? '' : '_' . $this->current_p->subnum)
			. '"' . $html['option_backlink'] . '>&gt;&gt;'
			. $this->current_p->num . (($this->current_p->subnum == 0) ? '' : ',' . $this->current_p->subnum)
			. '</a>' . $html['suffix'];

		if (array_key_exists($num, $this->posts_arr))
		{
			if ($this->backlinks_hash_only_url)
			{
				return $html['prefix'] . '<a href="' . $html['urltag'] . $num_id . '"' . $html['option_op']
					. '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
			}

			return $html['prefix'] . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', $num))
				. $html['urltag'] . $num_id . '"' . $html['option_op'] . '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
		}

		foreach ($this->posts_arr as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				if ($this->backlinks_hash_only_url)
				{
					return $html['prefix'] . '<a href="' . $html['urltag'] . $num_id . '"' . $html['option']
						. '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
				}

				return $html['prefix'] . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', $key))
					. $html['urltag'] . $num_id . '"' . $html['option'] . '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
			}
		}

		// what is $key?
		if ($this->realtime === TRUE)
		{
			return $html['prefix'] . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', $key))
				. $html['urltag'] . $num_id . '"' . $html['option'] . '>&gt;&gt;' . $num . '</a>' . $html['suffix'];
		}

		return $html['prefix'] . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'post', $num_id))
			. '">&gt;&gt;' . $num . '</a>' . $html['suffix'];

		// return un-altered
		return $matches[0];
	}


	/**
	 * @param array $matches
	 * @return string
	 */
	function process_crossboard_links($matches)
	{
		$shortname = $matches[3];
		$url = $matches[2];
		$num = $matches[4];

		$html = array(
			'prefix' => '',
			'suffix' => ''
		);

		if ($this->features === FALSE)
		{
			if ($this->fu_theme == 'fuuka')
			{
				$html = array(
					'prefix' => '<span class="unkfunc">',
					'suffix' => '</span>'
				);
			}

			if ($this->fu_theme == 'yotsuba')
			{
				$html = array(
					'prefix' => '<font class="unkfunc">',
					'suffix' => '</font>'
				);
			}
		}

		$board = $this->radix->get_by_shortname($shortname);
		if (!$board)
		{
			if ($num)
			{
				return $html['prefix'] . '<a href="http://boards.4chan.org/' . $shortname . '/res/' . $num . '">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];
			}

			return $html['prefix'] . '<a href="http://boards.4chan.org/' . $shortname . '/">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];
		}

		if ($num)
		{
			return $html['prefix'] . '<a href="' . site_url(array($board->shortname, 'post', $num)) . '">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];
		}

		return $html['prefix'] . '<a href="' . site_url($board->shortname) . '">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];

		// return un-altered
		return $matches[0];
	}


	/**
	 * @param object $board
	 * @param object $p
	 * @return string
	 */
	function build_board_comment($board, $p)
	{
		// load the functions from the current theme, else load the default one
		if (file_exists('content/themes/' . $this->fu_theme . '/theme_functions.php'))
		{
			require_once('content/themes/' . $this->fu_theme . '/theme_functions.php');
		}
		else
		{
			require_once('content/themes/' . $this->config->item('theme_extends') . '/theme_functions.php');
		}

		//require_once
		ob_start();

		if (file_exists('content/themes/' . $this->fu_theme . '/views/board_comment.php'))
		{
			include('content/themes/' . $this->fu_theme . '/views/board_comment.php');
		}
		else
		{
			include('content/themes/' . $this->config->item('theme_extends') . '/views/board_comment.php');
		}

		$string = ob_get_contents();
		ob_end_clean();

		return $string;
	}


	/**
	 * @param object $board
	 * @param int $num
	 * @return array
	 */
	function check_thread($board, $num)
	{
		if ($num == 0)
		{
			return array('invalid_thread' => TRUE);
		}

		// grab the entire thread
		$query = $this->db->query('
			(
				SELECT * FROM ' . $this->radix->get_table($board) . '
				WHERE num = ?
			)
			UNION
			(
				SELECT * FROM ' . $this->radix->get_table($board) . '
				WHERE parent = ?
			)
		',
			array($num, $num)
		);

		// thread was not found
		if ($query->num_rows() == 0)
		{
			return array('invalid_thread' => TRUE);
		}

		// define variables
		$thread_op_present = FALSE;
		$thread_last_bump = 0;
		$counter = array('posts' => 0, 'images' => 0);

		foreach ($query->result() as $post)
		{
			// we need to find if there's the OP in the list
			// let's be strict, we want the $num to be the OP
			if ($post->parent == 0 && $post->subnum == 0 && $post->num === $num)
			{
				$thread_op_present = TRUE;
			}

			if($post->subnum == 0 && $thread_last_bump < $post->timestamp)
			{
				$thread_last_bump = $post->timestamp;
			}

			if ($post->orig_filename)
			{
				$counter['images']++;
			}

			$counter['posts']++;
		}

		// free up result
		$query->free_result();

		// we didn't point to the thread OP, this is not a thread
		if (!$thread_op_present)
		{
			return array('invalid_thread' => TRUE);
		}

		// time check
		if(time() - $thread_last_bump > 432000)
		{
			return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE);
		}

		if ($counter['posts'] > 400)
		{
			if ($counter['images'] > 200)
			{
				return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE);
			}
			else
			{
				return array('thread_dead' => TRUE);
			}
		}
		else if ($counter['images'] > 200)
		{
			return array('disable_image_upload' => TRUE);
		}

		return array('valid_thread' => TRUE);
	}


	/**
	 * @param object $board
	 * @param array $args
	 * @param array $options
	 * @return array
	 */
	function get_search($board, $args, $options = array())
	{
		// default variables
		$process = TRUE;
		$clean = TRUE;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// set a valid value for $search['page']
		if ($args['page'])
		{
			if (!is_numeric($args['page']))
			{
				log_message('error', 'post.php/get_search: invalid page argument');
				show_404();
			}

			$args['page'] = intval($args['page']);
		}
		else
		{
			$args['page'] = 1;
		}

		// if global or board => use sphinx, else mysql for board only
		// global search requires sphinx
		if (($board === FALSE && get_setting('fs_sphinx_global')) || $board->sphinx)
		{
			$this->load->library('SphinxQL');

			// establish connection to sphinx
			$sphinx_server = explode(':', get_setting('fu_sphinx_listen', FOOL_PREF_SPHINX_LISTEN));

			if (!$this->sphinxql->set_server($sphinx_server[0], $sphinx_server[1]))
				return array('error' => _('The search backend is currently not online. Try later or contact us in case it\'s offline for too long.'));

			// determine if all boards will be used for search or not
			if ($board === FALSE)
			{
				$indexes = array();

				foreach ($this->radix->get_all() as $board)
				{
					// ignore boards that don't have sphinx enabled
					if (!$radix->sphinx)
					{
						continue;
					}

					$indexes[] = $board->shortname . '_ancient';
					$indexes[] = $board->shortname . '_main';
					$indexes[] = $board->shortname . '_delta';
				}
			}
			else
			{
				$indexes = array(
					$board->shortname . '_ancient',
					$board->shortname . '_main',
					$board->shortname . '_delta'
				);
			}

			// set db->from with indexes loaded
			$this->db->from($indexes, FALSE, FALSE);

			// begin filtering search params
			if ($args['text'])
			{
				if (mb_strlen($args['text']) < 1)
				{
					return array();
				}

				$this->db->sphinx_match('comment', $args['text'], 'half', TRUE);
			}
			if ($args['subject'])
			{
				$this->db->sphinx_match('title', $args['subject'], 'full', TRUE);
			}
			if ($args['username'])
			{
				$this->db->sphinx_match('name', $args['username'], 'full', TRUE);
			}
			if ($args['tripcode'])
			{
				$this->db->sphinx_match('trip', $args['tripcode'], 'full', TRUE, TRUE);
			}
			if ($args['email'])
			{
				$this->db->sphinx_match('email', $args['email'], 'full', TRUE);
			}
			if ($args['filename'])
			{
				$this->db->sphinx_match('media', $args['filename'], 'full', TRUE);
			}
			if ($args['capcode'] == 'admin')
			{
				$this->db->where('cap', 3);
			}
			if ($args['capcode'] == 'mod')
			{
				$this->db->where('cap', 2);
			}
			if ($args['capcode'] == 'user')
			{
				$this->db->where('cap', 1);
			}
			if ($args['deleted'] == 'deleted')
			{
				$this->db->where('is_deleted', 1);
			}
			if ($args['deleted'] == 'not-deleted')
			{
				$this->db->where('is_deleted', 0);
			}
			if ($args['ghost'] == 'only')
			{
				$this->db->where('is_internal', 1);
			}
			if ($args['ghost'] == 'none')
			{
				$this->db->where('is_internal', 0);
			}
			if ($args['type'] == 'op')
			{
				$this->db->where('is_op', 1);
			}
			if ($args['type'] == 'posts')
			{
				$this->db->where('is_op', 0);
			}
			if ($args['filter'] == 'image')
			{
				$this->db->where('has_image', 0);
			}
			if ($args['filter'] == 'text')
			{
				$this->db->where('has_image', 1);
			}
			if ($args['start'])
			{
				$this->db->where('timestamp >=', intval(strtotime($args['start'])));
			}
			if ($args['end'])
			{
				$this->db->where('timestamp <=', intval(strtotime($args['end'])));
			}
			if ($args['order'] == 'asc')
			{
				$this->db->where('timestamp', 'ASC');
			}
			else
			{
				$this->db->where('timestamp', 'DESC');
			}

			// set sphinx options
			$this->db->limit(25, ($args['page'] * 25) - 25)
				->sphinx_option('max_matches', 5000)
				->sphinx_option('reverse_scan', ($args['order'] == 'asc') ? 0 : 1);

			// send sphinxql to searchd
			$search = $this->sphinxql->query($this->db->statement());

			if (empty($search['matches']))
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			// populate array to query for full records
			$sql = array();

			foreach ($search['matches'] as $post => $result)
			{
				$sql[] = '
					(
						SELECT *, ' . $result['board'] . ' AS board
						FROM ' . $this->radix->get_table($this->radix->get_by_id($result['board'])) . '
						WHERE num = ' . $result['num'] . ' AND subnum = ' . $result['subnum'] . '
					)
				';
			}

			// query mysql for full records
			$query = $this->db->query(implode('UNION', $sql) . ' ORDER BY timestamp ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC'));
			$total = $search['total_found'];
		}
		else /* use mysql as fallback for non-sphinx indexed boards */
		{
			// begin cache of entire sql statement
			$this->db->start_cache();

			// set db->from with board
			$this->db->from($this->radix->get_table($board), FALSE);

			// begin filtering search params
			if ($args['text'])
			{
				if (mb_strlen($args['text']) < 1)
				{
					return array();
				}

				$this->db->like('comment', rawurldecode($args['text']));
			}
			if ($args['subject'])
			{
				$this->db->like('title', rawurldecode($args['subject']));
			}
			if ($args['username'])
			{
				$this->db->like('name', rawurldecode($args['username']));
				$this->db->use_index('name_index');
			}
			if ($args['tripcode'])
			{
				$this->db->like('trip', rawurldecode($args['tripcode']));
				$this->db->use_index('trip_index');
			}
			if ($args['email'])
			{
				$this->db->like('email', rawurldecode($args['email']));
				$this->db->use_index('email_index');
			}
			
			// @todo add index on media
			if ($args['filename'])
			{
				$this->db->like('media', rawurldecode($args['media']));
				$this->db->use_index('media_index');
			}
			if ($args['capcode'] == 'admin')
			{
				$this->db->where('capcode', 'A');
			}
			if ($args['capcode'] == 'mod')
			{
				$this->db->where('capcode', 'M');
			}
			if ($args['capcode'] == 'user')
			{
				$this->db->where('capcode !=', 'A');
				$this->db->where('capcode !=', 'M');
			}
			if ($args['deleted'] == 'deleted')
			{
				$this->db->where('deleted', 1);
			}
			if ($args['deleted'] == 'not-deleted')
			{
				$this->db->where('deleted', 0);
			}
			if ($args['ghost'] == 'only')
			{
				$this->db->where('subnum <>', 0);
				$this->db->use_index('subnum_index');
			}
			if ($args['ghost'] == 'none')
			{
				$this->db->where('subnum', 0);
				$this->db->use_index('subnum_index');
			}
			if ($args['type'] == 'op')
			{
				$this->db->where('parent', 0);
				$this->db->use_index('parent_index');
			}
			if ($args['type'] == 'posts')
			{
				$this->db->where('parent <>', 0);
				$this->db->use_index('parent_index');
			}
			if ($args['filter'] == 'image')
			{
				$this->db->where('media_hash IS NOT NULL');
				$this->db->use_index('media_hash_index');
			}
			if ($args['filter'] == 'text')
			{
				$this->db->where('media_hash IS NULL');
				$this->db->use_index('media_hash_index');
			}
			if ($args['start'])
			{
				$this->db->where('timestamp >=', intval(strtotime($args['start'])));
				$this->db->use_index('timestamp_index');
			}
			if ($args['end'])
			{
				$this->db->where('timestamp <=', intval(strtotime($args['end'])));
				$this->db->use_index('timestamp_index');
			}

			// stop cache of entire sql statement, the main query is stored
			$this->db->stop_cache();

			// fetch initial total first...
			$this->db->limit(5000);

			// check if we have any results
			$check = $this->db->query($this->db->statement());
			if ($check->num_rows() == 0)
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			// modify cached query for additional params
			if ($args['order'] == 'asc')
			{
				$this->db->order_by('timestamp', 'ASC');
				$this->db->use_index('timestamp_index');
			}
			else
			{
				$this->db->order_by('timestamp', 'DESC');
				$this->db->use_index('timestamp_index');
			}


			// set query options
			$this->db->limit(25, ($args['page'] * 25) - 25);

			// query mysql for full records
			$query = $this->db->query($this->db->statement());
			$total = $check->num_rows();

			// flush cache to avoid issues with regular queries
			$this->db->flush_cache();
		}

		// process all results to be displayed
		$results = array();

		$this->populate_posts_arr($query->result());

		foreach ($query->result() as $post)
		{
			// override board with full board information
			if (isset($post->board))
			{
				$post->board = $this->radix->get_by_id($post->board);
				$board = $post->board;
			}

			// populate posts_arr array
			$this->populate_posts_arr($post);

			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}

			$results[0]['posts'][] = $post;
		}

		return array('posts' => $results, 'total_found' => $total);
	}


	/**
	 * @param object $board
	 * @param int $page
	 * @param array $options
	 * @return array|bool
	 */
	function get_latest($board, $page = 1, $options = array())
	{
		// default variables
		$per_page = 20;
		$process = TRUE;
		$clean = TRUE;
		$type = 'by_post';

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'by_post':

				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT *, parent as unq_parent
						FROM ' . $this->radix->get_table($board, '_threads') . '
						ORDER BY time_bump DESC LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
					' . $this->sql_media_join($board, 'g') . '
					' . $this->sql_report_join($board, 'g') . '
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				$query_pages = $this->db->query('
					SELECT FLOOR(COUNT(parent)/' . intval($per_page) . ')+1 AS pages
					FROM ' . $this->radix->get_table($board, '_threads') . '
				');

				break;

			case 'by_thread':

				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT *, parent as unq_parent
						FROM ' . $this->radix->get_table($board, '_threads') . '
						ORDER BY parent DESC LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
					' . $this->sql_media_join($board, 'g') . '
					' . $this->sql_report_join($board, 'g') . '
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				$query_pages = $this->db->query('
					SELECT FLOOR(COUNT(parent)/' . intval($per_page) . ')+1 AS pages
					FROM ' . $this->radix->get_table($board, '_threads') . '
				');

				break;

			case 'ghost':

				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT *, parent as unq_parent
						FROM ' . $this->radix->get_table($board, '_threads') . '
						WHERE time_ghost_bump IS NOT NULL
						ORDER BY time_ghost_bump DESC LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
					' . $this->sql_media_join($board, 'g') . '
					' . $this->sql_report_join($board, 'g') . '
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				$query_pages = $this->db->query('
					SELECT FLOOR(COUNT(parent)/' . intval($per_page) . ')+1 AS pages
					FROM ' . $this->radix->get_table($board, '_threads') . '
					WHERE time_ghost_bump IS NOT NULL;
				');

				break;

			default:
				log_message('error', 'post.php/get_latest: invalid or missing type argument');
				return FALSE;
		}

		if ($query->num_rows() == 0)
		{
			return array(
				'result' => array('op' => array(), 'posts' => array()),
				'pages' => NULL
			);
		}

		// set total pages found
		$pages = $query_pages->row()->pages;
		if ($pages <= 1)
		{
			$pages = NULL;
		}

		// free up memory
		$query_pages->free_result();

		// populate arrays with posts
		$threads = array();
		$results = array();
		$sql_arr = array();

		foreach ($query->result() as $thread)
		{
			$threads[$thread->unq_parent] = array('replies' => $thread->nreplies, 'images' => $thread->nimages);

			$sql_arr[] = '
				(
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE parent = ' . $thread->unq_parent . '
					ORDER BY num DESC, subnum DESC
					LIMIT 0, 5
				)
			';
		}

		$query_posts = $this->db->query(implode('UNION', $sql_arr) . ' ORDER BY num DESC;');
		$posts = array_merge($query->result(), array_reverse($query_posts->result()));

		// populate posts_arr array
		$this->populate_posts_arr($query->result());

		// populate results array and order posts
		foreach ($posts as $post)
		{
			$post_num = ($post->parent > 0) ? $post->parent : $post->num;

			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}

			if (!isset($results[$post_num]['omitted']))
			{
				foreach ($threads as $parent => $counter)
				{
					if ($parent == $post_num)
					{
						$results[$post_num] = array(
							'omitted' => ($counter['replies'] - 5),
							'images_omitted' => $counter['images']
						);
					}
				}
			}

			if ($post->parent > 0)
			{
				if ($post->preview)
				{
					$results[$post->parent]['images_omitted']--;
				}

				$results[$post->parent]['posts'][] = $post;
			}
			else
			{
				$results[$post->num]['op'] = $post;
			}
		}

		return array('result' => $results, 'pages' => $pages);
	}


	/**
	 * @param object $board
	 * @param int $num
	 * @param array $options
	 * @return array|bool
	 */
	function get_thread($board, $num, $options = array())
	{
		// default variables
		$process = TRUE;
		$clean = TRUE;
		$type = 'thread';
		$type_extra = array();
		$realtime = FALSE;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'from_doc_id':

				if (!isset($type_extra['latest_doc_id']) || !is_natural($type_extra['latest_doc_id']))
				{
					log_message('error', 'post.php/get_thread: invalid last_doc_id argument');
					return FALSE;
				}

				$query = $this->db->query('
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE parent = ? AND doc_id > ?
					ORDER BY num, subnum ASC
				',
					array($num, $type_extra['latest_doc_id'])
				);

				break;

			case 'ghosts':

				$query = $this->db->query('
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE parent = ? AND subnum <> 0
					ORDER BY num, subnum ASC
				',
					array($num)
				);

				break;

			case 'last_x':

				if (!isset($type_extra['last_limit']) || !is_natural($type_extra['last_limit']))
				{
					log_message('error', 'post.php/get_thread: invalid last_limit argument');
					return FALSE;
				}

				$query = $this->db->query('
					SELECT *
					FROM
					(
						(
							SELECT * FROM ' . $this->radix->get_table($board) . '
							WHERE num = ? LIMIT 0, 1
						)
						UNION
						(
							SELECT * FROM ' . $this->radix->get_table($board) . '
							WHERE parent = ?
							ORDER BY num DESC, subnum DESC LIMIT ?
						)
					) AS x
					' . $this->sql_media_join($board, 'x') . '
					' . $this->sql_report_join($board, 'x') . '
					ORDER BY num, subnum ASC
				',
					array(
						$num, $num, intval($type_extra['last_limit'])
					)
				);

				break;

			case 'thread':

				$query = $this->db->query('
					(
						SELECT * FROM ' . $this->radix->get_table($board) . '
						' . $this->sql_media_join($board) . '
						' . $this->sql_report_join($board) . '
						WHERE num = ?
					)
					UNION
					(
						SELECT * FROM ' . $this->radix->get_table($board) . '
						' . $this->sql_media_join($board) . '
						' . $this->sql_report_join($board) . '
						WHERE parent = ?
					)
					ORDER BY num, subnum ASC
				',
					array($num, $num)
				);

				break;

			default:
				log_message('error', 'post.php/show_thread: invalid or missing type argument');
				return FALSE;
		}

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		// set global variables for special usage
		if ($realtime === TRUE)
		{
			$this->realtime = TRUE;
		}

		$this->backlinks_hash_only_url = TRUE;

		// populate posts_arr array
		$this->populate_posts_arr($query->result());

		// process entire thread and store in $result array
		$result = array();

		foreach ($query->result() as $post)
		{
			if ($process === TRUE)
			{
				if ($post->parent != 0)
				{
					$this->process_post($board, $post, $clean, $realtime);
				}
				else
				{
					$this->process_post($board, $post, TRUE, TRUE);
				}
			}

			if ($post->parent > 0)
			{
				$result[$post->parent]['posts'][$post->num . (($post->subnum == 0) ? '' : '_' . $post->subnum)] = $post;
			}
			else
			{
				$result[$post->num]['op'] = $post;
			}
		}

		// free up memory
		$query->free_result();

		// populate results with backlinks
		foreach ($this->backlinks as $key => $backlinks)
		{
			if (isset($result[$num]['op']) && $result[$num]['op']->num == $key)
			{
				$result[$num]['op']->backlinks = array_unique($backlinks);
			}
			else if (isset($result[$num]['posts'][$key]))
			{
				$result[$num]['posts'][$key]->backlinks = array_unique($backlinks);
			}
		}

		// reset module settings
		$this->backlinks_hash_only_url = FALSE;
		$this->realtime = FALSE;

		return $result;
	}


	/**
	 * @param object $board
	 * @param int $page
	 * @param array $options
	 * @return array|bool
	 */
	function get_gallery($board, $page = 1, $options = array())
	{
		// default variables
		$per_page = 200;
		$process = TRUE;
		$clean = TRUE;
		$type = 'by_thread';

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'by_image':

				$query = $this->db->query('
					SELECT * FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE media_filename IS NOT NULL
					ORDER BY timestamp DESC LIMIT ?, ?
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				$query_total = $this->db->query('
					SELECT COUNT(media_filename) AS count
					FROM ' . $this->radix->get_table($board) . '
					WHERE media_filename IS NOT NULL
				');

				break;

			case 'by_thread':

				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT *, parent as unq_parent
						FROM ' . $this->radix->get_table($board, '_threads') . '
						ORDER BY time_op DESC LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
					' . $this->sql_media_join($board, 'g') . '
					' . $this->sql_report_join($board, 'g') . '
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				$query_total = $this->db->query('
					SELECT COUNT(parent) AS count
					FROM ' . $this->radix->get_table($board, '_threads') . '
				');

				break;

			default:
				log_message('error', 'post.php/get_latest: invalid or missing type argument');
				return FALSE;
		}

		// set total images found
		$total = $query_total->row()->count;
		$query_total->free_result();

		// populate result array
		$results = array();

		foreach ($query->result() as $key => $post)
		{
			if ($post->preview)
			{
				$this->process_post($board, $post, $clean, $process);
				$results[$post->num] = $post;
			}
		}

		return array('threads' => $results, 'total_found' => $total);
	}


	/**
	 * @param int $page
	 * @return array|bool
	 */
	function get_reports($page = 1)
	{
		$this->load->model('report');

		// populate multi_posts array to fetch
		$multi_posts = array();

		foreach ($this->report->get_reports($page) as $post)
		{
			$multi_posts[] = array(
				'board_id' => $post->board_id,
				'doc_id'   => array($post->doc_id)
			);
		}

		return array('posts' => $this->get_multi_posts($multi_posts), 'total_found' => $this->report->get_count());
	}


	/**
	 * @param array $multi_posts
	 * @param null|string $order_by
	 * @return array|bool
	 */
	function get_multi_posts($multi_posts = array(), $order_by = NULL)
	{
		// populate sql array
		$sql = array();

		foreach ($multi_posts as $posts)
		{
			// posts => [board_id, doc_id => [1, 2, 3]]
			if (isset($posts['board_id']) && isset($posts['doc_id']))
			{
				$board = $this->radix->get_by_id($posts['board_id']);
				$sql[] = '
					(
						SELECT *, CONCAT(' . $this->db->escape($posts['board_id']) . ') AS board_id
						FROM ' . $this->radix->get_table($board) . ' AS g
						' . $this->sql_media_join($board, 'g') . '
						' . $this->sql_report_join($board, 'g') . '
						WHERE g.`doc_id` = ' . implode(' OR g.`doc_id` = ', $posts['doc_id']) . '
					)
				';
			}
		}

		if (empty($sql))
		{
			return array();
		}

		// order results properly with string argument
		$query = $this->db->query(implode('UNION', $sql) . ($order_by ? $order_by : ''));

		if ($query->num_rows() == 0)
		{
			return array();
		}

		// populate results array
		$results = array();

		foreach ($query->result() as $post)
		{
			$board = $this->radix->get_by_id($post->board_id);
			$post->board = $board;

			$this->process_post($board, $post);

			array_push($results, $post);
		}

		return $results;
	}


	/**
	 * @param object $board
	 * @param int $num
	 * @param int $subnum
	 * @return bool|object
	 */
	function get_post_thread($board, $num, $subnum = 0)
	{
		$query = $this->db->query('
			SELECT num, parent, subnum
			FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE num = ? AND subnum = ? LIMIT 0, 1
		',
			array($num, $subnum)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}


	/**
	 * @param object $board
	 * @param int|string $num
	 * @param int $subnum
	 * @return bool|object
	 */
	function get_post_by_num($board, $num, $subnum = 0)
	{
		if (strpos($num, '_') !== FALSE && $subnum == 0)
		{
			$num_array = explode('_', $num);

			if (count($num_array) != 2)
			{
				return FALSE;
			}

			$num = $num_array[0];
			$subnum = $num_array[1];
		}

		$num = intval($num);
		$subnum = intval($subnum);

		$query = $this->db->query('
			SELECT num, parent, subnum
			FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE num = ? AND subnum = ? LIMIT 0, 1
		',
			array($num, $subnum)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		// process results
		$post = $query->row();
		$this->process_post($board, $post, TRUE);

		return $post;
	}


	/**
	 * @param object $board
	 * @param int $doc_id
	 * @return bool|object
	 */
	function get_post_by_doc_id($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT * ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1
		',
			array($doc_id)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}


	/**
	 * @param object $board
	 * @param int $doc_id
	 * @return bool|object
	 */
	function get_by_doc_id($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			' . $this->sql_report_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1;
		',
			array($doc_id)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}


	/**
	 * @param object $board
	 * @param string $media_filename
	 * @return array
	 */
	function get_full_media($board, $media_filename)
	{
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE orig_filename = ?
			ORDER BY num DESC LIMIT 0, 1
		',
			array($media_filename)
		);

		if ($query->num_rows() == 0)
		{
			return array('error_type' => 'no_record', 'error_code' => 404);
		}

		$result = $query->row();
		$media_link = $this->get_media_link($board, $result);

		if ($media_link === FALSE)
		{
			$this->process_post($board, $result, TRUE);
			return array('error_type' => 'not_on_server', 'error_code' => 404, 'result' => $result);
		}

		return array('media_link' => $media_link);
	}


	/**
	 * @param object $board
	 * @param string $hash
	 * @param int $page
	 * @param array $options
	 * @return array
	 */
	function get_same_media($board, $hash, $page, $options = array())
	{
		// default variables
		$per_page = 25;
		$process = TRUE;
		$clean = TRUE;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// check for any same media
		$media = $this->db->query('
			SELECT media_id, total
			FROM ' . $this->radix->get_table($board, '_images') . '
			WHERE media_hash = ? LIMIT 0, 1
		',
			array($this->get_media_hash($hash))
		);

		// if no matches found, stop here...
		if ($media->num_rows() == 0)
		{
			return array('posts' => array(), 'total_found' => 0);
		}

		$media = $media->row();

		// query for same media
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			' . $this->sql_report_join($board) . '
			WHERE
				' . $this->radix->get_table($board) . '.`media_id` = ?
			ORDER BY num DESC LIMIT ?, ?
		',
			array(
				$media->media_id,
				intval(($page * $per_page) - $per_page),
				intval($per_page)
			)
		);

		// populate posts_arr array
		$this->populate_posts_arr($query->result());

		// populate results array

		$results = array();

		foreach ($query->result() as $post)
		{
			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}

			$results[0]['posts'][] = $post;
		}

		return array('posts' => $results, 'total_found' => $media->total);
	}


	/**
	 * @param object $board
	 * @param array $data
	 * @param array $options
	 * @return array
	 */
	function comment($board, $data, $options = array())
	{
		// default variables
		$media_allowed = TRUE;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// check: stopforumspam databae for banned ip
		if (check_stopforumspam_ip($this->input->ip_address()))
		{
			if ($data['media'] !== FALSE || $data['media'] != '')
			{
				if (!unlink($data['media']['full_path']))
				{
					log_message('error', 'post.php/comment: failed to remove media file from cache');
				}
			}

			return array('error' => _('Your IP has been identified as a spam proxy. Please try a different IP or remove the proxy to post.'));
		}

		// check: if passed stopforumspam, check if banned internally
		$check = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('posters', TRUE) . '
			WHERE ip = ?
			LIMIT 0, 1
		',
			array(inet_ptod($this->input->ip_address()))
		);

		if ($check->num_rows() > 0)
		{
			$row = $check->row();

			if ($row->banned && !$this->tank_auth->is_allowed())
			{
				if ($data['media'] !== FALSE || $data['media'] != '')
				{
					if (!unlink($data['media']['full_path']))
					{
						log_message('error', 'post.php/comment: failed to remove media file from cache');
					}
				}

				return array('error' => _('You are banned from posting'));
			}
		}

		// check: validate some information
		$check = $this->db->query('
			SELECT *
			FROM ' . $this->radix->get_table($board) . '
			WHERE id = ?
			ORDER BY timestamp DESC
			LIMIT 0,1
		',
			array(inet_ptod($this->input->ip_address()))
		);

		if ($check->num_rows() > 0)
		{
			$row = $check->row();

			if ($data['comment'] != '' && $row->comment == $data['comment'] && !$this->tank_auth->is_allowed())
			{
				return array('error' => _('You\'re posting again the same comment as the last time!'));
			}

			if (time() - $row->timestamp < 10 && time() - $row->timestamp > 0 && !$this->tank_auth->is_allowed())
			{
				return array('error' => 'You must wait at least 10 seconds before posting again.');
			}

		}

		// process comment name+trip
		if ($data['name'] === FALSE || $data['name'] == '')
		{
			$name = 'Anonymous';
			$trip = '';
		}
		else
		{
			// store name in cookie to repopulate forms
			$this->input->set_cookie('foolfuuka_post_name', $data['name'], 60 * 60 * 24 * 30);

			$name_trip = $this->process_name($data['name']);
			$name = $name_trip[0];
			$trip = (isset($name_trip[1])) ? $name_trip[1] : '';
		}

		// process comment email
		if ($data['email'] === FALSE || $data['email'] == '')
		{
			$email = '';
		}
		else
		{
			// store email in cookie to repopulate forms
			if ($data['email'] != 'sage')
			{
				$this->input->set_cookie('foolfuuka_post_email', $data['email'], 60 * 60 * 24 * 30);
			}

			$email = $data['email'];
		}

		// process comment subject
		if ($data['subject'] === FALSE || $data['subject'] == '')
		{
			$subject = '';
		}
		else
		{
			$subject = $data['subject'];
		}

		// process comment password
		if ($data['password'] === FALSE || $data['password'] == '')
		{
			$password = '';
		}
		else
		{
			// store password in cookie to repopulate forms
			$this->input->set_cookie('foolfuuka_post_password', $data['password'], 60 * 60 * 24 * 30);

			$password = $data['password'];
		}

		// process comment
		if ($data['comment'] === FALSE || $data['comment'] == '')
		{
			$comment = '';
		}
		else
		{
			$comment = $data['comment'];
		}

		// process comment ghost+spoiler
		if (isset($data['ghost']) && $data['ghost'] === TRUE)
		{
			$ghost = TRUE;
		}
		else
		{
			$ghost = FALSE;
		}


		if ($data['spoiler'] === FALSE || $data['spoiler'] == '')
		{
			$spoiler = 0;
		}
		else
		{
			$spoiler = $data['spoiler'];
		}


		// process comment media
		if ($data['media'] === FALSE || $data['media'] == '')
		{
			// if no media is present, remove spoiler setting
			if ($spoiler == 1)
			{
				$spoiler = 0;
			}

			// if no media is present and post is op, stop processing
			if ($data['num'] == 0)
			{
				return array('error' => _('An image is required for creating threads.'));
			}

			// check other media errors
			if (isset($data['media_error']))
			{
				// invalid file type
				if (strlen($data['media_error']) == 64)
				{
					return array('error' => _('The filetype you are attempting to upload is not allowed.'));
				}

				// media file is too large
				if (strlen($data['media_error']) == 79)
				{
					return array('error' =>  _('The image you are attempting to upload is larger than the permitted size.'));
				}
			}
		}
		else
		{
			$media = $data['media'];

			// check if media is allowed
			if ($media_allowed === FALSE)
			{
				if (!unlink($media['full_path']))
				{
					log_message('error', 'post.php/comment: failed to remove media file from cache');
				}

				return array('error' => _('Sorry, this thread has reached its maximum amount of image replies.'));
			}

			// check for valid media dimensions
			if ($media['image_width'] == 0 || $media['image_height'] == 0)
			{
				if (!unlink($media['full_path']))
				{
					log_message('error', 'post.php/comment: failed to remove media file from cache');
				}

				return array('error' => _('Your image upload is not a valid image file.'));
			}

			// generate media hash
			$media_hash = base64_encode(pack("H*", md5(file_get_contents($media['full_path']))));


			// check if media is banned
			$check = $this->db->get_where('banned_md5', array('md5' => $media_hash));

			if ($check->num_rows() > 0)
			{
				if (!unlink($media['full_path']))
				{
					log_message('error', 'post.php/comment: failed to remove media file from cache');
				}

				return array('error' => _('Your image upload has been flagged as inappropriate.'));
			}
		}

		// check comment data for spam regex
		if (check_commentdata($data))
		{
			return array('error' => _('Your post contains contents that is marked as spam.'));
		}

		// check entire length of comment
		if (mb_strlen($comment) > 4096)
		{
			return array('error' => _('Your post was too long.'));
		}

		// check total numbers of lines in comment
		if (count(explode("\n", $comment)) > 20)
		{
			return array('error' => _('Your post had too many lines.'));
		}

		// phpass password for extra security, using the same tank_auth setting since it's cool
		$phpass = new PasswordHash(
			$this->config->item('phpass_hash_strength', 'tank_auth'),
			$this->config->item('phpass_hash_portable', 'tank_auth')
		);
		$password = $phpass->HashPassword($password);

		// set missing variables
		$num = $data['num'];
		$lvl = $data['postas'];

		$timestamp = time();

		$check = $this->db->query('
				SELECT doc_id
				FROM ' . $this->radix->get_table($board) . '
				WHERE id = ? AND comment = ? AND timestamp >= ?
			',
			array(
				inet_ptod($this->input->ip_address()), ($comment)?$comment:NULL, ($timestamp - 10)
			)
		);

		if ($check->num_rows() > 0)
		{
			return array('error' => _('This post is already being processed...'));
		}

		// being processing insert...
		if ($ghost === TRUE)
		{
			// ghost reply to existing thread
			$this->db->query('
				INSERT INTO ' . $this->radix->get_table($board) . '
				(
					num, subnum, parent, timestamp, capcode,
					email, name, trip, title, comment, delpass, id
				)
				VALUES
				(
					(
						SELECT MAX(num)
						FROM
						(
							SELECT num
							FROM ' . $this->radix->get_table($board) . '
							WHERE num = ? OR parent = ?
						) AS x
					),
					(
						SELECT MAX(subnum)+1
						FROM
						(
							SELECT subnum
							FROM ' . $this->radix->get_table($board) . '
							WHERE
								num = (
									SELECT MAX(num)
									FROM ' . $this->radix->get_table($board) . '
									WHERE num = ? OR parent = ?
								)
						) AS x
					),
					?, ?, ?,
					?, ?, ?, ?, ?, ?, ?
				)
			',
				array(
					$num, $num, $num, $num, $num, $timestamp, $lvl,
					($email)?$email:NULL, ($name)?$name:NULL, ($trip)?$trip:NULL, ($subject)?$subject:NULL, ($comment)?$comment:NULL,
					$password, inet_ptod($this->input->ip_address())
				)
			);
		}
		else
		{
			// define default values for post
			$default_post_arr = array(
				0, $num, $timestamp, $lvl,
				($email)?$email:NULL, ($name)?$name:NULL, ($trip)?$trip:NULL, ($subject)?$subject:NULL, ($comment)?$comment:NULL,
				$password, $spoiler, inet_ptod($this->input->ip_address())
			);

			// process media
			if (isset($media))
			{
				$media_file = $this->process_media($board, $num, $media, $media_hash);
				if ($media_file === FALSE)
				{
					return array('error' => _('Your image was invalid.'));
				}

				// replace timestamp with timestamp generated by process_media
				$default_post_arr[2] = end($media_file);
				array_pop($media_file);
				$default_post_arr = array_merge($default_post_arr, $media_file);
			}
			else
			{
				// populate with empty media values
				$media_file = array(NULL, 0, 0, NULL, 0, 0, 0, NULL, NULL);
				$default_post_arr = array_merge($default_post_arr, $media_file);
			}

			// insert post into board
			$this->db->query('
				INSERT INTO ' . $this->radix->get_table($board) . '
				(
					num, subnum, parent, timestamp, capcode,
					email, name, trip, title, comment, delpass, spoiler, id,
					preview, preview_w, preview_h, media, media_w, media_h, media_size, media_hash, orig_filename
				)
				VALUES
				(
					(
						SELECT COALESCE(MAX(num), 0)+1
						FROM
						(
							SELECT num
							FROM ' . $this->radix->get_table($board) . '
						) AS x
					),
					?, ?, ?, ?,
					?, ?, ?, ?, ?, ?, ?, ?,
					?, ?, ?, ?, ?, ?, ?, ?, ?
				)
			',
				$default_post_arr
			);
		}

		// retreive num, subnum, parent for redirection
		$post = $this->db->query('
			SELECT num, subnum, parent
			FROM ' . $this->radix->get_table($board) . '
			WHERE doc_id = ? LIMIT 0, 1
		',
			array($this->db->insert_id())
		);

		return array('success' => TRUE, 'posted' => $post->row());
	}


	/**
	 * @param object $board
	 * @param array $post
	 * @return array|bool
	 */
	function delete($board, $post)
	{
		// $post => [doc_id, password, type]
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1
		',
			array($post['doc_id'])
		);

		if ($query->num_rows() == 0)
		{
			log_message('debug', 'post.php/delete: invalid doc_id for post or thread');
			return array('error' => _('There\'s no such a post to be deleted.'));
		}

		// store query results
		$row = $query->row();

		$phpass = new PasswordHash(
			$this->config->item('phpass_hash_strength', 'tank_auth'),
			$this->config->item('phpass_hash_portable', 'tank_auth')
		);

		// validate password
		if ($phpass->CheckPassword($post['password'], $row->delpass) !== TRUE && !$this->tank_auth->is_allowed())
		{
			log_message('debug', 'post.php/delete: invalid password');
			return array('error' => _('The password you inserted did not match the post\'s deletion password.'));
		}

		// delete media file for post
		if (!$this->delete_media($board, $row))
		{
			log_message('error', 'post.php/delete: unable to delete media from post');
			return array('error' => _('Unable to delete thumbnail for post.'));
		}

		// remove the thread
		$this->db->query('
				DELETE
				FROM ' . $this->radix->get_table($board) . '
				WHERE doc_id = ?
			',
			array($row->doc_id)
		);

		// an error was encountered
		if ($this->db->affected_rows() != 1)
		{
			log_message('error', 'post.php/delete: unable to delete thread op');
			return array('error', _('Unable to delete post.'));
		}

		// purge existing reports for post
		$this->db->delete('reports', array('board_id' => $board->id, 'doc_id' => $row->doc_id));

		// purge thread replies if parent post
		if ($row->parent == 0) // delete: thread
		{
			$thread = $this->db->query('
				SELECT * FROM ' . $this->radix->get_table($board) . '
				' . $this->sql_media_join($board) . '
				WHERE parent = ?
			',
				array($row->num)
			);

			// thread replies found
			if ($thread->num_rows() > 0)
			{
				// remove all media files
				foreach ($thread->result() as $p)
				{
					if (!$this->delete_media($board, $p))
					{
						log_message('error', 'post.php/delete: unable to delete media from thread op');
						return array('error' => _('Unable to delete thumbnail for thread replies.'));
					}

					// purge associated reports
					$this->db->delete('reports', array('board_id' => $board->id, 'doc_id' => $p->doc_id));
				}

				// remove all replies
				$this->db->query('
					DELETE FROM ' . $this->radix->get_table($board) . '
					WHERE parent = ?
				',
					array($row->num)
				);
			}
		}

		return TRUE;
	}


	/**
	 * @param object $board
	 * @param object $post
	 * @param bool $media
	 * @param bool $thumb
	 * @return bool
	 */
	function delete_media($board, $post, $media = TRUE, $thumb = TRUE)
	{
		if (!$post->media_filename && !$post->media_hash)
		{
			// if there's no media, it's all OK
			return TRUE;
		}

		// delete media file only if there is only one image OR user is admin
		if ($this->tank_auth->is_allowed() || $post->total == 1)
		{
			if ($media === TRUE)
			{
				$media_file = $this->get_media_dir($board, $post);
				if (file_exists($media_file))
				{
					if (!unlink($media_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $media_file);
						return FALSE;
					}
				}
			}

			if ($thumb === TRUE)
			{
				$thumb_file = $this->get_media_dir($board, $post, TRUE);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $thumb_file);
						return FALSE;
					}
				}
			}
		}

		return TRUE;
	}


	/**
	 * @param string $hash
	 * @param bool $delete
	 * @return bool
	 */
	function ban_media($media_hash, $delete = FALSE)
	{
		// insert into global banned media hash
		$this->db->query('
			INSERT IGNORE INTO ' . $this->db->protect_identifiers('banned_md5', TRUE) . '
			(
				md5
			)
			VALUES
			(
				?
			)
		',
			array($media_hash)
		);

		// update all local _images table
		foreach ($this->radix->get_all() as $board)
		{
			$this->db->query('
				INSERT INTO ' . $this->radix->get_table($board, '_images') . '
				(
					media_hash, media_filename, preview_op, preview_reply, total, banned
				)
				VALUES
				(
					?, ?, ?, ?, ?, ?
				)
				ON DUPLICATE KEY UPDATE banned = 1
			',
				array($media_hash, NULL, NULL, NULL, 0, 1)
			);
		}

		// delete media files if TRUE
		if ($delete === TRUE)
		{
			$posts = array();

			foreach ($this->radix->get_all() as $board)
			{
				$posts[] = '
					(
						SELECT *, CONCAT(' . $this->db->escape($board->id) . ') AS board_id
						FROM ' . $this->radix->get_table($board) . '
						WHERE media_hash = ' . $this->db->escape($media_hash) . '
					)
				';
			}

			$query = $this->db->query(implode('UNION', $posts));
			if ($query->num_rows() == 0)
			{
				log_message('error', 'post.php/ban_media: unable to locate posts containing media_hash');
				return FALSE;
			}

			foreach ($query->result() as $post)
			{
				$this->delete_media($this->radix->get_by_id($post->board_id), $post);
			}
		}

		return TRUE;
	}


	/**
	 * @param object $board
	 * @param int $doc_id
	 * @return bool
	 */
	function mark_spam($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->radix->get_table($board) . '
			WHERE doc_id = ?
			LIMIT 0, 1
		',
			array($doc_id)
		);

		if ($query->num_rows() == 0)
		{
			log_message('error', 'post.php/mark_spam: invalid doc_id argument');
			return FALSE;
		}

		// store post information
		$post = $query->row();

		// mark post as spam


		return TRUE;
	}


}
