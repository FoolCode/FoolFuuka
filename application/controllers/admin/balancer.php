<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Balancer extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();

		// preferences are settable only by admins!
		$this->tank_auth->is_admin() or redirect('admin');

		// title on top
		$this->viewdata['controller_title'] = '<a href="' . site_url("admin/balancer/balancers") . '">' . _("Load Balancer") . '</a>';
	}


	/*
	 * _submit is a private function that submits to the "preferences" table.
	 * entries that don't exist are created. the preferences table could get very large
	 * but it's not really an issue as long as the variables are kept all different.
	 * 
	 * @author Woxxy
	 */
	function _submit($post, $form)
	{
		foreach ($form as $key => $item)
		{

			if (isset($post[$item[1]['name']]))
				$value = $post[$item[1]['name']];
			else
				$value = NULL;

			$this->db->from('preferences');
			$this->db->where(array('name' => $item[1]['name']));
			if (is_array($value))
			{
				foreach ($value as $key => $val)
				{
					if ($value[$key] == "")
						unset($value[$key]);
				}
				$value = serialize($value);
			}
			if ($this->db->count_all_results() == 1)
			{
				$this->db->update('preferences', array('value' => $value), array('name' => $item[1]['name']));
			}
			else
			{
				$this->db->insert('preferences', array('name' => $item[1]['name'], 'value' => $value));
			}
		}


		load_settings();

		set_notice('notice', _('Updated settings.'));
	}


	/*
	 * Allows turning FoOlSlide into a load balancing clone
	 * 
	 * @author Woxxy
	 */
	function balancers()
	{
		if ($this->input->post())
		{
			$result = array();
			if ($urls = $this->input->post('url'))
			{
				$priorities = $this->input->post('priority');
				if (is_array($urls))
				{
					foreach ($urls as $key => $item)
					{
						if (!$item)
						{
							unset($urls[$key]);
							break;
						}
						if ($priorities[$key] >= 0 && $priorities[$key] <= 100)
						{
							$result[] = array('url' => $item, 'priority' => $priorities[$key]);
						}
					}
				}
				$result = serialize($result);

				$this->db->from('preferences');
				$this->db->where(array('name' => 'fs_balancer_clients'));
				if ($this->db->count_all_results() == 1)
				{
					$this->db->update('preferences', array('value' => $result), array('name' => 'fs_balancer_clients'));
				}
				else
				{
					$this->db->insert('preferences', array('name' => 'fs_balancer_clients', 'value' => $result));
				}
			}

			if ($value = $this->input->post('fs_balancer_ips'))
			{
				if (is_array($value))
				{
					foreach ($value as $key => $val)
					{
						if ($value[$key] == "")
							unset($value[$key]);
					}
					$value = serialize($value);
				}

				$this->db->from('preferences');
				$this->db->where(array('name' => 'fs_balancer_ips'));
				if ($this->db->count_all_results() == 1)
				{
					$this->db->update('preferences', array('value' => $value), array('name' => 'fs_balancer_ips'));
				}
				else
				{
					$this->db->insert('preferences', array('name' => 'fs_balancer_ips', 'value' => $value));
				}
			}

			load_settings();

			set_notice('notice', _('Updated settings.'));
		}


		if (get_setting('fs_balancer_clients'))
		{
			$data["balancers"] = unserialize(get_setting('fs_balancer_clients'));
		}
		else
		{
			$data["balancers"] = array();
		}
		if (get_setting('fs_balancer_ips'))
		{
			$data["ips"] = unserialize(get_setting('fs_balancer_ips'));
		}
		else
		{
			$data["ips"] = array();
		}
		$this->viewdata['function_title'] = _('Balancers');
		$this->viewdata["main_content_view"] = $this->load->view("admin/loadbalancer/balancers_list.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function _check_client($url)
	{
		$result = @file_get_contents($url . '/api/status/status/format/json');
		if (is_null($result))
		{
			return array('error' => _("Unavailable"));
		}

		$result = json_decode($result, TRUE);

		if (isset($result["error"]))
		{
			
		}
	}


	/*
	 * Allows turning FoOlSlide into a load balancing clone
	 * 
	 * @author Woxxy
	 */
	function client()
	{
		$this->viewdata["function_title"] = _("Client");

		$form = array();

		// build the array for the form
		$form[] = array(
			_('URL to master FoOlSlide root'),
			array(
				'type' => 'input',
				'name' => 'fs_balancer_master_url',
				'id' => 'site_title',
				'placeholder' => 'http://',
				'preferences' => 'fs_gen',
				'help' => _('Providing a URL will convert this FoOlSlide installation into a load balancer.')
			)
		);

		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);
		}

		// create a form
		$table = tabler($form, FALSE);
		$data['form_title'] = _('Client');
		$data['form_description'] = _('These settings can only be activated if there are no comics available on this FoOlSlide. All core functions will be disabled and your data will be duplicated quietly in the background.');
		$data['table'] = $table;

		// print out
		$this->viewdata['function_title'] = _('Client');
		$this->viewdata["main_content_view"] = $this->load->view("admin/preferences/general.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


}