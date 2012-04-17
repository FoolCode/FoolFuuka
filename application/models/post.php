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


	function sql_report_join($board, $join_on = NULL)
	{
		if (!$this->tank_auth->is_allowed())
			return '';

		return '
			LEFT JOIN
			(
				SELECT
					id AS report_id, doc_id AS report_doc_id, reason AS report_reason, status AS report_status,
					created AS report_created
				FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
				WHERE `board_id` = ' . $board->id . '
			) AS q
			ON
			' . ($join_on?$join_on:$this->radix->get_table($board)) . '.`doc_id`
			=
			' . $this->db->protect_identifiers('q') . '.`report_doc_id`
		';
	}


	function sql_media_join($board, $join_on = NULL)
	{
		return '
			LEFT JOIN
			(
				SELECT
					id AS media_id, media_filename AS media_image, preview_op AS media_thumb_op,
					preview_reply AS media_thumb_re, banned AS media_banned
				FROM ' . $this->radix->get_table($board, '_images') . '
			) as q
			ON
			' . ($join_on?$join_on:$this->radix->get_table($board)) . '.`media_id`
			=
			' . $this->db->protect_identifiers('q') . '.`media_id`
		';
	}


	function populate_posts_arr($object)
	{
		if (is_array($object))
		{
			foreach ($object as $post)
			{
				$this->populate_posts_arr($post);
			}
		}

		if (is_object($object))
		{
			$post = $object;

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


	function get_media_dir($board, $row, $thumbnail = FALSE)
	{
		if (!$row->preview)
			return FALSE;

		if ($board->archive)
		{
			if ($row->parent > 0)
				$number = $row->parent;
			else
				$number = $row->num;

			preg_match('/(\d+?)(\d{2})\d{0,3}$/', $number, $matches);

			if (!isset($matches[1]))
				$matches[1] = '';

			if (!isset($matches[2]))
				$matches[2] = '';

			$number = str_pad($matches[1], 4, "0", STR_PAD_LEFT) . str_pad($matches[2], 2, "0", STR_PAD_LEFT);
		}
		else
		{
			$number = ($thumbnail) ? $row->preview : $row->media_filename;

			if (!isset($row->media_filename))
				$row->media_filename = $row->preview;

			if (strpos($number, 's.') === FALSE)
				$thumbnail = FALSE;
		}

		return ((get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory') : FOOLFUUKA_BOARDS_DIRECTORY))
			. '/' . $board->shortname . '/' . ($thumbnail?'thumb':'img') . '/' . substr($number, 0, 4) . '/'
			. substr($number, 4, 2) . '/' . ($thumbnail?$row->preview:$row->media_filename);
	}


	function get_media_link($board, $row, $thumbnail = FALSE)
	{
		if (!$row->preview)
			return FALSE;

		if (!$this->tank_auth->is_allowed())
		{
			if (!$board->hide_thumbnails)
			{
				if ($thumbnail)
					return site_url() . 'content/themes/default/images/null-image.png';

				return FALSE;
			}

			if ($board->delay_thumbnails)
			{
				if (isset($row->timestamp) && ($row->timestamp + 86400) > time())
				{
					if ($thumbnail)
						return site_url() . 'content/themes/default/images/null-image.png';

					return FALSE;
				}
			}

			$media_check = $this->db->get_where('banned_md5', array('md5' => $row->media_hash));
			if ($media_check->num_rows() > 0)
			{
				if ($thumbnail)
				{
					$row->preview_h = 150;
					$row->preview_w = 150;
					return site_url() . 'content/themes/default/images/banned-image.png';
				}

				return FALSE;
			}
		}

		if ($board->archive)
		{
			if ($row->parent > 0)
				$number = $row->parent;
			else
				$number = $row->num;

			preg_match('/(\d+?)(\d{2})\d{0,3}$/', $number, $matches);

			if (!isset($matches[1]))
				$matches[1] = '';

			if (!isset($matches[2]))
				$matches[2] = '';

			$number = str_pad($matches[1], 4, "0", STR_PAD_LEFT) . str_pad($matches[2], 2, "0", STR_PAD_LEFT);
		}
		else
		{
			$number = ($thumbnail) ? $row->preview : $row->media_filename;

			if (!isset($row->media_filename))
				$row->media_filename = $row->preview;

			if (strpos($number, 's.') === FALSE)
				$thumbnail = FALSE;
		}

		if (file_exists($this->get_media_dir($board, $row, $thumbnail)) !== FALSE)
		{
			if (strlen(get_setting('fs_balancer_clients')) > 10)
			{
				preg_match('/([\d]+)/', $row->preview, $matches);

				if (isset($matches[1]))
				{
					$balancer_servers = '';
				}
			}

			return (get_setting('fs_fuuka_boards_url') ? get_setting('fs_fuuka_boards_url') : site_url() . FOOLFUUKA_BOARDS_DIRECTORY)
				. '/' . $board->shortname . '/' . ($thumbnail?'thumb':'img') . '/' . substr($number, 0, 4) . '/'
				. substr($number, 4, 2) . '/' . ($thumbnail?$row->preview:$row->media_filename);
		}

		if ($thumbnail)
		{
			$row->preview_h = 150;
			$row->preview_w = 150;
			return site_url() . 'content/themes/default/images/image_missing.jpg';
		}

		return FALSE;
	}


	function get_remote_media_link($board, $row)
	{
		if (!$row->preview)
			return FALSE;

		if ($board->archive)
		{
			// ignore webkit+opera user agents
			if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(opera|webkit)/i', $_SERVER['HTTP_USER_AGENT']))
				return $board->images_url . $row->media_filename;

			return site_url(array($board->shortname, 'redirect')) . $row->media_filename;
		}
		else
		{
			if (file_exists($this->get_media_dir($board, $row)) !== FALSE)
				return $this->get_media_link($board, $row);
			else
				return FALSE;
		}
	}


	function get_media_hash($input, $urlsafe = FALSE)
	{
		if (is_object($input) || is_array($input))
		{
			if (!$input->preview)
				return FALSE;

			$media_hash = $input->media_hash;
		}
		else
		{
			if (strlen(trim($input)) == 0)
				return FALSE;

			$media_hash = $input;
		}

		if ($urlsafe)
			return substr(urlsafe_b64encode(urlsafe_b64decode($media_hash)), 0, -2);
		else
			return base64_encode(urlsafe_b64decode($media_hash));
	}


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

				if ($normal_trip != '')
					$normal_trip = '!' . $normal_trip;
			}

			if (count($matches_trip) > 2)
			{
				$secure_trip = '!!' . $this->process_secure_tripcode($matches_trip[2]);
			}
		}

		return array($name, $normal_trip . $secure_trip);
	}


	function process_tripcode($plain)
	{
		if (trim($plain) == '')
			return '';

		$pass = mb_convert_encoding($plain, 'SJIS', 'UTF-8');
		$pass = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&#39;', '&lt;', '&gt;'), $pass);

		$salt = substr($pass . 'H.', 1, 2);
		$salt = preg_replace('/[^.-z]/', '.', $salt);

		return substr(crypt($pass, $salt), -10);
	}


	function process_secure_tripcode($plain)
	{
		return substr(base64_encode(sha1($plain . base64_decode(FOOLFUUKA_SECURE_TRIPCODE_SALT), TRUE)), 0, 11);
	}


	function process_post($board, $post, $clean = TRUE, $build = FALSE)
	{
		$this->current_p = $post;
		$this->load->helper('text');

		$post->safe_media_hash = $this->get_media_hash($post, TRUE);
		$post->remote_media_link = $this->get_remote_media_link($board, $post);
		$post->media_link = $this->get_media_link($board, $post);
		$post->thumb_link = $this->get_media_link($board, $post, TRUE);
		$post->comment_processed = @iconv('UTF-8', 'UTF-8//IGNORE', $this->process_comment($board, $post));
		$post->comment = @iconv('UTF-8', 'UTF-8//IGNORE', $post->comment);

		$elements = array('title', 'name', 'email', 'trip', 'media', 'preview', 'media_filename', 'media_hash');
		foreach($elements as $element)
		{
			$element_processed = $element . '_processed';

			$post->$element_processed = @iconv('UTF-8', 'UTF-8//IGNORE', fuuka_htmlescape($post->$element));
			$post->$element = @iconv('UTF-8', 'UTF-8//IGNORE', $post->$element);
		}

		if ($clean === TRUE)
		{
			if (!$this->tank_auth->is_allowed())
				unset($post->id);

			unset($post->delpass);
		}

		if ($build === TRUE)
		{
			$post->format = $this->build_board_comment($board, $post);
		}
	}


	function process_media($board, $post, $media, $media_hash)
	{
		if (!$board->archive)
		{
			$number = time();
		}
		else
		{
			return FALSE;
		}

		// generate unique filename with timestamp
		$media_unixtime = time() . rand(1000, 9999);
		$media_filename = $media_unixtime . strtolower($media['file_ext']);
		$thumb_filename = $media_unixtime . 's' . strtolower($media['file_ext']);

		// image directory structure
		$directory = array(
			'media' => (get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory')
				: FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/img/' . substr($number, 0, 4) .
				'/' . substr($number, 4, 2) . '/',

			'thumb' => (get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory')
				: FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/thumb/' . substr($number, 0, 4) .
				'/' . substr($number, 4, 2) . '/',
		);

		// if necessary, generate the full file path
		generate_file_path($directory['media']);
		generate_file_path($directory['thumb']);

		// relocate the media file to proper location
		if (!copy($media['full_path'], $directory['media'] . $media_filename))
		{
			log_message('error', 'post.php/process_media: failed to move media file');
			return FALSE;
		}

		// remove the media file
		if (!unlink($media['full_path']))
		{
			log_message('error', 'post.php/process_media: failed to remove media file from cache directory');
		}

		// determine the correct thumbnail dimensions
		if ($post == 0)
			$default_dimensions = 250;
		else
			$default_dimensions = 125;

		// generate thumbnail
		if ($media['image_width'] > $default_dimensions || $media['image_height'] > $default_dimensions)
		{
			$media_config = array(
				'image_library' => (find_imagick()) ? 'ImageMagick' : 'GD2',
				'library_path'  => (find_imagick()) ? get_setting('fs_serv_imagick_path', '/usr/bin') : '',
				'source_image'  => $directory['media'] . $media_filename,
				'new_image'     => $directory['thumb'] . $thumb_filename,
				'width'         => ($media['image_width'] > $default_dimensions) ? $default_dimensions : $media['image_width'],
				'height'        => ($media['image_height'] > $default_dimensions) ? $default_dimensions : $media['image_height'],
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
			$thumb_dimensions = @getimagesize($directory['thumb'] . $thumb_filename);
		}
		else
		{
			$thumb_filename = $media_filename;
			$thumb_dimensions = array($media['image_width'], $media['image_height']);
		}

		return array(
			$thumb_filename, $thumb_dimensions[0], $thumb_dimensions[1],
			$media['file_name'], $media['image_width'], $media['image_height'],
			floor($media['file_size'] * 1024), $media_hash, $media_filename, $number
		);
	}


	function process_comment($board, $row)
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

		$comment = $row->comment;

		// this stores an array of moot's formatting that must be removed
		$special = array(
			'<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">',
			'<span style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">'
		);

		// remove moot's special formatting
		if ($row->capcode == 'A' && mb_strpos($comment, $special[0]) == 0)
		{
			$comment = str_replace($special[0], '', $comment);

			if (mb_substr($comment, -6, 6) == '</div>')
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 6);
		}

		if ($row->capcode == 'A' && mb_strpos($comment, $special[1]) == 0)
		{
			$comment = str_replace($special[1], '', $comment);

			if (mb_substr($comment, -10, 10) == '[/spoiler]')
				$comment = mb_substr($comment, 0, mb_strlen($comment) - 10);
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
		$comment = parse_bbcode($comment, ($board->archive && $row->subnum) ? TRUE : FALSE);

		// additional formatting
		if ($board->archive && $row->subnum)
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
				return $html['prefix'] . '<a href="http://boards.4chan.org/' . $shortname . '/res/' . $num . '">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];

			return $html['prefix'] . '<a href="http://boards.4chan.org/' . $shortname . '/">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];
		}

		if ($num)
			return $html['prefix'] . '<a href="' . site_url(array($board->shortname, 'post', $num)) . '">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];

		return $html['prefix'] . '<a href="' . site_url($board->shortname) . '">&gt;&gt;&gt;' . $url . '</a>' . $html['suffix'];

		// return un-altered
		return $matches[0];
	}


	function build_board_comment($board, $p)
	{
		// load the functions from the current theme, else load the default one
		if (file_exists('content/themes/' . $this->fu_theme . '/theme_functions.php'))
			require_once('content/themes/' . $this->fu_theme . '/theme_functions.php');
		else
			require_once('content/themes/' . $this->config->item('theme_extends') . '/theme_functions.php');

		//require_once
		ob_start();

		if (file_exists('content/themes/' . $this->fu_theme . '/views/board_comment.php'))
			include('content/themes/' . $this->fu_theme . '/views/board_comment.php');
		else
			include('content/themes/' . $this->config->item('theme_extends') . '/views/board_comment.php');

		$string = ob_get_contents();
		ob_end_clean();

		return $string;
	}


	function check_thread($board, $num)
	{
		if ($num == 0)
			return array('invalid_thread' => TRUE);

		// grab the entire thread
		$query = $this->db->query('
			(
				SELECT *
				FROM ' . $this->radix->get_table($board) . '
				WHERE num = ?
			)
			UNION
			(
				SELECT *
				FROM ' . $this->radix->get_table($board) . '
				WHERE parent = ?
			)
		',
			array($num, $num)
		);

		// thread was not found
		if ($query->num_rows() == 0)
			return array('invalid_thread' => TRUE);

		// define variables
		$thread_op_present = FALSE;
		$thread_last_bump = 0;
		$counter = array('posts' => 0, 'images' => 0);

		foreach ($query->result() as $post)
		{
			// we need to find if there's the OP in the list
			// let's be strict, we want the $num to be the OP
			if ($post->parent == 0 && $post->subnum == 0 && $post->num === $num)
				$thread_op_present = TRUE;

			if($post->subnum == 0 && $thread_last_bump < $post->timestamp)
				$thread_last_bump = $post->timestamp;

			if ($post->media_filename)
				$counter['images']++;

			$counter['posts']++;
		}

		// free up result
		$query->free_result();

		// we didn't point to the thread OP, this is not a thread
		if (!$thread_op_present)
			return array('invalid_thread' => TRUE);

		// time check
		if(time() - $thread_last_bump > 432000)
			return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE);

		if ($counter['posts'] > 400)
		{
			if ($counter['images'] > 200)
				return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE);
			else
				return array('thread_dead' => TRUE);
		}
		else if ($counter['images'] > 200)
		{
			return array('disable_image_upload' => TRUE);
		}

		return array('valid_thread' => TRUE);
	}


	function get_search($board, $params, $options = array())
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
		if ($params['page'])
		{
			if (!is_numeric($params['page']))
			{
				log_message('error', 'post.php/get_search: invalid page argument');
				show_404();
			}

			$params['page'] = intval($params['page']);
		}
		else
		{
			$params['page'] = 1;
		}

		// if global or board => use sphinx, else mysql for board only
		// global search requires sphinx
		if ($board->sphinx || ($board === FALSE && get_setting('fs_sphinx_global')))
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
						continue;

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
			if ($params['text'])
			{
				if (mb_strlen($params['text']) < 1)
				{
					return array();
				}

				$this->db->sphinx_match('comment', $params['text'], 'half', TRUE);
			}
			if ($params['subject'])
				$this->db->sphinx_match('title', $params['subject'], 'full', TRUE);
			if ($params['username'])
				$this->db->sphinx_match('name', $params['username'], 'full', TRUE);
			if ($params['tripcode'])
				$this->db->sphinx_match('trip', $params['tripcode'], 'full', TRUE, TRUE);
			if ($params['email'])
				$this->db->sphinx_match('email', $params['email'], 'full', TRUE);
			if ($params['capcode'] == 'admin')
				$this->db->where('cap', 3);
			if ($params['capcode'] == 'mod')
				$this->db->where('cap', 2);
			if ($params['capcode'] == 'user')
				$this->db->where('cap', 1);
			if ($params['deleted'] == 'deleted')
				$this->db->where('is_deleted', 1);
			if ($params['deleted'] == 'not-deleted')
				$this->db->where('is_deleted', 0);
			if ($params['ghost'] == 'only')
				$this->db->where('is_internal', 1);
			if ($params['ghost'] == 'none')
				$this->db->where('is_internal', 0);
			if ($params['type'] == 'op')
				$this->db->where('is_op', 1);
			if ($params['type'] == 'posts')
				$this->db->where('is_op', 0);
			if ($params['filter'] == 'image')
				$this->db->where('has_image', 0);
			if ($params['filter'] == 'text')
				$this->db->where('has_image', 1);
			if ($params['start'])
				$this->db->where('timestamp >=', intval(strtotime($params['start'])));
			if ($params['end'])
				$this->db->where('timestamp <=', intval(strtotime($params['end'])));
			if ($params['order'] == 'asc')
				$this->db->where('timestamp', 'ASC');
			else
				$this->db->where('timestamp', 'DESC');

			// set sphinx options
			$this->db->limit(25, ($params['page'] * 25) - 25)
				->sphinx_option('max_matches', 5000)
				->sphinx_option('reverse_scan', ($params['order'] == 'asc') ? 0 : 1);

			// send sphinxql to searchd
			$search = $this->sphinxql->query($this->db->statement());

			if (empty($search['matches']))
				return array('posts' => array(), 'total_found' => 0);

			// populate array to query for full records
			$sql = array();

			foreach ($search['matches'] as $row => $result)
			{
				$sql[] = '
					(
						SELECT *, ' . $result['board'] . ' AS board
						FROM ' . $this->radix->get_table($this->radix->get_by_id($result['board'])) . '
						WHERE num = ' . $result['num'] . ' AND subnum = ' . $result['subnum'] . '
					)
				';
			}

			if ($params['order'] == 'asc')
				$sql = implode('UNION', $sql) . ' ORDER BY timestamp ASC';
			else
				$sql = implode('UNION', $sql) . ' ORDER BY timestamp DESC';

			// query mysql for full records
			$query = $this->db->query($sql);
			$total = $search['total_found'];
		}
		else /* use mysql as fallback for non-sphinx indexed boards */
		{
			// begin cache of entire sql statement
			$this->db->start_cache();

			// set db->from with board
			$this->db->from($this->radix->get_table($board), FALSE);

			// begin filtering search params
			if ($params['text'])
			{
				if (mb_strlen($params['text']) < 1)
				{
					return array();
				}

				$this->db->like('comment', rawurldecode($params['text']));
			}
			if ($params['subject'])
				$this->db->like('title', rawurldecode($params['subject']));
			if ($params['username'])
				$this->db->like('name', rawurldecode($params['username']))
					->use_index('name_index');
			if ($params['tripcode'])
				$this->db->like('trip', rawurldecode($params['tripcode']))
					->use_index('trip_index');
			if ($params['email'])
				$this->db->like('email', rawurldecode($params['email']))
					->use_index('email_index');
			if ($params['capcode'] == 'admin')
				$this->db->where('capcode', 'A');
			if ($params['capcode'] == 'mod')
				$this->db->where('capcode', 'M');
			if ($params['capcode'] == 'user')
				$this->db->where('capcode !=', 'A')->where('capcode !=', 'M');
			if ($params['deleted'] == 'deleted')
				$this->db->where('deleted', 1);
			if ($params['deleted'] == 'not-deleted')
				$this->db->where('deleted', 0);
			if ($params['ghost'] == 'only')
				$this->db->where('subnum <>', 0)
					->use_index('subnum_index');
			if ($params['ghost'] == 'none')
				$this->db->where('subnum', 0)
					->use_index('subnum_index');
			if ($params['type'] == 'op')
				$this->db->where('parent', 0)
					->use_index('parent_index');
			if ($params['type'] == 'posts')
				$this->db->where('parent <>', 0)
					->use_index('parent_index');
			if ($params['filter'] == 'image')
				$this->db->where('media_hash IS NOT NULL')
					->use_index('media_hash_index');
			if ($params['filter'] == 'text')
				$this->db->where('media_hash IS NULL')
					->use_index('media_hash_index');
			if ($params['start'])
				$this->db->where('timestamp >=', intval(strtotime($params['start'])))
					->use_index('timestamp_index');
			if ($params['end'])
				$this->db->where('timestamp <=', intval(strtotime($params['end'])))
					->use_index('timestamp_index');

			// stop cache of entire sql statement, the main query is stored
			$this->db->stop_cache();

			// fetch initial total first...
			$this->db->limit(5000);

			// check if we have any results
			$check = $this->db->query($this->db->statement());
			if ($check->num_rows() == 0)
				return array('posts' => array(), 'total_found' => 0);

			// modify cached query for additional params
			if ($params['order'] == 'asc')
				$this->db->order_by('timestamp', 'ASC')
					->use_index('timestamp_index');
			else
				$this->db->order_by('timestamp', 'DESC')
					->use_index('timestamp_index');

			// set query options
			$this->db->limit(25, ($params['page'] * 25) - 25);

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
				$this->process_post($board, $post, $clean);

			$results[0]['posts'][] = $post;
		}

		return array('posts' => $results, 'total_found' => $total);
	}


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
						ORDER BY time_bump DESC
						LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
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
						ORDER BY parent DESC
						LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
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
						ORDER BY time_ghost_bump DESC
						LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
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
		if ($pages)
			$pages = NULL;

		// free up memory
		$query_pages->free_result();

		// populate arrays with posts
		$threads = array();
		$results = array();
		$sql_arr = array();

		foreach ($query->result() as $row)
		{
			$threads[$row->unq_parent] = array('replies' => $row->nreplies, 'images' => $row->nimages);

			$sql_arr[] = '
				(
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_report_join($board) . '
					WHERE parent = ' . $row->unq_parent . '
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
				$this->process_post($board, $post, $clean);

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
					$results[$post->parent]['images_omitted']--;

				$results[$post->parent]['posts'][] = $post;
			}
			else
			{
				$results[$post->num]['op'] = $post;
			}
		}

		return array('result' => $results, 'pages' => $pages);
	}


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
							SELECT *
							FROM ' . $this->radix->get_table($board) . '
							WHERE num = ?
							LIMIT 0, 1
						)
						UNION
						(
							SELECT *
							FROM ' . $this->radix->get_table($board) . '
							WHERE parent = ?
							ORDER BY num DESC, subnum DESC
							LIMIT ?
						)
					) AS x
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
						SELECT *
						FROM ' . $this->radix->get_table($board) . '
						' . $this->sql_report_join($board) . '
						WHERE num = ?
					)
					UNION
					(
						SELECT *
						FROM ' . $this->radix->get_table($board) . '
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
			return FALSE;

		// set global variables for special usage
		if ($realtime === TRUE)
			$this->realtime = TRUE;

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
					$this->process_post($board, $post, $clean, $realtime);
				else
					$this->process_post($board, $post, TRUE, TRUE);
			}

			if ($post->parent > 0)
				$result[$post->parent]['posts'][$post->num . (($post->subnum == 0) ? '' : '_' . $post->subnum)] = $post;
			else
				$result[$post->num]['op'] = $post;
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
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_report_join($board) . '
					WHERE media_filename IS NOT NULL
					ORDER BY timestamp DESC
					LIMIT ?, ?
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
						ORDER BY time_op DESC
						LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
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


	function get_reports($page = 1)
	{
		$this->load->model('report');

		// populate multi_posts array to fetch
		$multi_posts = array();

		foreach ($this->report->get_posts($page) as $post)
		{
			$multi_posts[] = array(
				'board_id' => $post->board_id,
				'doc_id'   => array($post->doc_id)
			);
		}

		return $this->get_multi_posts($multi_posts);
	}


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
						' . $this->sql_report_join($board, 'g') . '
						WHERE g.`doc_id` = ' . implode(' OR g.`doc_id` = ', $posts['doc_id']) . '
					)
				';
			}
		}

		// order results properly with string argument
		if ($order_by !== NULL)
			$query = $this->db->query(implode('UNION', $sql) . ' ORDER BY ' . $order_by);
		else
			$query = $this->db->query(implode('UNION', $sql));

		if ($query->num_rows() == 0)
			return FALSE;

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


	function get_post_thread($board, $num, $subnum = 0)
	{
		$query = $this->db->query('
			SELECT num, parent, subnum
			FROM ' . $this->radix->get_table($board) . '
			WHERE num = ? AND subnum = ?
			LIMIT 0, 1
		',
			array($num, $subnum)
		);

		if ($query->num_rows() == 0)
			return FALSE;

		return $query->row();
	}


	function get_post_by_num($board, $num, $subnum = 0)
	{
		if (strpos($num, '_') !== FALSE && $subnum == 0)
		{
			$num_array = explode('_', $num);

			if (count($num_array) != 2)
				return FALSE;

			$num = intval($num_array[0]);
			$subnum = intval($num_array[1]);
		}
		else
		{
			$num = intval($num);
			$subnum = intval($subnum);
		}

		$query = $this->db->query('
			SELECT num, parent, subnum
			FROM ' . $this->radix->get_table($board) . '
			WHERE num = ? AND subnum = ?
			LIMIT 0, 1
		',
			array($num, $subnum)
		);

		if ($query->num_rows() == 0)
			return FALSE;

		// process results
		$post = $query->row();
		$this->process_post($board, $post, TRUE);

		return $post;
	}


	function get_post_by_doc_id($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT *
			' . $this->radix->get_table($board) . '
			WHERE doc_id = ?
			LIMIT 0, 1
		',
			array($doc_id)
		);

		if ($query->num_rows() == 0)
			return FALSE;

		return $query->row();
	}


	function get_by_doc_id($board, $doc_id)
	{
		$query = $this->db->query('
				SELECT * FROM ' . $this->radix->get_table($board) . '
				' . $this->sql_report_join($board) . '
				WHERE doc_id = ?
				LIMIT 0, 1;
			',
			array($doc_id));

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		foreach ($query->result() as $post)
		{
			return $post;
		}

		return FALSE;
	}


	function get_full_media($board, $media_filename)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->radix->get_table($board) . '
			WHERE media_filename = ?
			ORDER BY num DESC
			LIMIT 0, 1
		',
			array($media_filename)
		);

		if ($query->num_rows() == 0)
			return array('error_type' => 'no_record', 'error_code' => 404);

		$result = $query->row();
		$media_link = $this->get_media_link($board, $result);

		if ($media_link === FALSE)
		{
			$this->process_post($board, $result, TRUE);
			return array('error_type' => 'not_on_server', 'error_code' => 404, 'result' => $result);
		}

		return array('media_link' => $media_link);
	}


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
		$total = $this->db->query('
			SELECT total
			FROM ' . $this->radix->get_table($board, '_images') . '
			WHERE media_hash = ?
			LIMIT 0, 1
		',
			array($this->get_media_hash($hash))
		);

		// if no matches found, stop here...
		if ($total->num_rows() == 0)
			return array('post' => array(), 'total_found' => 0);

		// query for same media
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_report_join($board) . '
			WHERE media_hash = ?
			ORDER BY num DESC
			LIMIT ?, ?
		',
			array(
				$this->get_media_hash($hash),
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
				$this->process_post($board, $post, $clean);

			$results[0]['posts'][] = $post;
		}

		return array('posts' => $results, 'total_found' => $total->row()->total);
	}


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
					log_message('error', 'post.php/comment: failed to remove media file from cache');
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
						log_message('error', 'post.php/comment: failed to remove media file from cache');
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
				return array('error' => _('You\'re posting again the same comment as the last time!'));

			if (time() - $row->timestamp < 10 && time() - $row->timestamp > 0 && !$this->tank_auth->is_allowed())
				return array('error' => 'You must wait at least 10 seconds before posting again.');
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
				$this->input->set_cookie('foolfuuka_post_email', $data['email'], 60 * 60 * 24 * 30);

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
			$ghost = TRUE;
		else
			$ghost = FALSE;

		if ($data['spoiler'] === FALSE || $data['spoiler'] == '')
			$spoiler = 0;
		else
			$spoiler = $data['spoiler'];

		// process comment media
		if ($data['media'] === FALSE || $data['media'] == '')
		{
			// if no media is present, remove spoiler setting
			if ($spoiler == 1)
				$spoiler = 0;

			// if no media is present and post is op, stop processing
			if ($data['num'] == 0)
				return array('error' => _('An image is required for creating threads.'));

			// check other media errors
			if (isset($data['media_error']))
			{
				// invalid file type
				if (strlen($data['media_error']) == 64)
					return array('error' => _('The filetype you are attempting to upload is not allowed.'));

				// media file is too large
				if (strlen($data['media_error']) == 79)
					return array('error' =>  _('The image you are attempting to upload is larger than the permitted size.'));
			}
		}
		else
		{
			$media = $data['media'];

			// check if media is allowed
			if ($media_allowed === FALSE)
			{
				if (!unlink($media['full_path']))
					log_message('error', 'post.php/comment: failed to remove media file from cache');

				return array('error' => _('Sorry, this thread has reached its maximum amount of image replies.'));
			}

			// check for valid media dimensions
			if ($media['image_width'] == 0 || $media['image_height'] == 0)
			{
				if (!unlink($media['full_path']))
					log_message('error', 'post.php/comment: failed to remove media file from cache');

				return array('error' => _('Your image upload is not a valid image file.'));
			}

			// generate media hash
			$media_hash = base64_encode(pack("H*", md5(file_get_contents($media['full_path']))));

			// check if media is banned
			$check = $this->db->get_where('banned_md5', array('md5' => $media_hash));

			if ($check->num_rows() > 0)
			{
				if (!unlink($media['full_path']))
					log_message('error', 'post.php/comment: failed to remove media file from cache');

				return array('error' => _('Your image upload has been flagged as inappropriate.'));
			}
		}

		// check comment data for spam regex
		if (check_commentdata($data))
			return array('error' => _('Your post contains contents that is marked as spam.'));

		// check entire length of comment
		if (mb_strlen($comment) > 4096)
			return array('error' => _('Your post was too long.'));

		// check total numbers of lines in comment
		if (count(explode("\n", $comment)) > 20)
			return array('error' => _('Your post had too many lines.'));

		// phpass password for extra security, using the same tank_auth setting since it's cool
		$gophpass = new PasswordHash(
			$this->config->item('phpass_hash_strength', 'tank_auth'),
			$this->config->item('phpass_hash_portable', 'tank_auth')
		);
		$password = $gophpass->HashPassword($password);

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
			return array('error' => _('This post is already being processed...'));

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
					return array('error' => _('Your image was invalid.'));

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
					preview, preview_w, preview_h, media, media_w, media_h, media_size, media_hash, media_filename
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
			WHERE doc_id = ?
			LIMIT 0, 1
		',
			array($this->db->insert_id())
		);

		return array('success' => TRUE, 'posted' => $post->row());
	}


	function delete($board, $post)
	{
		// $post => [doc_id, password, type]
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->radix->get_table($board) . '
			WHERE doc_id = ?
			LIMIT 0, 1
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
		if ($phpass->CheckPassword($data['password'], $row->delpass) !== TRUE && !$this->tank_auth->is_allowed())
		{
			log_message('debug', 'post.php/delete: invalid password');
			return array('error' => _('The password you inserted did not match the post\'s deletion password.'));
		}

		// delete media file for post
		if (!$this->delete_image($board, $row))
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
				SELECT *
				FROM ' . $this->radix->get_table($board) . '
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
					if (!$this->delete_image($board, $t))
					{
						log_message('error', 'post.php/delete: unable to delete media from thread op');
						return array('error' => _('Unable to delete thumbnail for thread replies.'));
					}

					// purge associated reports
					$this->db->delete('reports', array('board_id' => $board->id, 'doc_id' => $p->doc_id));
				}

				// remove all replies
				$this->db->query('
					DELECT
					FROM ' . $this->radix->get_table($board) . '
					WHERE parent = ?
				',
					array($row->num)
				);
			}
		}

		return TRUE;
	}


	function delete_media($board, $row, $media = TRUE, $thumb = TRUE)
	{
		if (!$row->preview)
			return TRUE;

		if ($media === TRUE)
		{
			$media_file = $this->get_media_dir($board, $row);
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
			$thumb_file = $this->get_media_dir($board, $row, TRUE);
			if (file_exists($thumb_file))
			{
				if (!unlink($thumb_file))
				{
					log_message('error', 'post.php/delete_media: unable to remove ' . $thumb_file);
					return FALSE;
				}
			}
		}

		return TRUE;
	}


	function ban_media($hash, $delete = FALSE)
	{
		// insert into global banned media hash
		$this->db->query('
			INSERT IGNORE INTO ' . $this->db->protect_identifiers('banned_md5', TRUE) . ' (md5) VALUES (?)
		', array($hash));

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
						WHERE media_hash = ' . $this->db->escape($hash) . '
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
				$this->delete_media($this->radix->get_by_id($post->board_id), $post);
		}

		return TRUE;
	}


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
		$row = $query->row();

		// mark post as spam


		return TRUE;
	}


}
