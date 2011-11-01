<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Index extends Account_Controller
{
	function __construct()
	{
		parent::__construct();
		if (!$this->tank_auth->is_logged_in())
			redirect('/account/auth/login/');
		if ($this->uri->segment(2) == 'index')
			redirect('/account/' . $this->uri->segment(3));
		$this->load->library('form_validation');
		$this->_navbar();
	}


	function _navbar()
	{
		$echo = "";
		$array = array(
			'profile' => _('Profile'),
			'teams' => _('Teams'),
		);

		foreach ($array as $key => $item)
		{
			$echo .= '<a href="' . site_url('/account/' . $key . '/') . '"';
			if ($this->uri->segment(2) == $key || ( $this->uri->segment(2) == FALSE && $key == "profile"))
				$echo .= ' class="active" ';
			$echo .= '>' . $item . '</a>';
		}
		$this->viewdata["navbar"] = $echo;
	}


	function index()
	{
		redirect('/account/profile/');
	}


	function profile()
	{
		// get the data to save. low on security because the user can only save to himself from here
		if ($this->input->post())
		{
			$this->form_validation->set_rules('display_name', _('Display Name'), 'trim|max_length[30]|xss_clean');
			$this->form_validation->set_rules('twitter', _('Twitter username'), 'trim|max_length[20]|xss_clean');
			$this->form_validation->set_rules('bio', _('Bio'), 'trim|max_length[140]|xss_clean');

			if ($this->form_validation->run())
			{
				$profile = new Profile($this->tank_auth->get_user_id());
				// use the from_array to be sure what's being inputted
				$profile->display_name = $this->form_validation->set_value('display_name');
				$profile->twitter = $this->form_validation->set_value('twitter');
				$profile->bio = $this->form_validation->set_value('bio');
				if ($profile->save())
				{
					$data["saved"] = TRUE;
				}
			}
		}
		$user = new User($this->tank_auth->get_user_id());
		$profile = new Profile($this->tank_auth->get_user_id());

		$data["user_id"] = $user->id;
		$data["user_name"] = $user->username;
		$data["user_email"] = $user->email;
		$data["user_display_name"] = $profile->display_name;
		$data["user_twitter"] = $profile->twitter;
		$data["user_bio"] = $profile->bio;

		$this->viewdata["function_title"] = _("Your profile");
		$this->viewdata["main_content_view"] = $this->load->view('account/profile/profile', $data, TRUE);
		$this->load->view("account/default.php", $this->viewdata);
	}


	function teams()
	{
		if ($this->input->post('action'))
		{
			if ($this->input->post('action') == 'apply_with_team_name')
			{
				if (($error = $this->_apply()) !== TRUE)
					$data["errors"]['team_name'] = $error;
			}
		}
		// this is a datamapper object
		$teams = $this->tank_auth->is_team();
		$data["teams"] = $teams ? $teams->all_to_array(array('name', 'stub')) : array();

		$teams_leaded = $this->tank_auth->is_team_leader();
		$data["teams_leaded"] = $teams_leaded ? $teams_leaded->all_to_array(array('name', 'stub')) : array();

		$members = new Membership();
		$members->get_applicants();
		$data["requests"] = $members;
		
		$members = new Membership();
		$members->get_applications();
		$data["applications"] = $members;
		$data["user_id"] = $this->tank_auth->get_user_id();

		$this->viewdata["function_title"] = _("Your teams");
		$this->viewdata["main_content_view"] = $this->load->view('account/profile/teams', $data, TRUE);
		$this->load->view("account/default.php", $this->viewdata);
	}


	/**
	 * Allows a team leader or user to accept applications
	 * 
	 * @author Woxxy
	 */
	function _apply()
	{
		$this->form_validation->set_rules('team_name', _('Team name'), 'required|trim|xss_clean');
		if ($this->form_validation->run())
		{
			$team = new Team();
			$team->where('name', $this->form_validation->set_value('team_name'))->get();
			if ($team->result_count() != 1)
			{
				return _("Team does not exist");
			}
			$member = new Membership();
			if (!$member->apply($team->id))
			{
				return show_404();
			}
			return TRUE;
		}
	}


	function request($team_stub, $user_id)
	{
		$this->viewdata["navbar"] = "";
		$team = new Team();
		$team->where('stub', $team_stub)->get();
		$user = new User($user_id);
		if ($team->result_count() != 1 || $user->result_count() != 1)
		{
			show_404();
		}

		if ($this->input->post('action'))
		{
			$member = new Membership();

			if ($this->input->post('action') == 'accept')
			{
				if (!$member->accept_application($team->id, $user->id))
				{
					return show_404();
				}
			}

			if ($this->input->post('action') == 'reject')
			{
				if (!$member->reject_application($team->id, $user->id))
				{
					return show_404();
				}
			}

			redirect('/account/teams/');
		}

		$this->viewdata["function_title"] = _("Accept request");
		$data["team_name"] = $team->name;
		$data["user_name"] = $user->username;
		$data["show_accept"] = $this->tank_auth->is_team_leader($team->id);
		$this->viewdata["main_content_view"] = $this->load->view('account/profile/request', $data, TRUE);
		$this->load->view("account/default.php", $this->viewdata);
	}


	function leave_team($team_stub)
	{
		$this->viewdata["navbar"] = "";
		$team = new Team();
		$team->where('stub', $team_stub)->get();
		if ($team->result_count() != 1)
		{
			show_404();
		}

		if ($this->input->post())
		{
			$member = new Membership();
			if (!$member->reject_application($team->id))
			{
				return show_404();
			}
			redirect('/account/teams/');
		}

		$this->viewdata["function_title"] = _("Leave team");
		$data["team_name"] = $team->name;
		$data["team_id"] = $team->id;
		$this->viewdata["main_content_view"] = $this->load->view('account/profile/leave_team', $data, TRUE);
		$this->load->view("account/default.php", $this->viewdata);
	}


	function leave_leadership($team_stub)
	{
		$this->viewdata["navbar"] = "";
		$team = new Team();
		$team->where('stub', $team_stub)->get();
		if ($team->result_count() != 1)
		{
			show_404();
		}

		if (!$this->tank_auth->is_team_leader($team->id) && !$this->tank_auth->is_allowed())
		{
			show_404();
		}


		if ($this->input->post())
		{
			$member = new Membership();
			if (!$member->remove_team_leader($team->id))
			{
				return show_404();
			}
			redirect('/account/teams/');
		}

		$this->viewdata["function_title"] = _("Leave team leadership");
		$data["team_name"] = $team->name;
		$data["team_id"] = $team->id;
		$this->viewdata["main_content_view"] = $this->load->view('account/profile/leave_leadership', $data, TRUE);
		$this->load->view("account/default.php", $this->viewdata);
	}


}

/* End of file index.php */
/* Location: ./application/controllers/admin/index.php */