<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Database extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();
		// don't redirect if it's coming from the command line
		// that's because command line has no login skills...
		$this->tank_auth->is_admin() or redirect('admin');

		// power the migration library
		$this->load->library('migration');
		$this->config->load('migration');

		// title on top
		$this->viewdata['controller_title'] = _("Database");
	}


	/*
	 * Just shows the database admin page. Can be seen by admin only.
	 * 
	 * @author Woxxy
	 */
	function upgrade()
	{
		if (!$this->tank_auth->is_admin())
		{
			show_404();
			return FALSE;
		}

		// get the migration version
		$db_version = $this->db->get('migrations')->row()->version;
		$config_version = $this->config->item('migration_version');

		// if the version is the same, there's no need to be in this page
		if ($db_version == $config_version)
		{
			redirect('admin');
		}

		// subtitle on top
		$this->viewdata['function_title'] = _('Upgrade');

		// variable for suggesting command via command line
		$data["CLI_code"] = 'php ' . FCPATH . 'index.php admin database do_upgrade';

		// spawn the page
		$this->viewdata["main_content_view"] = $this->load->view("admin/database/upgrade", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	/*
	 * Does the actual database update, no models used, it's all in controller
	 * This supports command line for avoiding timeouts in large installations
	 * 
	 * @author Woxxy
	 */
	function do_upgrade()
	{
		if (!isAjax() && !$this->input->is_cli_request())
			return FALSE;
		// migrate
		$this->migration->latest();

		// give the correct kind of output, be it JSON via javascript or CLI request
		if ($this->input->is_cli_request())
			$this->output->set_output(_('Successfully updated the database.') . PHP_EOL);
		else
			$this->output->set_output(json_encode(array('href' => site_url('admin/')))); // give the url to go back to
		return TRUE;
	}


}