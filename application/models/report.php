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


	public function add($data = array())
	{
		if (!$this->update_report_db($data))
		{
			log_message('error', 'add_report: failed writing to database');
			return FALSE;
		}

		return TRUE;
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
		$CI = & get_instance();
		$CI->load->model('post');

		$reports = $this->get_paged($page, $per_page);
		$boards = new Board();
		$boards->get();

		if ($reports->paged->total_rows == 0)
		{
			return $reports;
		}

		// generate $board => [doc_id, doc_id]
		$multi_posts = array();
		foreach ($reports->all as $report)
		{
			foreach ($boards->all as $board)
			{
				if ($board->id == $report->board)
				{
					$multi_posts[$board->id][] = $report->post;
				}
			}
		}

		// generate [board_id, array(doc_id, doc_id)]
		$posts = array();
		foreach ($multi_posts as $key => $doc_id)
		{
			$posts[]  = array('board_id' => $key, 'doc_id' => $doc_id);
		}

		$results = $CI->post->get_multi_posts($posts);
		if (!empty($results))
		{
			$reports->all = $results;
		}
		return $reports;
	}


	public function process_report($id = 0, $options = array())
	{
		// report, [action, value]
		if (empty($options) || !isset($options['action']))
		{
			log_message('error', 'process_report: invalid call');
			return FALSE;
		}

		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
			WHERE id = ?
			LIMIT 0,1
		', array($id));

		if ($query->num_rows() == 0)
		{
			log_message('error', 'process_report: failed to process, report does not exist');
			return FALSE;
		}

		// set result
		$report = $query->row();

		$CI = & get_instance();
		$CI->load->model('post');

		// get single post
		$post = $CI->post->get_multi_posts(
			array(
				array(
					'board_id' => $report->board,
					'doc_id' => array(
						$report->post
					)
				)
			)
		);
		$post = $post[0];

		if (empty($post))
		{
			log_message('error', 'process_report: failed to process, post does not exist');
			$this->db->delete('reports', array('id' => $report->id));
			return FALSE;
		}

		switch ($options['action'])
		{
			case('ban'):

				if ($post['post']->poster_ip == "")
				{
					$this->db->delete('reports', array('id' => $report->id));
					log_message('error', 'process_report: failed to ban ip address specified');
					return array('error' => TRUE, 'message' => sprintf('Failed to ban the IP %s.', $post['post']->poster_ip));
				}

				$this->db->update(
					'posters',
					array(
						'banned' => 1,
						'banned_reason' => $options['value']['banned_reason'],
						'banned_start' => $options['value']['banned_start'],
						'banned_end' => $options['value']['banned_end']
					),
					array(
						'id' => $post['post']->poster_id
					)
				);
				$this->db->delete('reports', array('id' => $report->id));
				return array('success' => TRUE, 'message' => sprintf('The IP %s has been banned from posting.', $post['post']->poster_ip));

				break;

			case('delete'):

				if (!isset($options['value']['delete']))
				{
					log_message('error', 'process_report: invalid delete type');
					return array('error' => TRUE, 'message' => 'Invalid Operation.');
				}

				$result = $CI->post->delete(
					$post['board'],
					array(
						'post' => $post['post']->doc_id,
						'password' => $post['post']->delpass,
						'type' => $options['value']['delete']
					)
				);
				if (isset($result['error']))
				{
					log_message('error', 'process_report: failed to delete the reported post or image');
					return array('error' => TRUE, 'message' => 'Failed to remove the reported post or image from the database.');
				}
				return array('success' => TRUE, 'message' => 'The reported post or image has been removed from the database.');

				break;

			case('spam'):

				$result = $CI->post->spam($post['board'], $post['post']->doc_id);
				if (isset($result['error']))
				{
					log_message('error', 'process_report: failed to mark the reported post or image as spam');
					return array('error' => TRUE, 'message' => 'Failed to flag the reported post or image as spam.');
				}
				return array('success' => TRUE, 'message' => 'The reported post or image has been flagged as spam in the database.');

				break;

			default:

				return array('error' => TRUE, 'message' => 'Invalid Operation.');
		}

		return array('error' => TRUE, 'message' => 'Invalid Operation.');
	}


}
