<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Radix extends CI_Model
{

	// caching results
	var $preloaded_radixes = null;
	var $preloaded_radixes_array = null;
	var $loaded_radixes_archive = null;
	var $loaded_radixes_archive_array = null;
	var $loaded_radixes_board = null;
	var $loaded_radixes_board_array = null;
	var $selected_radix = null; // readily available if set
	var $selected_radix_array = null;


	function __construct($id = NULL)
	{
		parent::__construct();
		$this->preload();
	}


	/**
	 * The structure of the radix table to be used with validation and form creator
	 *
	 * @param Object $radix If available insert to customize the
	 */
	function structure($radix = NULL)
	{
		return array(
			'open' => array(
				'type' => 'open',
			),
			'id' => array(
				'type' => 'hidden',
				'database' => TRUE,
				'validation_func' => function($input, $form_internal)
				{
					// check that the ID exists
					$CI = & get_instance();
					$query = $CI->db->where('id', $input['id'])->get('boards');
					if ($query->num_rows() != 1)
					{
						return array(
							'error_code' => 'ID_NOT_FOUND',
							'error' => _('Couldn\'t find the board with the submitted ID.'),
							'critical' => TRUE
						);
					}

					return array('success' => TRUE);
				}
			),
			'name' => array(
				'database' => TRUE,
				'type' => 'input',
				'label' => _('Name'),
				'help' => _('Insert the name of the board normally shown as title.'),
				'placeholder' => _('Required'),
				'class' => 'span3',
				'validation' => 'required|max_length[128]'
			),
			'shortname' => array(
				'database' => TRUE,
				'type' => 'input',
				'label' => _('Shortname'),
				'help' => _('Insert the shorter name of the board. Reserved: "api", "cli", "admin".'),
				'placeholder' => _('Req.'),
				'class' => 'span1',
				'validation' => 'required|max_length[5]|alpha_dash',
				'validation_func' => function($input, $form_internal)
				{
					// as of PHP 5.3 we can't yet use $this in Closures, we could in 5.4
					$CI = & get_instance();

					// if we're not using the special subdomain for peripherals
					if (get_setting('fs_srv_sys_subdomain', FOOL_PREF_SYS_SUBDOMAIN) === FALSE)
					{
						if (in_array($input['shortname'], unserialize(FOOL_PROTECTED_RADIXES)))
						{
							return array(
								'error_code' => 'PROTECTED_RADIX',
								'error' => _('You can\'t use the protected shortnames unless you activate the system subdomain feature. The protected shortnames are:') . ' "' . implode(", ",
									unserialize(FOOL_PROTECTED_RADIXES)) . '".'
							);
						}
					}

					// if we're working on the same object
					if (isset($input['id']))
					{
						// existence ensured by CRITICAL in the ID check
						$query = $CI->db->where('id', $input['id'])->get('boards')->row();

						// no change?
						if ($input['shortname'] == $query->shortname)
						{
							// no change
							return array('success' => TRUE);
						}
					}

					// check that there isn't already a board with that name
					$query = $CI->db->where('shortname', $input['shortname'])->get('boards');
					if ($query->num_rows() > 0)
					{
						return array(
							'error_code' => 'ALREADY_EXISTS',
							'error' => _('The shortname is already used for another board.')
						);
					}
				}
			),
			'rules' => array(
				'databse' => TRUE,
				'type' => 'textarea',
				'label' => _('General rules'),
				'help' => _('Full board rules displayed in a separate page, in <a href="http://daringfireball.net/projects/markdown/basics" target="_blank">MarkDown</a> syntax. Will not display if left empty.'),
				'class' => 'span6',
				'placeholder' => _('MarkDown goes here')
			),
			'separator-3' => array(
				'type' => 'separator'
			),
			'posting_rules' => array(
				'databse' => TRUE,
				'type' => 'textarea',
				'label' => _('Posting rules'),
				'help' => _('Posting rules displayed in the posting area, in <a href="http://daringfireball.net/projects/markdown/basics" target="_blank">MarkDown</a> syntax. Will not display if left empty.'),
				'class' => 'span6',
				'placeholder' => _('MarkDown goes here')
			),
			'separator-1' => array(
				'type' => 'separator'
			),
			'archive' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => _('Is this a 4chan archiving board?'),
				'sub' => array(
					'board_url' => array(
						'database' => TRUE,
						'type' => 'input',
						'label' => _('URL to the 4chan board (facultative)'),
						'placeholder' => 'http://boards.4chan.org/' . (is_object($radix) ? $radix->shortname
								: 'shortname') . '/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					),
					'thumbs_url' => array(
						'database' => TRUE,
						'type' => 'input',
						'label' => _('URL to the board thumbnails (facultative)'),
						'placeholder' => 'http://0.thumbs.4chan.org/' . (is_object($radix) ? $radix->shortname
								: 'shortname') . '/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					),
					'images_url' => array(
						'database' => TRUE,
						'type' => 'input',
						'label' => _('URL to the board images (facultative)'),
						'placeholder' => 'http://images.4chan.org/' . (is_object($radix) ? $radix->shortname
								: 'shortname') . '/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					),
					'media_threads' => array(
						'database' => TRUE,
						'type' => 'input',
						'label' => _('Image fetching workers'),
						'help' => _('The number of workers that will fetch full images'),
						'placeholder' => 5,
						'class' => 'span1',
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'thumb_threads' => array(
						'database' => TRUE,
						'type' => 'input',
						'label' => _('Thumbnail fetching workers'),
						'help' => _('The number of workers that will fetch thumbnails'),
						'placeholder' => 5,
						'class' => 'span1',
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'new_threads_threads' => array(
						'database' => TRUE,
						'type' => 'input',
						'label' => _('Thread fetching workers'),
						'help' => _('The number of threads that will fetch thumbnails'),
						'placeholder' => 5,
						'class' => 'span1',
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'thread_refresh_rate' => array(
						'database' => TRUE,
						'type' => 'hidden',
						'value' => 3,
						'label' => _('Minutes to refresh the thread'),
						'placeholder' => 3,
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'page_settings' => array(
						'database' => TRUE,
						'type' => 'textarea',
						'label' => _('Thread refresh rate'),
						'help' => _('Array of refresh rates  in seconds per page in JSON format'),
						'placeholder' => form_prep('[{"delay": 30, "pages": [0, 1, 2]},
{"delay": 120, "pages": [3, 4, 5, 6, 7, 8, 9, 10, 11, 12]},
{"delay": 30, "pages": [13, 14, 15]}]'),
						'class' => 'span4',
						'style' => 'height:70px;',
						'validation_func' => function($input, $form_internal)
						{
							$json = @json_decode($input['page_settings']);
							if (is_null($json))
							{
								return array(
									'error_code' => 'NOT_JSON',
									'error' => _('The JSON inputted is not valid.')
								);
							}
						}
					)
				)
			),
			'hide_thumbnails' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => _('Hide the thumbnails?')
			),
			'delay_thumbnails' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => _('Hide the thumbnails for 24 hours? (for moderation purposes)')
			),
			'sphinx' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => _('Use SphinxSearch as search engine?')
			),
			'hidden' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => _('Hide the board from public access? (only admins and mods will be able to browse it)')
			),
			'separator-2' => array(
				'type' => 'separator-short'
			),
			'submit' => array(
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => _('Submit')
			),
			'close' => array(
				'type' => 'close'
			),
		);
	}


	function save($data)
	{
		// data must be already sanitized through the form array
		if (isset($data['id']))
		{
			$this->db->where('id', $data['id'])->update('boards', $data);
			$this->radix->preload();
		}
		else
		{
			$this->db->insert('boards', $data);
			$this->radix->preload();
			$board = $this->get_by_shortname($data['shortname']);

			$this->db->query("
				CREATE TABLE IF NOT EXISTS " . $this->get_table($board) . " (
					doc_id int unsigned NOT NULL auto_increment,
					media_id int unsigned NOT NULL DEFAULT '0',
					id decimal(39,0) unsigned NOT NULL DEFAULT '0',
					num int unsigned NOT NULL,
					subnum int unsigned NOT NULL,
					parent int unsigned NOT NULL DEFAULT '0',
					timestamp int unsigned NOT NULL,
					preview varchar(20),
					preview_w smallint unsigned NOT NULL DEFAULT '0',
					preview_h smallint unsigned NOT NULL DEFAULT '0',
					media text,
					media_w smallint unsigned NOT NULL DEFAULT '0',
					media_h smallint unsigned NOT NULL DEFAULT '0',
					media_size int unsigned NOT NULL DEFAULT '0',
					media_hash varchar(25),
					media_filename varchar(20),
					spoiler bool NOT NULL DEFAULT '0',
					deleted bool NOT NULL DEFAULT '0',
					capcode enum('N', 'M', 'A', 'G') NOT NULL DEFAULT 'N',
					email varchar(100),
					name varchar(100),
					trip varchar(25),
					title varchar(100),
					comment text,
					delpass tinytext,
					sticky bool NOT NULL DEFAULT '0',

					PRIMARY KEY (doc_id),
					UNIQUE num_subnum_index (num, subnum),
					INDEX id_index(id),
					INDEX num_index(num),
					INDEX subnum_index(subnum),
					INDEX parent_index(parent),
					INDEX timestamp_index(TIMESTAMP),
					INDEX media_hash_index(media_hash),
					INDEX email_index(email),
					INDEX name_index(name),
					INDEX trip_index(trip),
					INDEX fullname_index(name,trip)
				) engine=InnoDB CHARSET=utf8mb4;
			");

			$this->db->query("
				CREATE TABLE IF NOT EXISTS " .
						$this->get_table($board, '_threads') . " (
					`parent` int unsigned NOT NULL,
					`time_op` int unsigned NOT NULL,
					`time_last` int unsigned NOT NULL,
					`time_bump` int unsigned NOT NULL,
					`time_ghost` int unsigned DEFAULT NULL,
					`time_ghost_bump` int unsigned DEFAULT NULL,
					`nreplies` int unsigned NOT NULL DEFAULT '0',
					`nimages` int unsigned NOT NULL DEFAULT '0',
					PRIMARY KEY (`parent`),

					INDEX time_op_index (time_op),
					INDEX time_bump_index (time_bump),
					INDEX time_ghost_bump_index (time_ghost_bump),
				) ENGINE=InnoDB CHARSET=utf8mb4;
			");


			$this->db->query("
				CREATE TABLE IF NOT EXISTS " . $this->get_table($board,
					'_users') . " (
					`name` varchar(100) NOT NULL DEFAULT '',
					`trip` varchar(25) NOT NULL DEFAULT '',
					`firstseen` int(11) NOT NULL,
					`postcount` int(11) NOT NULL,
					PRIMARY KEY (`name`, `trip`),

					INDEX firstseen_index (firstseen),
					INDEX postcount_index (postcount)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			");

			$this->db->query("
				CREATE TABLE IF NOT EXISTS " . $this->get_table($board,
					'_images') . " (
					`id` int unsigned NOT NULL auto_increment,
					`media_hash` varchar(25) NOT NULL,
					`media_filename` varchar(20),
					`preview_op` varchar(20),
					`preview_reply` varchar(20),
					`total` int(10) unsigned NOT NULL DEFAULT '0',
					`banned` smallint unsigned NOT NULL DEFAULT '0',
					PRIMARY KEY (`id`),
					UNIQUE media_hash_index (`media_hash`),
					INDEX total_index (total)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			");

			$this->db->query("
				CREATE TABLE IF NOT EXISTS " . $this->get_table($board,
					'_daily') . " (
					`day` int(10) unsigned NOT NULL,
					`posts` int(10) unsigned NOT NULL,
					`images` int(10) unsigned NOT NULL,
					`sage` int(10) unsigned NOT NULL,
					`anons` int(10) unsigned NOT NULL,
					`trips` int(10) unsigned NOT NULL,
					`names` int(10) unsigned NOT NULL,
					PRIMARY KEY (`day`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			");

			$this->db->query("
				DROP PROCEDURE IF EXISTS `update_thread_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE PROCEDURE `update_thread_" . $board->shortname . "` (tnum INT)
				BEGIN
				UPDATE
					" . $this->get_table($board, '_threads') . " op
				SET
					op.time_last = (
					COALESCE(GREATEST(
						op.time_op,
						(SELECT MAX(timestamp) FROM " . $this->get_table($board) . " re FORCE INDEX(parent_index) WHERE
						re.parent = tnum AND re.subnum = 0)
						), op.time_op)
					),
					op.time_bump = (
						COALESCE(GREATEST(
						op.time_op,
						(SELECT MAX(timestamp) FROM " . $this->get_table($board) . " re FORCE INDEX(parent_index) WHERE
							re.parent = tnum AND (re.email <> 'sage' OR re.email IS NULL)
							AND re.subnum = 0)
						), op.time_op)
					),
					op.time_ghost = (
						SELECT MAX(timestamp) FROM " . $this->get_table($board) . " re FORCE INDEX(parent_index) WHERE
						re.parent = tnum AND re.subnum <> 0
					),
					op.time_ghost_bump = (
						SELECT MAX(timestamp) FROM " . $this->get_table($board) . " re FORCE INDEX(parent_index) WHERE
						re.parent = tnum AND re.subnum <> 0 AND (re.email <> 'sage' OR
							re.email IS NULL)
					),
					op.nreplies = (
						SELECT COUNT(*) FROM " . $this->get_table($board) . " re FORCE INDEX(parent_index) WHERE
						re.parent = tnum
					),
					op.nimages = (
						SELECT COUNT(media_hash) FROM " . $this->get_table($board) . " re FORCE INDEX(parent_index) WHERE
						re.parent = tnum
					)
					WHERE op.parent = tnum;
				END;
			");

			$this->db->query("
				DROP PROCEDURE IF EXISTS `create_thread_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE PROCEDURE `create_thread_" . $board->shortname . "` (num INT, timestamp INT)
				BEGIN
					INSERT IGNORE INTO " . $this->get_table($board,
					'_threads') . " VALUES (num, timestamp, timestamp,
						timestamp, NULL, NULL, 0, 0);
				END;
			");

			$this->db->query("
				DROP PROCEDURE IF EXISTS `delete_thread_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE PROCEDURE `delete_thread_" . $board->shortname . "` (tnum INT)
				BEGIN
					DELETE FROM " . $this->get_table($board,
					'_threads') . " WHERE parent = tnum;
				END;
			");

			$this->db->query("
				DROP PROCEDURE IF EXISTS `insert_image_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE PROCEDURE `insert_image_" . $board->shortname . "` (n_media_hash VARCHAR(25),
					n_media_filename VARCHAR(20), n_preview VARCHAR(20), n_parent INT)
				BEGIN
					IF n_parent = 0 THEN
						INSERT INTO " . $this->get_table($board,
					'_images') . " (media_hash, media_filename, preview_op, total)
						VALUES (n_media_hash, n_media_filename, n_preview, 1)
						ON DUPLICATE KEY UPDATE total = (total + 1), preview_op = COALESCE(preview_op, VALUES(preview_op));
					ELSE
						INSERT INTO " . $this->get_table($board,
					'_images') . " (media_hash, media_filename, preview_reply, total)
						VALUES (n_media_hash, n_media_filename, n_preview, 1)
						ON DUPLICATE KEY UPDATE total = (total + 1), preview_reply = COALESCE(preview_reply, VALUES(preview_reply));
					END IF;
				END;
			");

			$this->db->query("
				DROP PROCEDURE IF EXISTS `delete_image_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE PROCEDURE `delete_image_" . $board->shortname . "` (n_media_id INT)
				BEGIN
					UPDATE " . $this->get_table($board,
					'_images') . " SET total = (total - 1) WHERE id = n_media_id;
				END;
			");

			$this->db->query("
				DROP PROCEDURE IF EXISTS `insert_post_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE PROCEDURE `insert_post_" . $board->shortname . "` (p_timestamp INT, p_media_hash VARCHAR(25),
					p_email VARCHAR(100), p_name VARCHAR(100), p_trip VARCHAR(25))
				BEGIN
					DECLARE d_day INT;
					DECLARE d_image INT;
					DECLARE d_sage INT;
					DECLARE d_anon INT;
					DECLARE d_trip INT;
					DECLARE d_name INT;

					SET d_day = FLOOR(p_timestamp/86400)*86400;
					SET d_image = p_media_hash IS NOT NULL;
					SET d_sage = COALESCE(p_email = 'sage', 0);
					SET d_anon = COALESCE(p_name = 'Anonymous' AND p_trip IS NULL, 0);
					SET d_trip = p_trip IS NOT NULL;
					SET d_name = COALESCE(p_name <> 'Anonymous' AND p_trip IS NULL, 1);

					INSERT INTO " . $this->get_table($board,
					'_daily') . " VALUES(d_day, 1, d_image, d_sage, d_anon, d_trip,
						d_name)
						ON DUPLICATE KEY UPDATE posts=posts+1, images=images+d_image,
						sage=sage+d_sage, anons=anons+d_anon, trips=trips+d_trip,
						names=names+d_name;

					IF (SELECT trip FROM " . $this->get_table($board, '_users') . " WHERE trip = p_trip) IS NOT NULL THEN
						UPDATE " . $this->get_table($board,
					'_users') . " SET postcount=postcount+1,
						firstseen = LEAST(p_timestamp, firstseen)
						WHERE trip = p_trip;
					ELSE
						INSERT INTO " . $this->get_table($board,
					'_users') . " VALUES(COALESCE(p_name,''), COALESCE(p_trip,''), p_timestamp, 1)
						ON DUPLICATE KEY UPDATE postcount=postcount+1,
						firstseen = LEAST(VALUES(firstseen), firstseen);
					END IF;
				END;
			");

			$this->db->query("
				DROP PROCEDURE IF EXISTS `delete_post_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE PROCEDURE `delete_post_" . $board->shortname . "` (p_timestamp INT, p_media_hash VARCHAR(25), p_email VARCHAR(100), p_name VARCHAR(100), p_trip VARCHAR(25))
				BEGIN
					DECLARE d_day INT;
					DECLARE d_image INT;
					DECLARE d_sage INT;
					DECLARE d_anon INT;
					DECLARE d_trip INT;
					DECLARE d_name INT;

					SET d_day = FLOOR(p_timestamp/86400)*86400;
					SET d_image = p_media_hash IS NOT NULL;
					SET d_sage = COALESCE(p_email = 'sage', 0);
					SET d_anon = COALESCE(p_name = 'Anonymous' AND p_trip IS NULL, 0);
					SET d_trip = p_trip IS NOT NULL;
					SET d_name = COALESCE(p_name <> 'Anonymous' AND p_trip IS NULL, 1);

					UPDATE " . $this->get_table($board, '_daily') . " SET posts=posts-1, images=images-d_image,
						sage=sage-d_sage, anons=anons-d_anon, trips=trips-d_trip,
						names=names-d_name WHERE day = d_day;

					IF (SELECT trip FROM " . $this->get_table($board,
					'_users') . " WHERE trip = p_trip) IS NOT NULL THEN
						UPDATE " . $this->get_table($board,
					'_users') . " SET postcount = postcount-1 WHERE trip = p_trip;
					ELSE
						UPDATE " . $this->get_table($board,
					'_users') . " SET postcount = postcount-1 WHERE
						name = COALESCE(p_name, '') AND trip = COALESCE(p_trip, '');
					END IF;
				END;
			");

			$this->db->query("
				DROP TRIGGER IF EXISTS `before_ins_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE TRIGGER `before_ins_" . $board->shortname . "` BEFORE INSERT ON " . $this->get_table($board) . "
					FOR EACH ROW
					BEGIN
					IF NEW.media_hash IS NOT NULL THEN
						CALL insert_image_" . $board->shortname . "(NEW.media_hash, NEW.media_filename, NEW.preview, NEW.parent);
						SET NEW.media_id = LAST_INSERT_ID();
					END IF;
				END;
			");

			$this->db->query("
				DROP TRIGGER IF EXISTS `after_ins_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE TRIGGER `after_ins_" . $board->shortname . "` AFTER INSERT ON " . $this->get_table($board) . "
					FOR EACH ROW
					BEGIN
					IF NEW.parent = 0 THEN
						CALL create_thread_" . $board->shortname . "(NEW.num, NEW.timestamp);
					END IF;
					CALL update_thread_" . $board->shortname . "(NEW.parent);
					CALL insert_post_" . $board->shortname . "(NEW.timestamp, NEW.media_hash, NEW.email, NEW.name,
						NEW.trip);
				END;
			");

			$this->db->query("
				DROP TRIGGER IF EXISTS `after_del_" . $board->shortname . "`;
			");

			$this->db->query("
				CREATE TRIGGER `after_del_" . $board->shortname . "` AFTER DELETE ON " . $this->get_table($board) . "
				FOR EACH ROW
				BEGIN
				CALL update_thread_" . $board->shortname . "(OLD.parent);
				IF OLD.parent = 0 THEN
					CALL delete_thread_" . $board->shortname . "(OLD.num);
				END IF;
				CALL delete_post_" . $board->shortname . "(OLD.timestamp, OLD.media_hash, OLD.email, OLD.name,
					OLD.trip);
				IF OLD.media_hash IS NOT NULL THEN
					CALL delete_image_" . $board->shortname . "(OLD.media_id);
				END IF;
				END;
			");
		}
	}


	function remove($id)
	{
		$board = $this->get_by_id($id);

		$this->db->query("
			DROP TABLE IF EXISTS " . $this->get_table($board) . "
		");

		$this->db->query("
			DROP TABLE IF EXISTS " . $this->get_table($board, '_images') . "
		");

		$this->db->query("
			DROP TABLE IF EXISTS " . $this->get_table($board, '_daily') . "
		");

		$this->db->query("
			DROP TABLE IF EXISTS " . $this->get_table($board, '_users') . "
		");

		$this->db->query("
			DROP TABLE IF EXISTS " . $this->get_table($board, '_threads') . "
		");

		$this->db->query("
			DROP PROCEDURE IF EXISTS `update_thread_" . $board->shortname . "`;
		");

		$this->db->query("
			DROP PROCEDURE IF EXISTS `create_thread_" . $board->shortname . "`;
		");

		$this->db->query("
			DROP PROCEDURE IF EXISTS `delete_thread_" . $board->shortname . "`;
		");

		$this->db->query("
			DROP PROCEDURE IF EXISTS `insert_image_" . $board->shortname . "`;
		");

		$this->db->query("
			DROP PROCEDURE IF EXISTS `delete_image_" . $board->shortname . "`;
		");

		$this->db->query("
			DROP PROCEDURE IF EXISTS `insert_post_" . $board->shortname . "`;
		");

		$this->db->query("
			DROP PROCEDURE IF EXISTS `delete_post_" . $board->shortname . "`;
		");

		$this->db->query("
			DROP TRIGGER IF EXISTS `before_ins_" . $board->shortname . "`;
		");

		$this->db->query("
			DROP TRIGGER IF EXISTS `after_del_" . $board->shortname . "`;
		");

		$this->db->where('id', $id)->delete('boards');


		return TRUE;
	}


	/**
	 * Puts the table in readily available variables
	 */
	function preload()
	{
		if (!$this->tank_auth->is_allowed())
		{
			$this->db->where('hidden', 0);
		}

		$this->db->order_by('shortname', 'ASC');
		$query = $this->db->get('boards');
		if ($query->num_rows() == 0)
		{
			$this->preloaded_radixes = array();
			$this->preloaded_radixes_array = array();
			return FALSE;
		}

		$object = $query->result();
		$array = $query->result_array();

		foreach ($object as $item)
		{
			$result_object[$item->id] = $item;
			$result_object[$item->id]->formatted_title = ($item->name) ?
				'/' . $item->shortname . '/ - ' . $item->name : '/' . $item->shortname . '/';
			$result_object[$item->id]->href = site_url(array($item->shortname));
		}

		foreach ($array as $item)
		{
			$result_array[$item['id']] = $item;
			$result_array[$item['id']]['formatted_title'] = ($item['name']) ?
				'/' . $item['shortname'] . '/ - ' . $item['name'] : '/' . $item['shortname'] . '/';
			$result_array[$item['id']]['href'] = site_url(array($item['shortname']));
		}

		$this->preloaded_radixes = $result_object;
		$this->preloaded_radixes_array = $result_array;
	}


	/**
	 *
	 * @param string $shortname The shortname, or the whole board object
	 * @param string $suffix board suffix like _images
	 * @return type
	 */
	function get_table($shortname, $suffix = '')
	{
		if (is_object($shortname))
			$shortname = $shortname->shortname;

		if (get_setting('fs_fuuka_boards_db'))
		{
			return '`' . get_setting('fs_fuuka_boards_db') . '`.`' . $shortname . $suffix . '`';
		}
		else
		{
			return $this->db->protect_identifiers('board_' . $shortname . $suffix, TRUE);
		}
	}


	/**
	 * Set a radix for execution (example: chan.php)
	 * Always returns object, array can be returned by get_selected_radix_array()
	 *
	 * @param type $shortname
	 * @return type
	 */
	function set_selected_by_shortname($shortname)
	{
		if (FALSE != ($val = $this->get_by_shortname($shortname)))
		{
			$this->selected_radix = $val;
			// clean up the array version
			$this->selected_radix_array = null;
			return $val;
		}

		$this->selected_radix = FALSE;
		$this->selected_radix_array = FALSE;

		return FALSE;
	}


	function get_selected()
	{
		if (is_null($this->selected_radix))
		{
			// we are now using this even to check if any is selected
			//log_message('error', 'radix.php get_selected_radix(): no radix selected');
			return FALSE;
		}

		return $this->selected_radix;
	}


	function get_selected_array()
	{

		if ($this->get_selected() === FALSE)
			return FALSE;

		return $this->selected_radix_array = $this->get_by_id_array($this->get_selected()->id);
	}


	/**
	 * Returns all the radixes as array of objects
	 *
	 * @return array
	 */
	function get_all()
	{
		return $this->preloaded_radixes;
	}


	/**
	 * Returns all the radixes as array of arrays
	 *
	 * @return array
	 */
	function get_all_array()
	{
		return $this->preloaded_radixes_array;
	}


	/**
	 * Returns the single radix
	 */
	function get_by_id($radix_id, $array = FALSE)
	{
		if ($array)
			$items = $this->get_all_array();
		else
			$items = $this->get_all();

		if (isset($items[$radix_id]))
			return $items[$radix_id];

		return FALSE;
	}


	/**
	 * Returns the single radix as array
	 */
	function get_by_id_array($radix_id)
	{
		return $this->get_by_id($radix_id, TRUE);
	}


	/**
	 * Returns the single radix by type selected
	 *
	 * @param type $value
	 * @param type $type
	 * @param type $switch
	 * @param type $array
	 * @return type
	 */
	function get_by_type($value, $type, $switch = TRUE)
	{
		$items = $this->get_all();

		foreach ($items as $item)
		{
			if ($switch == ($item->$type === $value))
			{
				return $item;
			}
		}

		return FALSE;
	}


	/**
	 * Returns the single radix by type selected as array
	 *
	 * @param type $value
	 * @param type $type
	 * @param type $switch
	 * @param type $array
	 * @return type
	 */
	function get_by_type_array($value, $type, $switch = TRUE)
	{
		$items = $this->get_all();

		foreach ($items as $item)
		{
			if ($switch === ($item[$type] === $value))
			{
				return $item;
			}
		}

		return FALSE;
	}


	/**
	 * Returns the single radix by shortname
	 */
	function get_by_shortname($shortname)
	{
		return $this->get_by_type($shortname, 'shortname');
	}


	/**
	 * Returns the single radix as array by shortname
	 */
	function get_by_shortname_array($shortname)
	{
		return $this->get_by_type_array($shortname, 'shortname');
	}


	/**
	 * Returns only the type specified (exam)
	 *
	 * @param string $type 'archive'
	 * @param boolean $switch 'archive'
	 * @param type $array
	 * @return type
	 */
	function filter_by_type($type, $switch, $array = FALSE)
	{
		if ($array)
			$items = $this->get_all_array();
		else
			$items = $this->get_all();

		foreach ($items as $key => $item)
		{
			if ($array)
			{
				if ($item[$type] != $switch)
					unset($items[$key]);
			}
			else
			{
				if ($item->$type != $switch)
					unset($items[$key]);
			}
		}

		return $items;
	}


	/**
	 *  Returns an array of objects that are archives
	 */
	function get_archives()
	{
		if (!is_null($this->loaded_radixes_archive))
			return $this->loaded_radixes_archive;

		return $this->loaded_radixes_archive = $this->filter_by_type('archive', TRUE);
	}


	/**
	 *  Returns an array of objects that are boards (not archives)
	 */
	function get_boards()
	{
		if (!is_null($this->loaded_radixes_board))
			return $this->loaded_radixes_board;

		return $this->loaded_radixes_board = $this->filter_by_type('archive', FALSE);
	}


	/**
	 *  Returns an array of arrays that are archives
	 */
	function get_archives_array()
	{
		if (!is_null($this->loaded_radixes_archive_array))
			return $this->loaded_radixes_archive_array;

		return $this->loaded_radixes_archive_array = $this->filter_by_type('archive',
			TRUE, TRUE);
	}


	/**
	 *  Returns an array of arrays that are boards (not archives)
	 */
	function get_boards_array()
	{
		if (!is_null($this->loaded_radixes_board_array))
			return $this->loaded_radixes_board_array;

		return $this->loaded_radixes_board_array = $this->filter_by_type('archive',
			FALSE, TRUE);
	}

}