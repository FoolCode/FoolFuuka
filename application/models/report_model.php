<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * FoOlFuuka Reports Model
 *
 * The Reports Model manages the reports linked to the board posts
 *
 * @package        	FoOlFrame
 * @subpackage    	FoOlFuuka
 * @category    	Models
 * @author        	FoOlRulez
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Report_model extends CI_Model
{

	/**
	 * Nothing special here 
	 */
	function __construct()
	{
		parent::__construct(NULL);
	}

	
	/**
	 * The functions with 'p_' prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	public function __call($name, $parameters)
	{
		$before = $this->plugins->run_hook('fu_report_model_before_' . $name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the 
		// replaced function wont' be run
		$replace = $this->plugins->run_hook('fu_report_model_replace_' . $name, $parameters, array($parameters));

		if ($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			switch (count($parameters))
			{
				case 0:
					$return = $this->{'p_' . $name}();
					break;
				case 1:
					$return = $this->{'p_' . $name}($parameters[0]);
					break;
				case 2:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1]);
					break;
				case 3:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2]);
					break;
				case 4:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
					break;
				case 5:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
					break;
				default:
					$return = call_user_func_array(array(&$this, 'p_' . $name), $parameters);
					break;
			}
		}

		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = $this->plugins->run_hook('fu_report_model_after_' . $name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}
	

	/**
	 * The structure of a report
	 * 
	 * @return array the structure 
	 */
	private function p_structure()
	{
		return array(
			'open' => array(
				'type' => 'open',
			),
			'board' => array(
				'label' => __('Board shortname'),
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
						'error' => __('Couldn\'t find the board with the submitted shortname.'),
						'critical' => TRUE
					);
				}
			),
			'board_id' => array(
				'type' => 'hidden',
				'label' => __('Board ID'),
				'database' => TRUE,
				'validation' => 'is_int',
				'validation_func' => function($input, $form_internal)
				{
					if (!isset($input['board_id']))
					{
						return array(
							'error_code' => 'BOARD_ID_NOT_SENT',
							'error' => __('You didn\'t send a board ID.'),
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
								'error' => __('Couldn\'t find the board with the submitted ID.'),
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
				'label' => __('Post number'),
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
							'error' => __('Couldn\'t find the post with the submitted post number.'),
							'critical' => TRUE
						);
					}
					
					return array('success' => TRUE, 'push' => array('doc_id' => $post->doc_id));
				}
			),
			'doc_id' => array(
				'type' => 'hidden',
				'label' => __('Post ID'),
				'database' => TRUE,
				'validation' => 'is_int',
				'validation_func' => function($input, $form_internal)
				{
					if(!isset($input['doc_id']))
					{
						return array(
							'error_code' => 'POST_ID_NOT_SENT',
							'error' => __('You didn\'t send a post ID.'),
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
							'error' => __('Couldn\'t find the post with the submitted doc_id.'),
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
				'label' => __('Reason'),
				'database' => TRUE,
				'validation' => 'trim|max_length[512]'
			),
			'separator-2' => array(
				'type' => 'separator-short'
			),
			'submit' => array(
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => __('Submit')
			),
			'close' => array(
				'type' => 'close'
			),
		);
	}
	
	
	/**
	 * Get reports by page
	 * 
	 * @param int $page the page
	 * @return array the rows fetched
	 */
	private function p_get_reports($page)
	{
		$query = $this->db->limit(25, $page * 25 - 25)->get('reports');
		if($query->num_rows == 0)
		{
			return array();
		}
		
		return $query->result();
	}

	
	/**
	 * Return the count of the reports
	 * 
	 * @return int the count of the reports
	 */
	private function p_get_count()
	{
		$query = $this->db->select('COUNT(*) AS count')->get('reports');
		
		if($query->num_rows == 0)
		{
			return 0;
		}
		
		$row = $query->row();
		
		return $row->count;
	}

	
	/**
	 * Add a report to a post
	 * 
	 * @param array $data the data to validate against the structure
	 * @return array the result from form_validation 
	 */
	private function p_add($data = array())
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


	/**
	 * Remove the report by ID
	 * 
	 * @param int $id the ID of the report
	 */
	private function p_remove($id)
	{
		$this->db->where('id', $id)->delete('reports');
	}


	/**
	 * Remove report by doc_id, also multiple reports in case
	 * 
	 * @param object $board the board object
	 * @param int $doc_id the doc_id
	 */
	private function p_remove_by_doc_id($board, $doc_id)
	{
		$this->db->where('board_id', $board->id)
			->where('doc_id', $doc_id)->delete('reports');
	}

}

/* End of file report_model.php */
/* Location: ./application/models/report_model.php */