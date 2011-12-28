<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Reports extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->tank_auth->is_admin() or redirect('admin');

		// title on top
		$this->viewdata['controller_title'] = '<a href="'.site_url("admin/reports").'">' . _("Reports") . '</a>';;
	}


	function index()
	{
		return $this->manage();
	}


	function manage($page = 1)
	{
		$this->viewdata["function_title"] = _('Manage');
		$reports = new Report();

		$data["reports"] = $reports->list_reports_all_boards($page);

		$this->viewdata["main_content_view"] = $this->load->view("admin/reports/manage.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function action($method = NULL, $id = 0)
	{
		if (!$this->input->is_ajax_request())
		{
			$this->output->set_output(_('Access to reports outside the administrative panel is denied.'));
			log_message('error', 'Controller: reports.php/action: access denied');
			return FALSE;
		}

		switch ($method)
		{
			case('ban'):
				$report = new Report($id);
				if (!$report->ban())
				{
					$data = $report->get_post();
					flash_notice('error', sprintf(_('Failed to ban the following IP: %s.'), $data[0]->poster_ip));
					log_message('error', 'Controller: reports.php/ban: failed to ban ip');
					$this->output->set_output(json_encode(array('href' => site_url('admin/reports/manage'))));
					return FALSE;
				}
				flash_notice('notice', sprintf(_('The IP %s has been banned from posting.'), $data->poster_ip));
				$this->output->set_output(json_encode(array('href' => site_url('admin/reports/manage'))));
				break;

			case('delete'):
				$report = new Report($id);
				if (!$report->remove())
				{
					flash_notice('error', _('Failed to delete the reported post.'));
					log_message('error', 'Controller: reports.php/delete: failed to delete reported post');
					$this->output->set_output(json_encode(array('href' => site_url('admin/reports/manage'))));
					return FALSE;
				}
				flash_notice('notice', _('The reported post has been removed from the database.'));
				$this->output->set_output(json_encode(array('href' => site_url('admin/reports/manage'))));
				break;

			case('spam'):
				$report = new Report($id);
				if (!$report->remove_report_db())
				{
					flash_notice('error', _('Failed to remove the spam report from the database.'));
					log_message('error', 'Controller: reports.php/spam: remove the spam report');
					$this->output->set_output(json_encode(array('href' => site_url('admin/reports/manage'))));
					return FALSE;
				}
				flash_notice('notice', _('The report has been marked as spam and removed.'));
				$this->output->set_output(json_encode(array('href' => site_url('admin/reports/manage'))));
				break;
		}
	}

}