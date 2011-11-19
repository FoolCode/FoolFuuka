<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Board extends DataMapper {

	var $has_one = array();
	var $has_many = array();
	var $validation = array(
		'postid' => array(
			'rules' => array('required', 'is_int'),
			'label' => 'Post ID',
			'type' => 'input'
		),
		'reason' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Reason',
			'type' => 'input'
		)
	);
	
	
	function __construct($id = NULL)
	{		
		parent::__construct($id);
	}

	
	function post_model_init($from_cache = FALSE)
	{
		
	}
	
	
	public function add_report($data = array())
	{
		if (!$this->update_report_db($data))
		{
			log_message('error', 'add_report: failed writing to database');
			return false;
		}
		
		return true;
	}
	
	
	public function del_report()
	{
		if (!$this->remove_report_db($data))
		{
			log_message('error', 'remove_report: failed to remove database entry');
			return false;
		}
		
		return true;
	}
	
	
	public function process_report($data)
	{
		if (!isset($data["id"]) && $data["id"] == '')
		{
			log_message('error', 'process_report: failed to process report completely');
			return false;
		}
		
		// update_report_db with approval/rejection/etc.
		// move thumbnail if needed. temporary structure.
		
		if ($data["action"] == 'spam' && $data["thumbnail"] = TRUE)
		{
			if (!$this->move_thumbnail($source, $destination))
			{
				log_message('error', 'process_report: failed to move thumbnail to spam folder');
				return false;
			}
		}
		return true;
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
				return false;
			}
		}
		
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
			return false;
		}
		
		return true;
	}
	
	
	public function remove_report_db()
	{
		if (!$this->delete())
		{
			set_notice('error', 'This report couldn\'t be removed from the database for unknown reasons.');
			log_message('error', 'remove_report_db: failed to removed requested id');
			return false;
		}
		
		return true;
	}
	
	
	public function move_thumbnail($source, $destination)
	{
		if (!rename($source, $destination))
		{
			set_notice('error', 'This thumbnail could not be moved. Please check your file permissions.');
			log_message('error', 'move_thumbnail: failed to move thumbnail');
			return false;
		}
		return true;
	}
}
