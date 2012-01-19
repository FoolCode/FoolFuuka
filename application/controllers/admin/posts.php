<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Posts extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();

		if (!($this->tank_auth->is_allowed()))
			redirect('admin');

		// title on top
		$this->viewdata['controller_title'] = '<a href="'.site_url("admin/posts").'">' . _("Posts") . '</a>';;
	}


	function index()
	{
		return $this->reports();
	}


	function reports($page = 1)
	{
		$this->viewdata["function_title"] = _('Reports');
		$reports = new Report();

		$data["reports"] = $reports->list_all_reports($page);

		$this->viewdata["main_content_view"] = $this->load->view("admin/reports/manage.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function spam($page = 1)
	{

	}


	function action($method = NULL, $id = 0, $remove = NULL)
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
				$report = new Report();
				$params = array('process' => 'ban', 'banned_reason' => '', 'banned_start' => '', 'banned_end' => '');
				if (!$data = $report->process_report($id, $params))
				{
					flash_notice('error', _('Failed to ban the IP.'));
					log_message('error', 'Controller: reports.php/ban: failed to ban ip');
					$this->output->set_output(json_encode(array('href' => site_url('admin/posts/reports'))));
					return FALSE;
				}
				flash_notice('notice', sprintf(_('The IP %s has been banned from posting.'), $data->poster_ip));
				$this->output->set_output(json_encode(array('href' => site_url('admin/posts/reports'))));
				break;

			case('delete'):
				$report = new Report();
				$params = array('process' => 'delete', 'remove' => $remove);
				if (!$report->process_report($id, $params))
				{
					flash_notice('error', _('Failed to delete the reported post/image.'));
					log_message('error', 'Controller: reports.php/delete: failed to delete reported post/image');
					$this->output->set_output(json_encode(array('href' => site_url('admin/posts/reports'))));
					return FALSE;
				}
				flash_notice('notice', _('The reported post/image has been removed from the database.'));
				$this->output->set_output(json_encode(array('href' => site_url('admin/posts/reports'))));
				break;

			case('remove'):
				$report = new Report($id);
				if (!$report->remove_report_db())
				{
					flash_notice('error', _('Failed to remove the report from the database.'));
					log_message('error', 'Controller: reports.php/remove: failed to remove the report');
					$this->output->set_output(json_encode(array('href' => site_url('admin/posts/reports'))));
					return FALSE;
				}
				flash_notice('notice', _('The report has been removed.'));
				$this->output->set_output(json_encode(array('href' => site_url('admin/posts/reports'))));
				break;

			case('spam'):
				$report = new Report();
				$params = array('process' => 'spam');
				if (!$report->process_report($id, $params))
				{
					flash_notice('error', _('Failed to mark the reported post as spam.'));
					log_message('error', 'Controller: reports.php/spam: failed to mark the post as spam');
					$this->output->set_output(json_encode(array('href' => site_url('admin/posts/reports'))));
					return FALSE;
				}
				flash_notice('notice', _('The reported post has been marked as spam.'));
				$this->output->set_output(json_encode(array('href' => site_url('admin/posts/reports'))));
				break;
		}
	}

}