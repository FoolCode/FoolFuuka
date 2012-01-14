<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Report extends DataMapper
{

	var $has_one = array();
	var $has_many = array();
	var $validation = array(
		'board' => array(
			'rules' => array('required', 'is_int'),
			'label' => 'Board',
			'type' => 'input'
		),
		'post' => array(
			'rules' => array('required', 'is_int'),
			'label' => 'Post',
			'type' => 'input'
		),
		'reason' => array(
			'rules' => array(),
			'label' => 'Reason',
			'type' => 'textarea'
		)
	);

	function __construct($id = NULL)
	{
		parent::__construct(NULL);

		// We've overwrote some functions, and we need to use the get() from THIS model
		if (!empty($id) && is_numeric($id))
		{
			$this->where('id', $id)->get();
		}
	}


	function post_model_init($from_cache = FALSE)
	{

	}


	public function get($limit = NULL, $offset = NULL)
	{
		$CI = & get_instance();

		return parent::get($limit, $offset);
	}


	public function get_reported_post($report)
	{
		$board = new Board();
		$board->where('id', $report->board)->get();

		if ($board->result_count() == 0)
			return FALSE;

		$sql = 'SELECT *, CONCAT('.$this->db->escape($board->shortname).') AS shortname
			FROM ' . $this->get_table($board->shortname) . '
			LEFT JOIN
				(
					SELECT id as report_id, post as report_doc_id, reason as report_reason, status as report_status, created as report_created
					FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
					WHERE `id` = ' . $report->id . '
				) as q
				ON
				' . $this->get_table($board->shortname) . '.`doc_id`
				=
				' . $this->db->protect_identifiers('q') . '.`report_doc_id`
			LEFT JOIN
				(
					SELECT id AS poster_id_join,
						ip AS poster_ip, user_agent AS poster_user_agent,
						banned AS poster_banned, banned_reason AS poster_banned_reason,
						banned_start AS poster_banned_start, banned_end AS poster_banned_end
					FROM'.$this->db->protect_identifiers('posters', TRUE).'
				) as p
				ON
				' . $this->get_table($board->shortname) . '.`poster_id`
				=
				' . $this->db->protect_identifiers('p') . '.`poster_id_join`
			WHERE doc_id = ' . $this->db->escape($report->post) . '
			LIMIT 0, 1';

		$query = $this->db->query($sql);

		if ($query->num_rows() == 0)
			return FALSE;

		return $query->row();
	}


	public function add($data = array())
	{
		if (!$this->update_report_db($data))
		{
			log_message('error', 'add_report: failed writing to database');
			return FALSE;
		}

		return TRUE;
	}


	public function process_report($id = 0, $action = array())
	{
		if (empty($action) || !isset($action['process']))
		{
			log_message('error', 'process_report: invalid operation');
			return FALSE;
		}

		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
			WHERE `id` = ' . $id . '
			LIMIT 0,1
		');

		if ($query->num_rows() == 0)
		{
			log_message('error', 'process_report: failed to process report, it does not exist');
			$this->db->delete('reports', array('id' => $report->id));
			return FALSE;
		}

		$report = $query->row();
		$postdata = $this->get_reported_post($report);

		if ($postdata === FALSE)
		{
			log_message('error', 'process_report: failed to process post, it does not exist');
			$this->db->delete('reports', array('id' => $report->id));
			return FALSE;

		}

		switch ($action['process'])
		{
			case('ban'):
				if ($postdata->poster_ip == "")
				{
					$this->db->delete('reports', array('id' => $report->id));
					return FALSE;
				}
				$this->db->update('posters', array('banned' => 1, 'banned_reason' => $action['banned_reason'], 'banned_start' => $action['banned_start'], 'banned_end' => $action['banned_end']), array('id' => $postdata->poster_id));
				$this->db->delete('reports', array('id' => $report->id));
				return $postdata;
				break;

			case('delete'):
				set_selected_board($postdata->shortname);
				$post = new Post(FALSE);
				$data = array('post' => $postdata->doc_id, 'password' => $postdata->delpass, 'remove' => $action['remove']);
				$result = $post->delete($data);
				if (isset($result['error']))
				{
					log_message('error', 'process_report: failed to delete post');
					return FALSE;
				}
				$this->db->delete('reports', array('id' => $report->id));
				return TRUE;
				break;

			case('spam'):
				set_selected_board($postdata->shortname);
				$post = new Post(FALSE);
				$result = $post->spam($postdata->doc_id);
				if (isset($result['error']))
				{
					log_message('error', 'process_report: failed to mark post as spam');
					return FALSE;
				}
				$this->db->delete('reports', array('id' => $report->id));
				return TRUE;
				break;
		}

		return FALSE;
	}


	public function update_report_db($data = array())
	{
		if (isset($data["id"]) && $data["id"] != '')
		{
			$this->where("id", $data["id"])->get();
			if ($this->result_count() == 0)
			{
				set_notice('error', 'The report you wish to modify doesn\'t exist.');
				log_message('error', 'update_report_db: failed to find requested id');
				return FALSE;
			}
		}

		// Loop over the array and assign values to the variables.
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}

		if (!$this->save())
		{
			if (!$this->valid)
			{
				set_notice('error', 'Please check that you have filled all of the required fields.');
				log_message('error', 'update_report_db: failed validation check');
			}
			else
			{
				set_notice('error', 'Failed to save this entry to the database for unknown reasons.');
				log_message('error', 'update_report_db: failed to save entry');
			}
			return FALSE;
		}

		return TRUE;
	}


	public function remove_report_db()
	{
		if (!$this->delete())
		{
			set_notice('error', 'This report couldn\'t be removed from the database for unknown reasons.');
			log_message('error', 'remove_report_db: failed to removed requested id');
			return FALSE;
		}

		return TRUE;
	}


	public function move_thumbnail($source, $destination)
	{
		if (!rename($source, $destination))
		{
			set_notice('error', 'This thumbnail could not be moved. Please check your file permissions.');
			log_message('error', 'move_thumbnail: failed to move thumbnail');
			return FALSE;
		}
		return TRUE;
	}


	public function get_table($shortname)
	{
		if (get_setting('fs_fuuka_boards_db'))
		{
			return $this->table = $this->db->protect_identifiers(get_setting('fs_fuuka_boards_db')) . '.' . $this->db->protect_identifiers($shortname);
		}
		return $this->table = $this->db->protect_identifiers('board_' . $shortname, TRUE);
	}


	public function list_all_reports($page = 1, $per_page = 15)
	{
		$reports = $this->get_paged($page, $per_page);

		if($reports->paged->total_rows == 0)
			return array();

		$boards = new Board();
		$boards->get();

		$selects = array();
		foreach ($reports->all as $report)
		{
			foreach ($boards->all as $board)
			{
				if ($board->id == $report->board)
				{
					$selects[] = '
					(
						SELECT *, CONCAT('.$this->db->escape($board->shortname).') AS shortname
						FROM ' . $this->get_table($board->shortname) . '
						LEFT JOIN
							(
								SELECT id as report_id, post as report_post, reason as report_reason, status as report_status, created as report_created
								FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
								WHERE `id` = ' . $report->id . '
							) as q
							ON
							' . $this->get_table($board->shortname) . '.`doc_id`
							=
							' . $this->db->protect_identifiers('q') . '.`report_post`
						LEFT JOIN
							(
								SELECT id AS poster_id_join,
									ip AS poster_ip, user_agent AS poster_user_agent,
									banned AS poster_banned, banned_reason AS poster_banned_reason,
									banned_start AS poster_banned_start, banned_end AS poster_banned_end
								FROM'.$this->db->protect_identifiers('posters', TRUE).'
							) as p
							ON ' . $this->get_table($board->shortname) . '.`poster_id`
							=
							' . $this->db->protect_identifiers('p') . '.`poster_id_join`
						WHERE doc_id = ' . $this->db->escape($report->post) . '
						LIMIT 0, 1
					)';
				}
			}
		}

		$sql = implode(' UNION ', $selects);
		$query = $this->db->query($sql);

		$reports->all = $query->result();
		return $reports;
	}


	public function list_reports_all_boards($page = 1, $per_page = 100)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
			ORDER BY created DESC
			LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
		');

		if ($query->num_rows() == 0)
			return array();

		$boards = new Board();
		$boards->get();

		$selects = array();
		foreach ($query->result() as $key => $item)
		{
			foreach ($boards->all as $board)
			{
				if ($board->id == $item->board)
				{
					$selects[] = '
					(
						SELECT *, CONCAT('.$this->db->escape($board->shortname).') AS shortname
						FROM ' . $this->get_table($board->shortname) . '
						LEFT JOIN
							(
								SELECT id as report_id, post as report_post, reason as report_reason, status as report_status, created as report_created
								FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
								WHERE `id` = ' . $item->id . '
							) as q
							ON
							' . $this->get_table($board->shortname) . '.`doc_id`
							=
							' . $this->db->protect_identifiers('q') . '.`report_post`
						LEFT JOIN
							(
								SELECT id AS poster_id_join,
									ip AS poster_ip, user_agent AS poster_user_agent,
									banned AS poster_banned, banned_reason AS poster_banned_reason,
									banned_start AS poster_banned_start, banned_end AS poster_banned_end
								FROM'.$this->db->protect_identifiers('posters', TRUE).'
							) as p
							ON ' . $this->get_table($board->shortname) . '.`poster_id`
							=
							' . $this->db->protect_identifiers('p') . '.`poster_id_join`
						WHERE doc_id = ' . $this->db->escape($item->post) . '
						LIMIT 0, 1
					)';
				}
			}
		}

		$sql = implode(' UNION ', $selects);
		$query = $this->db->query($sql);

		return $query->result();
	}


	function get_image_href($data, $thumbnail = FALSE)
	{
		if (!$data->preview)
			return FALSE;

		if ($data->parent > 0)
			$number = $data->parent;
		else
			$number = $data->num;
		while (strlen((string) $number) < 9)
		{
			$number = '0' . $number;
		}

		if (file_exists($this->get_image_dir($data, $thumbnail)) !== FALSE)
		{
			if ($data->preview_h == 0 && $data->preview_w == 0)
			{
				$data->preview_h = 126;
				$data->preview_w = 126;
			}
			return (get_setting('fs_fuuka_boards_url') ? get_setting('fs_fuuka_boards_url') : site_url() . FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $data->shortname . '/' . (($thumbnail) ? 'thumb' : 'img') . '/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . (($thumbnail) ? $data->preview : $data->media_filename);
		}
		if ($thumbnail)
		{
			$data->preview_h = 150;
			$data->preview_w = 150;
			return site_url() . 'content/themes/default/images/image_missing.jpg';
		}
		else
		{
			return '';
		}
	}


	function get_image_dir($data, $thumbnail = FALSE)
	{
		if (!$data->preview)
			return FALSE;

		if ($data->parent > 0)
			$number = $data->parent;
		else
			$number = $data->num;

		while (strlen((string) $number) < 9)
		{
			$number = '0' . $number;
		}

		return ((get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory') : FOOLFUUKA_BOARDS_DIRECTORY)) . '/' . $data->shortname . '/' . (($thumbnail === TRUE) ? 'thumb' : 'img') . '/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . (($thumbnail === TRUE) ? $data->preview : $data->media_filename);
	}
}
