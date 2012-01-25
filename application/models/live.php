<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Live extends CI_Model
{
	function __construct($id = NULL)
	{
		parent::__construct();
	}


	function cron()
	{
		$boards = new Board();
		$boards->get();
		
		foreach($boards->all as $board)
		{
			$this->run($board);
		}
	}

	function run($board)
	{
		
	}

	function get_table($board)
	{
		if (get_setting('fs_fuuka_boards_db'))
		{
			return $this->table = $this->db->protect_identifiers(get_setting('fs_fuuka_boards_db')) . '.' . $this->db->protect_identifiers($board->shortname);
			;
		}
		return $this->table = $this->db->protect_identifiers('board_' . $board->shortname, TRUE);
	}


	function get_recent($board)
	{
		$latest_doc_id = get_setting('fs_cron_live_latest_doc_id');
		if ($latest_doc_id == FALSE)
			$latest_doc_id = 0;

		$query = $this->db->query('
			SELECT *
			FROM ' . $this->get_table($board) . '
			WHERE doc_id > ?
			ORDER BY doc_id DESC
			LIMIT 0, 200
		', array($latest_doc_id));
	}
	
	
	function libpuzzle_store($row)
	{
		$this->load->model('post');
		puzzle_fill_cvec_from_file($filename);
	}


}