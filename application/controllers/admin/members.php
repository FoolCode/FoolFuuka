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

		// prepare a variable to decide who can edit
		if ($this->tank_auth->is_admin() || $this->tank_auth->is_group('mod'))
			$can_edit = true;
		else
			$can_edit = false;

		// set the subtitle
		$this->viewdata["function_title"] = _('Members');

		$users = new User();

		// support filtering via search
		if ($this->input->post())
		{
			$users->ilike('username', $this->input->post('search'));
			$this->viewdata['extra_title'][] = _('Searching') . " : " . $this->input->post('search');
		}

		// page results
		$users->get_paged($page, 20);

		$form = array();

		// prepare the array to print out as a form
		foreach ($users->all as $key => $item)
		{
			$form[$key][] = '<a href="' . site_url('/admin/members/member/' . $item->id) . '">' . $item->username . '</a>';
			// if true allow seeing the email
			if ($can_edit)
				$form[$key][] = $item->email;
			$form[$key][] = $item->last_login;
		}

		// create the form off the array
		$data['form_title'] = _('Members');
		$data['table'] = tabler($form, TRUE, FALSE);

		// print out
		$this->viewdata["main_content_view"] = $this->load->view('admin/members/users', $data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}


	/*
	 * shows the data of a member, and allows admins and mods to change it
	 */
	function member($id)
	{
		// don't troll us with other than numbers as ID, throw 404 in case
		if (!is_numeric($id))
			show_404();

		// if the user doesn't exist throw 404
		$user = new User($id);
		if ($user->result_count() != 1)
			show_404();

		// if the user is clicking on himself, send him to the you page.
		// the you method sends back here, so the user will still see the rest.
		// the second part of the if makes sure that if "member" method is called from "you"
		// the user is not redirected to "you" again
		if ($this->tank_auth->get_user_id() == $id && $this->uri->segment(3) != 'you')
			redirect('/account/profile/');

		// give admins and mods ability to edit user profiles
		if ($this->input->post() && $this->tank_auth->is_allowed())
		{
			$profile = new Profile($id);
			if ($profile->result_count() == 1)
				$profile->from_array($this->input->post(), array('display_name', 'twitter', 'bio'), TRUE);
		}

		// set the subtitle
		$this->viewdata["function_title"] = '<a href="' . site_url("admin/members") . '">' . _('Members') . '</a>';

		// create a table with user login name and email
		$table = ormer($user);
		$table = tabler($table, TRUE, FALSE);
		$data['table'] = $table;

		// let's give the user object to the view
		$data['user'] = $user;

		// grab the profile and put it in a table
		$profile = new Profile();
		$profile->where('user_id', $id)->get();
		$profile_table = ormer($profile);
		$data['profile'] = tabler($profile_table, TRUE, ($this->tank_auth->is_allowed() || $this->uri->segment(3) != 'you'));

		$this->viewdata["extra_title"][] = $user->username;
		// print out
		$this->viewdata["main_content_view"] = $this->load->view('admin/members/user', $data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}


	/*
	 * Shows the list of teams as well as showing the single team
	 * 
	 * @author Woxxy
	 */
	function teams($stub = "")
	{
		// no team selected
		if ($stub == "")
		{
			// set subtitle
			$this->viewdata["function_title"] = _('Teams');

			// we can use get_iterated on teams
			$teams = new Team();

			// support filtering via search
			if ($this->input->post())
			{
				$teams->ilike('name', $this->input->post('search'));
				$this->viewdata['extra_title'][] = _('Searching') . " : " . $this->input->post('search');
			}

			$teams->order_by('name', 'ASC')->get_iterated();
			$rows = array();
			// produce links for each team
			foreach ($teams as $team)
			{
				$rows[] = array('title' => '<a href="' . site_url('admin/members/teams/' . $team->stub) . '">' . $team->name . '</a>');
			}
			// put in a list the teams
			$data['form_title'] = _('Teams');
			$data['table'] = lister($rows);

			// print out
			$this->viewdata["main_content_view"] = $this->load->view('admin/members/users', $data, TRUE);
			$this->load->view("admin/default", $this->viewdata);
		}
		else
		{
			// team was selected, let's grab it and create a form for it
			$team = new Team();
			$team->where('stub', $stub)->get();

			// if the team was not found return 404
			if ($team->result_count() != 1)
				show_404();

			// if admin or mod allow full editing rights
			if ($this->tank_auth->is_allowed())
				$can_edit = true;
			else
				$can_edit = false;

			// if it's a team leader, but not admin or mod, allow him to change data but not the team name
			if ($this->tank_auth->is_team_leader($team->id) && !$can_edit)
				$can_edit_limited = true;
			else
				$can_edit_limited = false;

			// if allowed in any way to edit, 
			if (($post = $this->input->post()) && ($can_edit || $can_edit_limited))
			{
				$post["id"] = $team->id;

				// save the stub in case it's changed

				$old_stub = $team->stub;
				// don't allow editing of name for team leaders
				if ($can_edit_limited)
				{
					unset($post['name']);
				}

				// send the data to database
				$team->update_team($post);

				// green box to tell data is saved
				set_notice('notice', _('Saved.'));

				if ($team->stub != $old_stub)
				{
					flash_notice('notice', _('Saved.'));
					redirect('admin/members/teams/' . $team->stub);
				}
			}


			// subtitle
			$this->viewdata["function_title"] = '<a href="' . site_url("admin/members/teams") . '">' . _('Teams') . '</a>';
			// subsubtitle!
			$this->viewdata["extra_title"][] = $team->name;

			// gray out the name field for team leaders by editing directly the validation array
			if ($can_edit_limited)
				$team->validation['name']['disabled'] = 'true';

			// convert the team information to an array
			$result = ormer($team);

			// convert the array to a form
			$result = tabler($result, TRUE, ($can_edit || $can_edit_limited));
			$data['table'] = $result;
			$data['team'] = $team;

			// get the team's members
			$members = new Membership();
			$users = $members->get_members($team->id);

			// the team members' array needs lots of buttons and links
			$users_arr = array();
			foreach ($users->all as $key => $item)
			{
				$users_arr[$key][] = '<a href="' . site_url('/admin/members/member/' . $item->id) . '">' . $item->username . '</a>';

				// show the email only to admins and mods
				if ($can_edit)
					$users_arr[$key][] = $item->email;
				$users_arr[$key][] = $item->last_login;

				// leader of normal member?
				$users_arr[$key][] = ($item->is_leader) ? _('Leader') : _('Member');


				if ($this->tank_auth->is_team_leader($team->id) || $this->tank_auth->is_allowed())
				{
					$buttoner = array();
					$buttoner = array(
						'text' => _("Remove member"),
						'href' => site_url('/admin/members/reject_application/' . $team->id . '/' . $item->id),
						'plug' => _('Do you want to remove this team member?')
					);
				}

				// add button to array or stay silent if there's no button
				$users_arr[$key]['action'] = (isset($buttoner) && !empty($buttoner)) ? buttoner($buttoner) : '';
				if (!$item->is_leader && ($this->tank_auth->is_team_leader($team->id) || $this->tank_auth->is_allowed()))
				{
					$buttoner = array();
					$buttoner = array(
						'text' => _("Make leader"),
						'href' => site_url('/admin/members/make_team_leader/' . $team->id . '/' . $item->id),
						'plug' => _('Do you want to make this user a team leader?')
					);
				}
				if ($item->is_leader && ($this->tank_auth->is_team_leader($team->id) || $this->tank_auth->is_allowed()))
				{
					$buttoner = array();
					$buttoner = array(
						'text' => _("Remove leader"),
						'href' => site_url('/admin/members/remove_team_leader/' . $team->id . '/' . $item->id),
						'plug' => _('Do you want to remove this user from the team leadership?')
					);
				}
				// add button to array or stay silent if there's no button
				$users_arr[$key]['action'] .= (isset($buttoner) && !empty($buttoner)) ? buttoner($buttoner) : '';
			}

			// Spawn the form for adding a team leader
			$data["no_leader"] = FALSE;
			if ($this->tank_auth->is_allowed())
				$data["no_leader"] = TRUE;

			// make a form out of the array of members
			$data['members'] = tabler($users_arr, TRUE, FALSE);

			// print out
			$this->viewdata["main_content_view"] = $this->load->view('admin/members/team', $data, TRUE);
			$this->load->view("admin/default", $this->viewdata);
		}
	}


	/*
	 * Redirects to the right team
	 * 
	 * @author Woxxy
	 */
	function home_team()
	{
		$team = new Team();
		$team->where('name', get_setting('fs_gen_default_team'))->get();
		redirect('/admin/members/teams/' . $team->stub);
	}


	/*
	 * Form to add teams, admin and mod only
	 * 
	 * @author Woxxy
	 */
	function add_team()
	{
		// only admins and mods are allowed to create teams
		if (!$this->tank_auth->is_allowed())
		{
			show_404();
		}

		// save the data if POST
		if ($post = $this->input->post())
		{
			$team = new Team();
			$team->update_team($this->input->post());
			flash_notice('notice', 'Added the team ' . $team->name . '.');
			redirect('/admin/members/teams/' . $team->stub);
		}

		$team = new Team();

		// set title and subtitle
		$this->viewdata["function_title"] = '<a href="' . site_url("/admin/members/teams") . '">' . _('Teams') . '</a>';
		$this->viewdata["extra_title"][] = _('Add New');

		// transform the Datamapper array to a form
		$result = ormer($team);
		$result = tabler($result, FALSE, TRUE);
		$data['form_title'] = _('Add New Team');
		$data['table'] = $result;

		// print out
		$this->viewdata["main_content_view"] = $this->load->view('admin/form', $data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}


	/*
	 * Allows a team leader or user to accept applications
	 * 
	 * Ajax protected until CSRF is setup
	 * 
	 * @author Woxxy
	 */
	function accept_application($team_id, $user_id = NULL)
	{
		if (!isAjax())
		{
			return false;
		}

		$this->viewdata["function_title"] = _("Accepting into team...");
		$member = new Membership();
		if (!$member->accept_application($team_id, $user_id))
		{
			return FALSE;
		}
		flash_notice('notice', _('User accepted into the team.'));
		$team = new Team($team_id);
		$this->output->set_output(json_encode(array('href' => site_url('/admin/members/teams/' . $team->stub))));
	}


	/*
	 * Allows a team leader to reject applications
	 * 
	 * Ajax protected until CSRF is setup
	 * 
	 * @author Woxxy
	 */
	function reject_application($team_id, $user_id = NULL)
	{
		if (!isAjax())
		{
			return false;
		}

		$this->viewdata["function_title"] = _("Removing from team...");
		$member = new Membership();
		if (!$member->reject_application($team_id, $user_id))
		{
			return FALSE;
		}
		$user = new User($user_id);
		flash_notice('notice', sprintf(_('You have removed %s from the team.'), $user->username));
		$team = new Team($team_id);
		$this->output->set_output(json_encode(array('href' => site_url('/admin/members/teams/' . $team->stub))));
	}


	/*
	 * Allows an user to apply for a team
	 * 
	 * Ajax protected until CSRF is setup
	 * 
	 * @author Woxxy
	 */
	function make_team_leader($team_id, $user_id)
	{
		if (!isAjax())
		{
			return false;
		}
		if (!$this->tank_auth->is_team_leader($team_id) && !$this->tank_auth->is_allowed())
			return false;
		$this->viewdata["function_title"] = "Making team leader...";
		$member = new Membership();
		$member->make_team_leader($team_id, $user_id);
		$user = new User($user_id);
		flash_notice('notice', sprintf(_('You have upgrade %s to team leader.'), $user->username));
		$team = new Team($team_id);
		$this->output->set_output(json_encode(array('href' => site_url('/admin/members/teams/' . $team->stub))));
	}


	/*
	 * Allows a team leader, an admin or a mod to create a new leader for the selected team
	 * 
	 * This is NOT triggered via AJAX
	 * 
	 * @author Woxxy
	 */
	function make_team_leader_username($team_id)
	{
		if (!$this->tank_auth->is_team_leader($team_id) && !$this->tank_auth->is_allowed())
			return false;
		$team = new Team($team_id);
		$user = new User();
		$user->where('username', $this->input->post('username'))->get();
		if ($user->result_count() != 1)
		{
			flash_notice('error', _('User not found.'));
			redirect('/admin/members/teams/' . $team->stub);
		}
		$this->viewdata["function_title"] = "Making team leader...";
		$member = new Membership();
		$member->make_team_leader($team_id, $user->id);
		flash_notice('notice', sprintf(_('You have added %s to the team with the position of team leader.'), $user->username));
		redirect('/admin/members/teams/' . $team->stub);
	}


	/*
	 * Allows team leaders, admins and mods to remove leaders
	 * 
	 * Ajax protected until CSRF is setup
	 * 
	 * @author Woxxy
	 */
	function remove_team_leader($team_id, $user_id)
	{
		if (!isAjax())
		{
			return false;
		}
		if (!$this->tank_auth->is_team_leader($team_id) && !$this->tank_auth->is_allowed())
			return false;
		$this->viewdata["function_title"] = "Removing team leader...";
		$member = new Membership();
		$member->remove_team_leader($team_id, $user_id);
		$user = new User($user_id);
		flash_notice('notice', sprintf(_('You have stripped %s of their team leader position.'), $user->username));
		$team = new Team($team_id);
		$this->output->set_output(json_encode(array('href' => site_url('/admin/members/teams/' . $team->stub))));
	}


	/*
	 * Makes an user an admin, only admins can do this
	 * 
	 * Ajax protected until CSRF is setup
	 * 
	 * @author Woxxy
	 */
	function make_admin($user_id)
	{
		if (!isAjax())
		{
			return false;
		}
		if (!$this->tank_auth->is_admin())
			return false;
		$profile = new Profile();
		if ($profile->change_group($user_id, 1))
		{
			flash_notice('notice', _('You have added the user to the admin group.'));
			$this->output->set_output(json_encode(array('href' => site_url('/admin/members/member/' . $user_id))));
			return true;
		}
		return false;
	}


	/*
	 * Removes an admin, only admins can do this
	 * 
	 * Ajax protected until CSRF is setup
	 * 
	 * @author Woxxy
	 */
	function remove_admin($user_id)
	{
		if (!isAjax())
		{
			return false;
		}
		if (!$this->tank_auth->is_admin())
			return false;
		$profile = new Profile();
		if ($profile->change_group($user_id, 0))
		{
			flash_notice('notice', _('You have removed the user from the administrators group.'));
			$this->output->set_output(json_encode(array('href' => site_url('/admin/members/member/' . $user_id))));
			return true;
		}
		return false;
	}


	/*
	 * Gives mod powers, only admins can do this
	 * 
	 * Ajax protected until CSRF is setup
	 * 
	 * @author Woxxy
	 */
	function make_mod($user_id)
	{
		if (!isAjax())
		{
			return false;
		}
		if (!$this->tank_auth->is_admin())
			return false;
		$profile = new Profile();
		if ($profile->change_group($user_id, 3))
		{
			flash_notice('notice', _('You have added the user to the moderators group.'));
			$this->output->set_output(json_encode(array('href' => site_url('/admin/members/member/' . $user_id))));
			return true;
		}
		return false;
	}


	/*
	 * Removes mod powers, only admins can do this
	 * 
	 * Ajax protected until CSRF is setup
	 * 
	 * @author Woxxy
	 */
	function remove_mod($user_id)
	{
		if (!isAjax())
		{
			return false;
		}
		if (!$this->tank_auth->is_admin())
			return false;
		$profile = new Profile();
		if ($profile->change_group($user_id, 0))
		{
			flash_notice('notice', _('You have removed the user from the moderators group.'));
			$this->output->set_output(json_encode(array('href' => site_url('/admin/members/member/' . $user_id))));
			return true;
		}
		return false;
	}


}