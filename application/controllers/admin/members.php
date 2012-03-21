<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Members extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->viewdata['controller_title'] = '<a href="' . site_url("admin/members") . '">' . _("Members") . '</a>';
	}


	/**
	 * Index redirects to the list page
	 * 
	 * @author Woxxy
	 */
	function index()
	{
		redirect('/admin/members/members');
	}


	/**
	 * Lists registered member
	 * 
	 * "membersa" instead of "members" because clash with class name. routes fix this
	 * 
	 * @author Woxxy
	 */
	function membersa()
	{
		$this->load->model('member');
		$data['users'] = $this->member->get_all_with_profile();
		
		$this->viewdata["main_content_view"] = $this->load->view("admin/members/manage.php",
			$data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	/**
	 * shows the data of a member, and allows admins and mods to change it
	 * or the user himself if the 
	 */
	function member($id)
	{
		
	}




}