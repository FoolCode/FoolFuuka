<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Board extends DataMapper {

	var $has_one = array();
	var $has_many = array();
	var $validation = array(
		'shortname' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Board',
			'type' => 'input'
		),
		'name' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Board Name',
			'type' => 'input'
		),
		'url' => array(
			'rules' => array('max_length' => 256),
			'label' => 'Board URL',
			'type' => 'input'
		),
		'thumbs_url' => array(
			'rules' => array('max_length' => 256),
			'label' => 'Thumbs URL',
			'type' => 'input'
		),
		'images_url' => array(
			'rules' => array('max_length' => 256),
			'label' => 'Images URL',
			'type' => 'input'
		),
		'posting_url' => array(
			'rules' => array('max_length' => 256),
			'label' => 'Posting URL',
			'type' => 'input'
		),
		'archive' => array(
			'rules' => array(),
			'label' => '4chan Board',
			'type' => 'checkbox'
		),
		'thumbnails' => array(
			'rules' => array(),
			'label' => 'Display Thumbnails',
			'type' => 'checkbox'
		),
		'delay_thumbnails' => array(
			'rules' => array(),
			'label' => 'Hide Thumbnails for 1 Day',
			'type' => 'checkbox'
		),
		'sphinx' => array(
			'rules' => array(),
			'label' => 'Use Sphinx search',
			'type' => 'checkbox'
		),
		'hidden' => array(
			'rules' => array(),
			'label' => 'Hide Board',
			'type' => 'checkbox'
		),
		'thread_refresh_rate' => array(
			'rules' => array(),
			'label' => 'Thread Refresh Rate #',
			'type' => 'input'
		),
		'threads_posts' => array(
			'rules' => array('is_natural'),
			'label' => 'Thread Threads #',
			'type' => 'input'
		),
		'threads_media' => array(
			'rules' => array('is_natural'),
			'label' => 'Media Threads #',
			'type' => 'input'
		),
		'threads_thumb' => array(
			'rules' => array('is_natural'),
			'label' => 'Thumb Threads #',
			'type' => 'input'
		),
		'max_ancient_id' => array(
			'rules' => array('is_natural'),
			'label' => 'Description',
		),
		'max_indexed_id' => array(
			'rules' => array('is_natural'),
			'label' => 'Description',
		)
	);


	function __construct($id = NULL)
	{
		parent::__construct($id);
	}


	function post_model_init($from_cache = FALSE)
	{

	}


	public function add($data = array())
	{
		if (!$this->update_board_db($data))
		{
			log_message('error', 'add_board: failed writing to database');
			return false;
		}

		if (!$this->add_board_dir())
		{
			// cleanup
			$this->remove_board_db();
			log_message('error', 'add_board: failed creating board directory');
			return false;
		}

		return true;
	}


	public function remove()
	{
		/*
		if (!$this->remove_board_dir())
		{
			log_message('error', 'remove_board: failed to remove board directory');
			return false;
		}
		*/

		if (!$this->remove_board_db())
		{
			log_message('error', 'remove_board: failed to remove database entry');
			return false;
		}

		return true;
	}


	public function update_board_db($data = array())
	{
		if (isset($data["id"]) && $data["id"] != '')
		{
			$this->where("id", $data["id"])->get();
			if ($this->result_count() == 0)
			{
				set_notice('error', 'The board you wish to modify doesn\'t exist.');
				log_message('error', 'update_board_db: failed to find requested id');
				return false;
			}
		}

		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}

		if ((!isset($this->id) || $this->id == ''))
		{
			$i = 1;
			$found = FALSE;

			$board = new Board();
			$board->where('shortname', $this->shortname)->get();
			if ($board->result_count() == 0)
			{
				$found = TRUE;
			}

			while (!$found)
			{
				$i++;
				$new_shortname = $this->shortname . '_' . $i;
				$board = new Board();
				$board->where('shortname', $new_shortname)->get();
				if ($board->result_count() == 0)
				{
					$this->shortname = $new_shortname;
					$found = TRUE;
				}
			}
		}

		if (isset($old_shortname) && $old_shortname != $this->shortname && is_dir("content/boards/" . $old_shortname))
		{
			$dir_old = "content/boards/" . $old_shortname;
			$dir_new = "content/boards/" . $new_shortname;
			rename($dir_old, $dir_new);
		}

		if (!$this->save())
		{
			if (!$this->valid)
			{
				set_notice('error', 'Please check that you have filled all of the required fields.');
				log_message('error', 'update_board_db: failed validation check');
			}
			else
			{
				set_notice('error', 'Failed to save this entry to the database for unknown reasons.');
				log_message('error', 'update_board_db: failed to save entry');
			}
			return false;
		}

		return true;
	}


	public function remove_board_db()
	{
		if (!$this->delete())
		{
			set_notice('error', 'This board couldn\t be removed from the database for unknown reasons.');
			log_message('error', 'remove_board_db: failed to remove requested id');
			return false;
		}

		return true;
	}


	public function add_board_dir()
	{
		if (!mkdir("content/boards/" . $this->directory()))
		{
			set_notice('error', 'The directory for this board could not be created. Please check your file permissions.');
			log_message('error', 'add_board_dir: failed to create board directory');
			return false;
		}
		return true;
	}


	public function remove_board_dir()
	{
		// Place Holder
	}

	public function check_shortname($shortname) {
		$this->where('shortname', $shortname)->get();
		return $this->result_count() > 0;
	}

	public function directory()
	{
		return $this->shortname;
	}

	public function href()
	{
		return site_url($this->shortname);
	}

}
