<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Poster_model extends CI_Model
{


	function __construct()
	{
		parent::__construct(NULL);
	}
	
	
	/**
	 * This bans people
	 * 
	 * @param int $decimal_ip
	 * @param int $length_in_hours
	 * @param String $reason 
	 */
	function ban($decimal_ip, $length_in_hours = NULL, $reason = NULL)
	{
		$query = $this->db->where('ip', $decimal_ip)->get('posters');
		
		if($query->num_rows() == 0)
		{
			$this->db->insert('posters', array(
				'ip' => $decimal_ip,
				'banned' => 1,
				'banned_start' => date('Y-m-d H:i:s'),
				'banned_length' => $length_in_hours,
				'banned_reason' => $reason
			));
		}
		else
		{
			$this->db->where('ip', $decimal_ip)->update('posters', array(
				'banned' => 1,
				'banned_start' => date('Y-m-d H:i:s'),
				'banned_length' => $length_in_hours,
				'banned_reason' => $reason
			));
		}
	}
	
	function unban($decimal_ip)
	{
		$this->db->where('ip', $decimal_ip)->get('posters');
		
		if($this->num_rows() == 1)
		{
			$this->db->where('ip', $decimal_ip)->update('posters', array(
				'banned' => 0
			));
		}
	}
	
	/**
	 * Checks if a person is banned. Returns the poster object if banned, else FALSE
	 * 
	 * @param type $decimal_ip
	 * @return boolean 
	 */
	function is_banned($decimal_ip)
	{
		$query = $this->db->where('ip', $decimal_ip)->get('posters');
		
		if($query->num_rows() > 0)
		{
			$row = $query->row();
			
			if($row->banned && (
				strtotime($row->banned_start) + ($row->banned_length * 60 * 60) < time()
			))
			{
				return $row;
			}
		}
		
		return FALSE;
	}

}