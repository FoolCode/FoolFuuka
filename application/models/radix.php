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


	function board_structure()
	{
		return array(
			'open' => array(
				'type' => 'open',
			),
			'id' => array(
				'type' => 'hidden',
			),
			'name' => array(
				'type' => 'input',
				'label' => _('Name'),
				'help' => _('Insert the name of the board normally shown as title.'),
				'placeholder' => _('Required'),
				'class' => 'span3',
				'validation' => 'required|max_length[128]'
			),
			'shortname' => array(
				'type' => 'input',
				'label' => _('Shortname'),
				'help' => _('Insert the shorter name of the board. Reserved: "api", "cli", "admin".'),
				'placeholder' => _('Required'),
				'class' => 'span1',
				'validation' => 'required|max_length[5]',
				'validation_func' => function($shortname, $current = NULL)
				{
					// if we're not using the special subdomain for peripherals
					if (get_setting('fs_srv_sys_subdomain', FOOL_PREF_SYS_SUBDOMAIN) === FALSE)
					{
						if (in_array($shortname, unserialize(FOOL_PROTECTED_RADIXES)))
						{
							return array(
								'error_code' => 'PROTECTED_RADIX',
								'error' => _('You can\'t use the protected shortnames unless you activate the system subdomain feature. The protected shortnames are:') . ' "' . implode(", ",
									unserialize(FOOL_PROTECTED_RADIXES)) . '".'
							);
						}
					}

					if (!is_null($current))
					{
						// no change
						if ($shortname == $current)
						{
							// no change
							return array('success' => TRUE);
						}
					}
					else 
					{
						
					}
				}
			),
			'separator-1' => array(
				'type' => 'separator'
			),
			'archive' => array(
				'type' => 'checkbox',
				'help' => _('Is this a 4chan archiving board?')
			),
			'thumbnails' => array(
				'type' => 'checkbox',
				'help' => _('Display the thumbnails?')
			),
			'delay_thumbnails' => array(
				'type' => 'checkbox',
				'help' => _('Hide the thumbnails for 24 hours? (for moderation purposes)')
			),
			'sphinx' => array(
				'type' => 'checkbox',
				'help' => _('Use SphinxSearch as search engine?')
			),
			'hidden' => array(
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


	/**
	 * Puts the table in readily available variables
	 */
	function preload()
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('boards',
				TRUE) . '
			ORDER BY shortname ASC
		');

		if ($query->num_rows() == 0)
		{
			$this->preloaded_radixes = array();
			$this->preloaded_radixes_array = array();
			return FALSE;
		}

		$object = $query->result();
		$array = $query->result_array();
		$result = array();

		foreach ($object as $item)
		{
			$result_object[$item->id] = $item;
			$result_object[$item->id]->href = site_url(array($item->shortname));
		}

		foreach ($array as $item)
		{
			$result_array[$item['id']] = $item;
			$result_array[$item['id']]['href'] = site_url(array($item['shortname']));
		}

		$this->preloaded_radixes = $result_object;
		$this->preloaded_radixes_array = $result_array;
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
			log_message('error', 'radix.php get_selected_radix(): no radix selected');
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


	function save($data)
	{
		//if($data['shortname'])
		// check presence of shortname in database
		//$this->db->
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