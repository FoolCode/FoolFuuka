<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Plugins_Admin extends Admin_Controller
{


	function __construct()
	{
		parent::__construct();
		$this->tank_auth->is_admin() or redirect('admin');

		$this->viewdata['controller_title'] = '<a href="' . site_url("admin/plugins") . '">' .
			__("Plugins") . '</a>';
	}


	function manage()
	{
		$data = array();
		$data['plugins'] = $this->plugins->get_all();
		$this->viewdata['function_title'] = __('Manage');
		$this->viewdata["main_content_view"] = $this->load->view("admin/plugins/manage.php",
			$data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function action($slug)
	{
		if (!$this->input->post('action') || !in_array($this->input->post('action'),
				array('enable', 'disable', 'remove')))
		{
			show_404();
		}

		$action = $this->input->post('action');

		switch ($action)
		{
			case 'enable':
				$plugin = $this->plugins->enable($slug);
				if ($plugin === FALSE)
				{
					log_message('error', 'Plugin couldn\'t be enabled');
					flash_notice('error', __('The plugin couldn\'t be enabled.'));
				}
				else
				{
					flash_notice('success',
						sprintf(__('The %s plugin is now enabled.'), $plugin->info->name));
				}
				break;

			case 'disable':
				$plugin = $this->plugins->disable($slug);
				if ($plugin === FALSE)
				{
					log_message('error', 'Plugin couldn\'t be disabled');
					flash_notice('error', __('The plugin couldn\'t be disabled.'));
				}
				else
				{
					flash_notice('success',
						sprintf(__('The %s plugin is now disabled.'), $plugin->info->name));
				}
				break;

			case 'remove':
				$plugin = $this->plugins->get_by_slug($slug);
				$result = $this->plugins->remove($slug);
				if (isset($result['error']))
				{
					log_message('error', 'Plugin couldn\'t be removed');
					flash_notice('error',
						sprintf(__('The %splugin couldn\'t be removed.'), $plugin->info->name));
				}
				else
				{
					flash_notice('success',
						sprintf(__('The %s plugin was removed.'), $plugin->info->name));
				}
				break;
		}

		redirect('admin/plugins/manage');
	}

}