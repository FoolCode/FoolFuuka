<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Report extends CI_Model
{


	function __construct()
	{
		parent::__construct(NULL);
	}


	function structure()
	{
		return array(
			'open' => array(
				'type' => 'open',
			),
			'board' => array(
				'label' => _('Board shortname'),
				'validation_func' => function($input, $form_internal)
				{
					// no need to check if isset, we wouldn't be here if
					// there wasn't 'board' POSTed
					$CI = & get_instance();
					$board = $CI->radix->get_by_shortname($input['board']);
					if ($board !== FALSE)
					{
						// let's send forward the object, this is internal stuff
						return array('success' => TRUE, 'push' => array('board_id' => $board));
					}

					return array(
						'error_code' => 'BOARD_SHORTNAME_NOT_FOUND',
						'error' => _('Couldn\'t find the board with the submitted shortname.'),
						'critical' => TRUE
					);
				}
			),
			'board_id' => array(
				'type' => 'hidden',
				'label' => _('Board ID'),
				'database' => TRUE,
				'validation' => 'is_int',
				'validation_func' => function($input, $form_internal)
				{
					if (!isset($input['board_id']))
					{
						return array(
							'error_code' => 'BOARD_ID_NOT_SENT',
							'error' => _('You didn\'t send a board ID.'),
							'critical' => TRUE
						);
					}

					// check that the ID exists
					if (!is_object($input['board_id']))
					{
						$CI = & get_instance();
						$board = $CI->radix->get_by_id($input['board_id']);
						if ($board == FALSE)
						{
							return array(
								'error_code' => 'BOARD_ID_NOT_FOUND',
								'error' => _('Couldn\'t find the board with the submitted ID.'),
								'critical' => TRUE
							);
						}
					}

					return array('success' => TRUE, 'push' => array('board_id' => $board->id));
				}
			),
			'post' => array(
				'type' => 'hidden',
				'validation' => 'trim',
				'label' => _('Post number'),
				'validation_func' => function($input, $form_internal)
				{
					// check that the doc_id of the post exists
					$CI = & get_instance();
					$board = $CI->radix->get_by_id($input['board_id']);
					$post = $CI->post->get_by_num($board, $input['post']);
					
					if ($post === FALSE)
					{
						return array(
							'error_code' => 'POST_NUM_NOT_FOUND',
							'error' => _('Couldn\'t find the post with the submitted post number.'),
							'critical' => TRUE
						);
					}
					
					return array('success' => TRUE, 'push' => array('doc_id' => $post->doc_id));
				}
			),
			'doc_id' => array(
				'type' => 'hidden',
				'label' => _('Post ID'),
				'database' => TRUE,
				'validation' => 'is_int',
				'validation_func' => function($input, $form_internal)
				{
					if(!isset($input['doc_id']))
					{
						return array(
							'error_code' => 'POST_ID_NOT_SENT',
							'error' => _('You didn\'t send a post ID.'),
							'critical' => TRUE
						);
					}
				
					// check that the doc_id of the post exists
					$CI = & get_instance();
					$board = $CI->radix->get_by_id($input['board_id']);
					$post = $CI->post->get_by_doc_id($board, $input['doc_id']);
					if ($post === FALSE)
					{
						return array(
							'error_code' => 'POST_DOC_ID_NOT_FOUND',
							'error' => _('Couldn\'t find the post with the submitted doc_id.'),
							'critical' => TRUE
						);
					}

					return array('success' => TRUE);
				}
			),
			'ip_reporter' => array(
				'type' => 'hidden',
				'database' => TRUE,
			),
			'reason' => array(
				'type' => 'textarea',
				'label' => _('Reason'),
				'database' => TRUE,
				'validation' => 'trim|max_length[512]'
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
	
	
	function get_reports($page)
	{
		$query = $this->db->limit(25, $page * 25 - 25)->get('reports');
		if($query->num_rows == 0)
		{
			return array();
		}
		
		return $query->result();
	}

	function get_count()
	{
		$query = $this->db->select('COUNT(*) AS count')->get('reports');
		
		if($query->num_rows == 0)
		{
			return 0;
		}
		
		$row = $query->row();
		
		return $row->count;
	}

	function add($data = array())
	{
		// if it's set we want to know the IP address of the reporter
		if($this->input->ip_address())
			$data['ip_reporter'] = inet_ptod($this->input->ip_address());
		
		$this->load->library('form_validation');
		$result = $this->form_validation->form_validate($this->structure(), $data);

		if (isset($result['error']))
		{
			return $result;
		}

		if (isset($result['success']))
		{
			$this->db->insert('reports', $result['success']);
			return $result;
		}
	}


	function remove($id)
	{
		$this->db->where('id', $id)->delete('reports');
	}


	function remove_by_doc_id($board, $doc_id)
	{
		$this->db->where('board_id', $board->id)
			->where('doc_id', $doc_id)->delete('reports');
	}

}