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


	/*
	 * Index redirects to the own page
	 * 
	 * @author Woxxy
	 */
	function index()
	{
		redirect('/admin/members/members');
	}


	/*
	 * Lists registered members, and supports search via POST
	 * 
	 * membersa instead of members because clash with class name. routes fix this
	 * 
	 * @author Woxxy
	 */
	function membersa($page = 1)
	{

	}


	/*
	 * shows the data of a member, and allows admins and mods to change it
	 */
	function member($id)
	{
		
	}




}