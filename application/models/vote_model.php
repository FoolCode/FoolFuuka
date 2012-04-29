<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Vote_model extends CI_Model
{


	function __construct()
	{
		parent::__construct();
	}
	
	
	function add($board, $doc_id, $vote = 1)
	{
		$post = $this->post->get_by_doc_id($board, $doc_id);
		
		if(!$post)
		{
			return array('error' => _('Post not found.'));
		}
		
		$query = $this->db->where('ip', inet_ptod($this->input->ip_address()))
			->where('doc_id', $doc_id)->where('board_id', $board->id)
			->get('votes');
		
		if($query->num_rows() != 0)
		{
			$result = $query->row();
			
			if($result->vote != $vote)
			{
				$this->db->where('ip', inet_ptod($this->input->ip_address()))
					->where('doc_id', $doc_id)->where('board_id', $board->id)
					->update('votes', array('vote' => $vote));
				
				return array('success' => TRUE);
			}
			
			// if it was clicked before remove the vote
			$this->db->where('ip', inet_ptod($this->input->ip_address()))
					->where('doc_id', $doc_id)->where('board_id', $board->id)
					->delete('votes');
			
			return array('success' => TRUE);
		}
		
		$this->db->insert('votes', array(
			'vote' => $vote,
			'ip' => inet_ptod($this->input->ip_address()),
			'doc_id' => $doc_id,
			'board_id' => $board->id
		));
		
		return array('success' => TRUE);
	}
	
	
	function count($board, $doc_id)
	{
		$query = $this->db->select('SUM(vote)')
			->where('doc_id', $doc_id)
			->where('board_id', $board->id)
			->get('votes');
		
		$result = $query->row_array();
		if(is_null($result['SUM(vote)']))
		{
			return 0;
		}
		return $result['SUM(vote)'];
	}
	
}