<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class API_Controller extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		header("Access-Control-Allow-Origin: http://board3.foolz.us");
		header('Access-Control-Allow-Credentials: true');	
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Max-Age: 604800');
	}
	
	/*
	 * Commodity to check that the ID is not wrong and return a coherent error
	 * 
	 * @author Woxxy
	 */
	function check_board()
	{
		if (!$this->get('board') && !$this->post('board'))
		{
			$this->response(array('error' => _('You didn\'t select a board')), 404);
		}
			
		$board = ($this->get('board'))?$this->get('board'):$this->post('board');

			
		if(!$this->radix->set_selected_by_shortname($board))
		{
			$this->response(array('error' => _('The board you selected doesn\'t exist')), 404);
		}

		$this->load->model('post');
	}
	
}