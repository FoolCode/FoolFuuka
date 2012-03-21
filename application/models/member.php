<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Member extends CI_Model
{


	function __construct($id = NULL)
	{
		parent::__construct();
	}
	
	
	function get_all()
	{
		$query = $this->db->get('users');
		
		if($query->num_rows() == 0)
		{
			return array();
		}
		
		return $query->result();
	}
	
	
	function get_all_with_profile()
	{
		$this->db->from('users');
		$this->db->join('profiles', 'users.id = profiles.user_id');
		$query = $this->db->get();
		
		if($query->num_rows() == 0)
		{
			return array();
		}
		
		return $query->result();
	}
	
	function get($id)
	{
		// this actually calls members
		$query = $this->db->where('users.id', $id)
			->join('profiles', 'users.id = profiles.user_id')->get('users');
		
		if($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		return $query->row();
	}
	
	
}