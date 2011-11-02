<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Chan extends Public_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('pagination');
		$this->load->library('template');
		$this->template->set_layout('chan');
	}


	/*
	 * Show the boards
	 */
	public function index()
	{
		
	}
	
	public function board()
	{
		
	}
	
	public function thread($id)
	{
		
	}
	
	public function post($£id)
	{
		
	}
	
	public function ghost($page = 1)
	{
		
	}
	
	public function _remap($method, $params = array())
	{
		if (method_exists($this->RC, $method))
		{
			return call_user_func_array(array($this->RC, $method), $params);
		}
		
		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}
	
}