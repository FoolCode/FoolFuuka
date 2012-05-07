<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Radix_model extends CI_Model
{

	// caching results
	var $preloaded_radixes = null;
	var $loaded_radixes_archive = null;
	var $loaded_radixes_board = null;
	var $selected_radix = null; // readily available if set


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
				'database' => TRUE,
				'boards_preferences' => TRUE,
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
				'database' => TRUE,
				'boards_preferences' => TRUE,
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
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => _('URL to the 4chan board (facultative)'),
						'placeholder' => 'http://boards.4chan.org/' . (is_object($radix) ? $radix->shortname : 'shortname') . '/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					),
					'thumbs_url' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => _('URL to the board thumbnails (facultative)'),
						'placeholder' => 'http://0.thumbs.4chan.org/' . (is_object($radix) ? $radix->shortname : 'shortname') . '/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					),
					'images_url' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => _('URL to the board images (facultative)'),
						'placeholder' => 'http://images.4chan.org/' . (is_object($radix) ? $radix->shortname : 'shortname') . '/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					),
					'media_threads' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => _('Image fetching workers'),
						'help' => _('The number of workers that will fetch full images. Set to zero not to fetch them.'),
						'placeholder' => 5,
						'value' => 0,
						'class' => 'span1',
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'thumb_threads' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => _('Thumbnail fetching workers'),
						'help' => _('The number of workers that will fetch thumbnails'),
						'placeholder' => 5,
						'value' => 5,
						'class' => 'span1',
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'new_threads_threads' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'input',
						'label' => _('Thread fetching workers'),
						'help' => _('The number of workers that fetch new threads'),
						'placeholder' => 5,
						'value' => 5,
						'class' => 'span1',
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'thread_refresh_rate' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'type' => 'hidden',
						'value' => 3,
						'label' => _('Minutes to refresh the thread'),
						'placeholder' => 3,
						'validation' => 'trim|is_natural|less_than[32]'
					),
					'page_settings' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
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
				),
				'sub_inverse' => array(
					'thumbnail_op_width' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => _('Opening post thumbnail maximum width'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_THUMB_OP_WIDTH
					),
					'thumbnail_op_height' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => _('Opening post thumbnail maximum height'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_THUMB_OP_HEIGHT
					),
					'thumbnail_reply_width' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => _('Reply thumbnail maximum width'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_THUMB_REPLY_WIDTH
					),
					'thumbnail_reply_height' => array(
						'database' => TRUE,
						'boards_preferences' => TRUE,
						'label' => _('Reply thumbnail maximum height'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|is_natural|greater_than[25]',
						'default_value' => FOOL_RADIX_THUMB_REPLY_HEIGHT
					),
				)
			),
			'hide_thumbnails' => array(
				'database' => TRUE,
				'type' => 'checkbox',
				'help' => _('Hide the thumbnails?')
			),
			'delay_thumbnails' => array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
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
		// filter _boards data from _boards_preferences data
		$structure = $this->structure();
		$data_boards = array();
		$data_boards_preferences = array();
		
		foreach($structure as $key => $item)
		{
			// mix the sub and sub_inverse and flatten the array
			if(isset($item['sub_inverse']) && isset($item['sub']))
			{
				$item['sub'] = array_merge($item['sub'], $item['sub_inverse']);
			}
			
			if(isset($item['sub']))
			{	
				foreach($item['sub'] as $k => $i)
				{
					if(isset($i['boards_preferences']) && isset($data[$k]))
					{
						$data_boards_preferences[$k] = $data[$k];
						unset($data[$k]);
					}
				}
			}
			
			if(isset($item['boards_preferences']) && isset($data[$key]))
			{
				$data_boards_preferences[$key] = $data[$key];
				unset($data[$key]);
			}
				
		}
		
		// data must be already sanitized through the form array
		if (isset($data['id']))
		{
			if(!$radix = $this->get_by_id($data['id']))
			{
				show_404();
			}
			
			// save normal values
			$this->db->where('id', $data['id'])->update('boards', $data);
			
			// save extra preferences
			foreach($data_boards_preferences as $name => $value)
			{
				$query = $this->db->where('board_id', $data['id'])->where('name', $name)->get('boards_preferences');
				if($query->num_rows())
				{
					$this->db->where('board_id', $data['id'])->where('name', $name)
						->update('boards_preferences', array('value' => $value));
				}
				else
				{
					$this->db->insert('boards_preferences', array('board_id' => $data['id'], 'name' => $name, 'value' => $value));
				}
			}
			
			$this->radix->preload();
		}
		else
		{
			$this->db->insert('boards', $data);
			$id = $this->db->insert_id();
			
			// save extra preferences
			foreach($data_boards_preferences as $name => $value)
			{
				$query = $this->db->where('board_id', $id)->where('name', $name)->get('boards_preferences');
				if($query->num_rows())
				{
					$this->db->where('board_id', $id)->where('name', $name)
						->update('boards_preferences', array($name => $value));
				}
				else
				{
					$this->db->insert('boards_preferences', array('board_id' => $id, 'name' => $name, 'value' => $value));
				}
			}
			
			$this->radix->preload();
			$board = $this->get_by_shortname($data['shortname']);

			$this->mysql_remove_triggers($board);
			$this->mysql_create_board($board);
			$this->mysql_create_triggers($board);
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



		$this->db->where('id', $id)->delete('boards');
		$this->db->where('board_id', $id)->delete('boards_preferences');


		return TRUE;
	}


	/**
	 * Puts the table in readily available variables
	 */
	function preload($preferences = FALSE)
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
			return FALSE;
		}

		$object = $query->result();

		foreach ($object as $item)
		{
			$structure = $this->structure($item);
			
			$result_object[$item->id] = $item;
			$result_object[$item->id]->formatted_title = ($item->name) ?
				'/' . $item->shortname . '/ - ' . $item->name : '/' . $item->shortname . '/';

			if ($item->archive == 1)
			{
				$result_object[$item->id]->href = site_url(array('@archive', $item->shortname));
			}
			else
			{
				$result_object[$item->id]->href = site_url(array('@board', $item->shortname));
			}
			
			// load the basic value of the preferences
			foreach($structure as $key => $arr)
			{
				if(!isset($result_object[$item->id]->$key) && isset($arr['boards_preferences']))
				{
					if(isset($arr['default_value']))
						$result_object[$item->id]->$key = $arr['default_value'];
					else
						$result_object[$item->id]->$key = FALSE;
				}
				
				if(isset($arr['sub']))
				{
					foreach($arr['sub'] as $k => $a)
					{
						if(!isset($result_object[$item->id]->$k) && isset($a['boards_preferences']))
						{
							if(isset($arr['default_value']))
								$result_object[$item->id]->$k = $a['default_value'];
							else
								$result_object[$item->id]->$k = FALSE;
						}
					}
				}
				
			}
			
		}
		
		//echo '<pre>'.print_r($result_object, true).'</pre>';

		$this->preloaded_radixes = $result_object;
		
		if(TRUE || $preferences == TRUE)
			$this->load_preferences();
	}

	/**
	 * Loads preferences data for the board. 
	 *
	 * @param type $board null/array of IDs/ID/board object
	 */
	function load_preferences($board = NULL)
	{
		if(is_null($board))
		{	
			$ids = array_keys($this->preloaded_radixes);
		}
		else if (is_array($board))
		{
			$ids = $board;
		}
		else if (is_object($board))
		{
			$ids = array($board->id);
		}
		else // it's an id
		{
			$ids = array($board);
		}
		
		$selected = FALSE;
		foreach($ids as $id)
		{
			$query = $this->db->where('board_id', $id)->get('boards_preferences');
			foreach($query->result() as $value)
			{;
				$this->preloaded_radixes[$id]->{$value->name} = $value->value;
			}
			
			$selected = $this->preloaded_radixes[$id];
		}

		// useful if only one has been selected
		return $selected;
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
	 *
	 * @param type $shortname
	 * @return type
	 */
	function set_selected_by_shortname($shortname)
	{
		if (FALSE != ($val = $this->get_by_shortname($shortname)))
		{
			$this->selected_radix = $val;
			$val = $this->load_preferences($val);
			return $val;
		}

		$this->selected_radix = FALSE;

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
	 * Returns the single radix
	 */
	function get_by_id($radix_id)
	{
		$items = $this->get_all();

		if (isset($items[$radix_id]))
			return $items[$radix_id];

		return FALSE;
	}


	/**
	 * Returns the single radix by type selected
	 *
	 * @param type $value
	 * @param type $type
	 * @param type $switch
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
	 * Returns the single radix by shortname
	 */
	function get_by_shortname($shortname)
	{
		return $this->get_by_type($shortname, 'shortname');
	}


	/**
	 * Returns only the type specified (exam)
	 *
	 * @param string $type 'archive'
	 * @param boolean $switch 'archive'
	 * @return type
	 */
	function filter_by_type($type, $switch)
	{
		$items = $this->get_all();

		foreach ($items as $key => $item)
		{
			if ($item->$type != $switch)
				unset($items[$key]);
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
	 * Tells us if the entire MySQL server is compatible with multibyte 
	 */
	function mysql_check_multibyte()
	{
		$query = $this->db->query("SHOW CHARACTER SET WHERE Charset = 'utf8mb4';");
		return (boolean) $query->num_rows();
	}


	function mysql_add_board($board)
	{
		if ($this->mysql_check_multibyte())
		{
			$charset = 'utf8mb4';
		}
		else
		{
			$charset = 'utf8';
		}

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
			) engine=InnoDB CHARSET=" . $charset . ";
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . $this->get_table($board, '_threads') . " (
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
			) ENGINE=InnoDB CHARSET=" . $charset . ";
		");


		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . $this->get_table($board, '_users') . " (
				`name` varchar(100) NOT NULL DEFAULT '',
				`trip` varchar(25) NOT NULL DEFAULT '',
				`firstseen` int(11) NOT NULL,
				`postcount` int(11) NOT NULL,
				PRIMARY KEY (`name`, `trip`),

				INDEX firstseen_index (firstseen),
				INDEX postcount_index (postcount)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ";
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . $this->get_table($board, '_images') . " (
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
			CREATE TABLE IF NOT EXISTS " . $this->get_table($board, '_daily') . " (
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
	}


	function mysql_add_triggers($board)
	{
		$this->db->query("
			CREATE PROCEDURE `update_thread_" . $board->shortname . "` (tnum INT)
			BEGIN
			UPDATE
				" . $this->get_table($board,
			'_threads') . " op
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
			CREATE PROCEDURE `create_thread_" . $board->shortname . "` (num INT, timestamp INT)
			BEGIN
				INSERT IGNORE INTO " . $this->get_table($board,
			'_threads') . " VALUES (num, timestamp, timestamp,
					timestamp, NULL, NULL, 0, 0);
			END;
		");

		$this->db->query("
			CREATE PROCEDURE `delete_thread_" . $board->shortname . "` (tnum INT)
			BEGIN
				DELETE FROM " . $this->get_table($board,
			'_threads') . " WHERE parent = tnum;
			END;
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
			CREATE PROCEDURE `delete_image_" . $board->shortname . "` (n_media_id INT)
			BEGIN
				UPDATE " . $this->get_table($board,
			'_images') . " SET total = (total - 1) WHERE id = n_media_id;
			END;
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

				IF (SELECT trip FROM " . $this->get_table($board,
			'_users') . " WHERE trip = p_trip) IS NOT NULL THEN
					UPDATE " . $this->get_table($board, '_users') . " SET postcount=postcount+1,
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

				UPDATE " . $this->get_table($board,
			'_daily') . " SET posts=posts-1, images=images-d_image,
					sage=sage-d_sage, anons=anons-d_anon, trips=trips-d_trip,
					names=names-d_name WHERE day = d_day;

				IF (SELECT trip FROM " . $this->get_table($board,
			'_users') . " WHERE trip = p_trip) IS NOT NULL THEN
					UPDATE " . $this->get_table($board, '_users') . " SET postcount = postcount-1 WHERE trip = p_trip;
				ELSE
					UPDATE " . $this->get_table($board,
			'_users') . " SET postcount = postcount-1 WHERE
					name = COALESCE(p_name, '') AND trip = COALESCE(p_trip, '');
				END IF;
			END;
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


	function mysql_remove_triggers($board)
	{
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
	}

}