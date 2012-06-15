<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Members extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();
		// members are editable by mods!
		// only admins can change levels though
		$this->auth->is_allowed() or redirect('admin');
		$this->viewdata['controller_title'] = '<a href="' . site_url("admin/members") . '">' . __("Members") . '</a>';
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
		$this->load->model('member_model', 'member');
		$data['users'] = $this->member->get_all_with_profile();
		$this->viewdata['function_title'] = __('Manage');
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
		$this->load->model('member_model', 'member');
		$this->load->model('profile_model', 'profile');
		$data['form'] = $this->profile->structure();
		
		if($this->input->post())
		{
			$this->load->library('form_validation');
			$result = $this->form_validation->form_validate($data['form']);
			if (isset($result['error']))
			{
				set_notice('warning', $result['error']);
			}
			else
			{
				// it's actually fully checked, we just have to throw it in DB
				$this->profile->save($result['success']);
				set_notice('success', __('User profile saved!'));
			}

		}
		
		$data['object'] = $this->member->get($id);
		if($data['object'] == FALSE)
		{
			show_404();
		}

		$this->viewdata['function_title'] = __('Editing user:') . ' ' . $data['object']->username;

		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator.php",
			$data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}




}