<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Posts extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->auth->is_mod_admin() or redirect('@system/admin');

		$this->load->model('post_model', 'post');
		
		// title on top
		$this->viewdata['controller_title'] = '<a href="'.site_url("admin/posts").'">' . __("Posts") . '</a>';;
	}


	function index()
	{
		return $this->reports();
	}


	function reports($page = 1)
	{
		if(!is_natural($page) || $page == 0)
		{
			show_404();
		}
		
		$this->load->model('theme_model', 'theme');
		$this->theme->set_theme('default');
		
		$this->viewdata["function_title"] = __('Reports');
		
		// for safety, load all boards' preferences and not just the main table's
		$this->radix->load_preferences();

		// ['posts', 'total_found']
		$data = $this->post->get_reports($page);
		
		// for pagination copied from default theme
		$data['pagination']['total'] = $data['total_found']/25;
		$data['pagination']['current_page'] = $page;
		$data['pagination']['base_url'] = site_url(array('admin', 'posts', 'reports'));
		$this->viewdata['backend_vars'] = $this->get_backend_vars();
		$this->viewdata['backend_vars']['mod_url'] = site_url(array('admin', 'posts', 'mod_post_actions'));
		$this->viewdata["main_content_view"] = $this->load->view("admin/reports/manage.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}
	
	
	function mod_post_actions()
	{
		parent::mod_post_actions();
	}


	function action($type = 'remove', $report_id = 0, $remove = NULL)
	{
		if (!$this->input->is_ajax_request())
		{
			$this->output->set_output(__('Access to reports outside the administrative panel is denied.'));
			log_message('error', 'Controller: reports.php/action: access denied');
			return FALSE;
		}

		if (isset($_SERVER['HTTP_REFERER']))
		{
			$redirect = $_SERVER['HTTP_REFERER'];
		}
		else
		{
			$redirect = site_url('admin/posts/reports');
		}


		switch ($type)
		{
			case('ban'):

				$report = new Report();
				$result = $report->process_report(
					$report_id,
					array(
						'action' => 'ban',
						'value' => array(
							'banned_reason' => $this->input->post('banned_reason'),
							'banned_start' => $this->input->post('banned_start'),
							'banned_end' => $this->input->post('banned_end')
						)
					)
				);
				if (isset($result['error']))
				{
					flash_notice('error', $result['message']);
					$this->output->set_output(json_encode(array('href' => $redirect)));
					return FALSE;
				}
				flash_notice('notice', $result['message']);
				$this->output->set_output(json_encode(array('href' => $redirect)));

				break;

			case('delete'):

				$report = new Report();
				$result = $report->process_report(
					$report_id,
					array(
						'action' => 'delete',
						'value' => array(
							'delete' => $remove
						)
					)
				);
				if (isset($result['error']))
				{
					flash_notice('error', $result['message']);
					$this->output->set_output(json_encode(array('href' => $redirect)));
					return FALSE;
				}
				flash_notice('notice', $result['message']);
				$this->output->set_output(json_encode(array('href' => $redirect)));

				break;

			case('md5'):

				$report = new Report();
				$result = $report->process_report(
					$report_id,
					array(
						'action' => 'md5'
					)
				);
				if (isset($result['error']))
				{
					flash_notice('error', $result['message']);
					$this->output->set_output(json_encode(array('href' => $redirect)));
					return FALSE;
				}
				flash_notice('notice', $result['message']);
				$this->output->set_output(json_encode(array('href' => $redirect)));

				break;

			case('remove'):

				$report = new Report($report_id);
				if (!$report->remove_report_db())
				{
					flash_notice('error', __('Failed to remove the report from the database.'));
					log_message('error', 'Controller: reports.php/remove: failed to remove the report');
					$this->output->set_output(json_encode(array('href' => $redirect)));
					return FALSE;
				}
				flash_notice('notice', __('The report has been removed from the database.'));
				$this->output->set_output(json_encode(array('href' => $redirect)));

				break;

			case('spam'):

				$report = new Report();
				$result = $report->process_report(
					$report_id,
					array(
						'action' => 'spam'
					)
				);
				if (isset($result['error']))
				{
					flash_notice('error', $result['message']);
					$this->output->set_output(json_encode(array('href' => $redirect)));
					return FALSE;
				}
				flash_notice('notice', $result['message']);
				$this->output->set_output(json_encode(array('href' => $redirect)));

				break;
		}
	}


}