<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class System extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();

		// only admins should do this
		$this->tank_auth->is_admin() or redirect('admin');

		// we need the upgrade module's functions
		$this->load->model('upgrade_model');

		// page title
		$this->viewdata['controller_title'] = '<a href="' . site_url("admin/system") . '">' . _("System") . '</a>';
	}


	/*
	 * A page telling if there's an ugrade available
	 * 
	 * @author Woxxy
	 */
	function index()
	{
		redirect('/admin/system/information');
	}


	function information()
	{
		$this->viewdata["function_title"] = _("Information");

		// get current version from database
		$data["current_version"] = FOOLSLIDE_VERSION;
		$data["form_title"] = _("Information");

		$this->viewdata["main_content_view"] = $this->load->view("admin/system/information", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function preferences()
	{
		$this->viewdata["function_title"] = _("Preferences");

		$form = array();

		if (find_imagick())
		{
			$imagick_status = '<span class="label success">' . _('Found and Working') . '</span>';
		}
		else
		{
			if (!$this->fs_imagick->exec)
				$imagick_status = '<span class="label important">' . _('Not Available') . '</span><a rel="popover-right" href="#" data-content="' . htmlspecialchars(_('You must have Safe Mode turned off and the exec() function enabled to allow ImageMagick to process your images. Please check the information panel for more details.')) . '" data-original-title="' . htmlspecialchars(_('Disabled Functions')) . '"><img src="' . icons(388, 16) . '" class="icon icon-small"></a>';
			else if (!$this->fs_imagick->found)
				$imagick_status = '<span class="label important">' . _('Not Found') . '</span><a rel="popover-right" href="#" data-content="' . htmlspecialchars(_('You must provide the correct path to the "convert" binary on your system. This is typically located under /usr/bin (Linux), /opt/local/bin (Mac OSX) or the installation directory (Windows).')) . '" data-original-title="' . htmlspecialchars(_('Disabled Functions')) . '"><img src="' . icons(388, 16) . '" class="icon icon-small"></a>';
			else if (!$this->fs_imagick->available)
				$imagick_status = '<span class="label important">' . _('Not Working') . '</span><a rel="popover-right" href="#" data-content="' . htmlspecialchars(sprintf(_('There has been an error encountered when testing your ImageMagick installation. To manually check for errors, access your server via shell or command line and type: %s'), '<br/><code>' . $this->fs_imagick->found . ' -version</code>')) . '" data-original-title="' . htmlspecialchars(_('Disabled Functions')) . '"><img src="' . icons(388, 16) . '" class="icon icon-small"></a>';
		}


		$form[] = array(
			_('Path to ImageMagick') . ' ' . $imagick_status,
			array(
				'type' => 'input',
				'name' => 'fs_serv_imagick_path',
				'placeholder' => '/usr/bin',
				'preferences' => 'fs_gen',
				'help' => sprintf(_('FoOlSlide uses %s via command line to maximize the processor power for processing images. If ImageMagick %s automatically, enter the location of the "convert" binary on your server in the field above.'), '<a href="#" rel="popover-below" title="ImageMagick" data-content="' . _('This is a library used to dynamically create, edit, compose or convert images.') . '">ImageMagick</a>', '<a href="#" rel="popover-below" title="' . _('ImageMagick Binary') . '" data-content="' . htmlspecialchars(_('This is typically located under /usr/bin (Linux), /opt/local/bin (Mac OSX) or the installation directory (Windows).')) . '" >' . _('can\'t be found') . '</a>')
			)
		);


		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);
			redirect('admin/system/preferences');
		}

		// create a form
		$table = tabler($form, FALSE);
		$data['table'] = $table;

		// print out
		$this->viewdata["main_content_view"] = $this->load->view("admin/preferences/general.php", $data, TRUE);


		$data["form_title"] = _("Preferences");

		$this->viewdata["main_content_view"] = $this->load->view("admin/system/preferences", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
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
		// Support Checkbox Listing
		$former = array();
		foreach ($form as $key => $item)
		{
			if (isset($item[1]['value']) && is_array($item[1]['value']))
			{
				foreach ($item[1]['value'] as $key => $item2)
				{
					$former[] = array('1', $item2);
				}
			}
			else
				$former[] = $form[$key];
		}

		foreach ($former as $key => $item)
		{
			if (isset($post[$item[1]['name']]))
				$value = $post[$item[1]['name']];
			else
				$value = NULL;

			$this->db->from('preferences');
			$this->db->where(array('name' => $item[1]['name']));
			if ($this->db->count_all_results() == 1)
			{
				$this->db->update('preferences', array('value' => $value), array('name' => $item[1]['name']));
			}
			else
			{
				$this->db->insert('preferences', array('name' => $item[1]['name'], 'value' => $value));
			}
		}

		$CI = & get_instance();
		$array = $CI->db->get('preferences')->result_array();
		$result = array();
		foreach ($array as $item)
		{
			$result[$item['name']] = $item['value'];
		}
		$CI->fs_options = $result;
		flash_notice('notice', _('Settings updated.'));
	}


	function tools()
	{
		$this->db->dbdriver;
		$this->viewdata["function_title"] = _("Tools");

		// get current version from database
		$data["form_title"] = _("Tools");

		$data["imagick_optimize"] = FALSE;
		if (find_imagick())
		{
			$page = new Page();
			$page->where('description', '')->limit(1)->get();
			if ($page->result_count() == 1)
			{
				$data["imagick_optimize"] = TRUE;
			}
		}

		$data["database_backup"] = strtolower($this->db->dbdriver) == "mysql";
		$data["database_optimize"] = strtolower($this->db->dbdriver) == "mysql" || strtolower($this->db->dbdriver) == "mysqli";

		$logs = get_dir_file_info($this->config->item('log_path'));
		$data["logs_space"] = 0;
		foreach ($logs as $log)
		{
			$data["logs_space"] += $log["size"];
		}

		$data["logs_space"] = round($data["logs_space"] / 1024);

		$this->viewdata["main_content_view"] = $this->load->view("admin/system/tools", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function tools_optimize_thumbnails($howmany = NULL)
	{
		if (!isAjax())
		{
			show_404();
		}

		if (!find_imagick())
		{
			show_404();
		}

		$pages = new Page();
		if (is_null($howmany))
		{
			$count = $pages->where('description', '')->count();
			$this->output->set_output(json_encode(array('count' => $count)));
			return TRUE;
		}

		if (is_numeric($howmany) && $howmany > 0)
		{
			$pages->where('description', '')->limit(10)->get();
			if ($pages->result_count() < 1)
			{
				$this->output->set_output(json_encode(array('status' => 'done')));
				return TRUE;
			}

			$warnings = array();
			foreach ($pages->all as $page)
			{
				if (!$page->rebuild_thumbnail())
				{
					$last_notice = end($this->notices);
					if ($last_notice['type'] == 'warning')
					{
						$warnings[] = $last_notice['message'];
					}
					$this->output->set_output(json_encode(array('error' => $this->notices)));
					return FALSE;
				}
			}
			$this->output->set_output(json_encode(array('status' => 'success', 'warnings' => $warnings)));
			return TRUE;
		}
	}


	function tools_database_backup()
	{
		if (strtolower($this->db->dbdriver) != "mysql")
		{
			show_404();
		}

		$this->load->dbutil();
		$backup = & $this->dbutil->backup(array('filename' => '[' . date("Y-m-d") . ']FoOlSlide_database.gz'));
		$this->load->helper('download');
		force_download('[' . date("Y-m-d") . ']FoOlSlide_database.gz', $backup);
	}


	function tools_database_optimize()
	{
		if (!isAjax())
		{
			show_404();
		}

		if (strtolower($this->db->dbdriver) != "mysql" && strtolower($this->db->dbdriver) != "mysqli")
		{
			show_404();
		}

		$this->load->dbutil();
		$result = $this->dbutil->optimize_database();

		if ($result !== FALSE)
		{
			flash_notice('success', _('Your FoOlSlide database has been optimized.'));
			$this->output->set_output(json_encode(array('href' => site_url('admin/system/tools'))));
			return TRUE;
		}

		flash_notice('error', _('An error occurred while optimizing the database.'));
		$this->output->set_output(json_encode(array('href' => site_url('admin/system/tools'))));
		return FALSE;
	}


	function tools_logs_get($date = NULL)
	{
		$logs = get_dir_file_info($this->config->item('log_path'));

		if (count($logs) == 0)
		{
			$this->output->set_output(json_encode(array('error' => _('There are no logs available.'))));
			return FALSE;
		}

		// sort by key high to low
		ksort($logs);

		if (is_null($date))
		{
			$selected = end($logs);
		}
		else
		{
			$date = 'log-' . $date . '.php';
			if (!isset($logs[$date]))
			{
				$this->output->set_output(json_encode(array('error' => _('There is no available log for this date.'))));
				return FALSE;
			}
			$selected = $logs[$date];
		}

		$selected_log = read_file($selected['server_path']);
		$dates = array();
		foreach ($logs as $key => $log)
		{
			$dates[] = substr($key, 4, -4);
		}

		$this->output->set_output(json_encode(array('dates' => $dates, 'log' => $selected_log)));
	}


	function tools_logs_prune()
	{
		if (!isAjax())
		{
			show_404();
		}

		delete_files($this->config->item('log_path'));
		flash_notice('success', _('Your FoOlSlide logs have been pruned.'));
		$this->output->set_output(json_encode(array('href' => site_url('admin/system/tools'))));
	}


	function tools_check_comics($repair = FALSE)
	{
		// basically CSRF protection from repairing
		if (!$this->input->is_cli_request())
		{
			$repair = FALSE;
		}

		if ($this->input->post('repair') == 'repair')
		{
			$repair = TRUE;
		}

		$recursive = FALSE;
		if ($this->input->is_cli_request())
		{
			$recursive = TRUE;
		}

		$comics = new Comic();
		$comics->check_external($repair, $recursive);

		$warnings = array();
		foreach ($this->notices as $notice)
		{
			if ($notice['type'] == 'error')
			{
				if (!$this->input->is_cli_request())
				{
					$this->output->set_output(json_encode(array('status' => 'error', 'message' => $notice['message'])));
				}
				if ($this->input->is_cli_request())
				{
					echo PHP_EOL . _('You have to correct the errors above to continue.') . PHP_EOL;
				}
				return FALSE;
			}

			if ($notice['type'] == 'warning')
			{
				$warnings[] = $notice['message'];
			}
		}

		if (!$recursive)
		{
			// if we are here we at most have warning notices
			// add count to request so we can process chapters one by one
			$chapters = new Chapter();
			$count = $chapters->count();
		}

		if (!$this->input->is_cli_request())
		{
			$this->output->set_output(json_encode(array(
						'status' => (count($warnings) > 0) ? 'warning' : 'success',
						'messages' => $warnings,
						'count' => $count
					)));
		}
		else
		{
			echo '#----------DONE----------#' . PHP_EOL;
			if (!$repair)
				echo sprintf(_('To repair automatically by removing the unidentified data and rebuilding the missing thumbnails, enter: %s'), 'php ' . FCPATH . 'index.php admin system tools_check_comics repair') . PHP_EOL;
			else
				echo _('Successfully repaired your library.') . PHP_EOL;
		}
	}


	function tools_check_library()
	{
		$type = $this->input->post('type');
		if ($type != 'page' && $type != 'chapter')
		{
			show_404();
		}

		$page = $this->input->post('page');
		if (!is_numeric($page))
		{
			show_404();
		}

		$repair = FALSE;
		if ($this->input->post('repair') == 'repair')
		{
			$repair = TRUE;
		}

		if ($type == 'page')
		{
			$count = 300;
			if ($repair)
			{
				$count = 50;
			}
			$items = new Page();
		}

		if ($type == 'chapter')
		{
			$count = 15;
			if ($repair)
			{
				$count = 2;
			}
			$items = new Chapter();
		}

		$offset = ($page * $count) - $count;
		$items->limit($count, $offset)->get_iterated();

		if ($items->result_count() == 0)
		{
			if ($type == 'chapter')
			{
				$pages = new Page();
				$pages_count = $pages->count();
				$this->output->set_output(json_encode(array(
							'status' => 'done',
							'pages_count' => $pages_count
						)));
			}
			else
			{
				$this->output->set_output(json_encode(array(
							'status' => 'done'
						)));
			}
			return TRUE;
		}

		foreach ($items as $item)
		{
			$item->check($repair);
		}

		$warnings = array();
		foreach ($this->notices as $notice)
		{
			if ($notice['type'] == 'error')
			{
				if (!$this->input->is_cli_request())
				{
					$this->output->set_output(json_encode(array('status' => 'error', 'message' => $notice['message'])));
				}
				return FALSE;
			}

			if ($notice['type'] == 'warning')
			{
				$warnings[] = $notice['message'];
			}
		}

		$this->output->set_output(json_encode(array(
					'status' => (count($warnings) > 0) ? 'warning' : 'success',
					'messages' => $warnings,
					'processed' => $items->result_count()
				)));
	}


	function upgrade()
	{
		$this->viewdata["function_title"] = _("Upgrade FoOlSlide");

		// get current version from database
		$data["current_version"] = FOOLSLIDE_VERSION;

		// check if the user can upgrade by checking if files are writeable
		$data["can_upgrade"] = $this->upgrade_model->check_files();
		if (!$data["can_upgrade"])
		{
			// if there are not writeable files, suggest the actions to take
			$this->upgrade_model->permissions_suggest();
		}

		// look for the latest version available
		$data["new_versions"] = $this->upgrade_model->check_latest();

		// print out
		$this->viewdata["main_content_view"] = $this->load->view("admin/system/upgrade", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	/*
	 * This just triggers the upgrade function in the upgrade model
	 * 
	 * @author Woxxy
	 */
	function do_upgrade()
	{

		if (!isAjax())
		{
			return false;
		}

		// triggers the upgrade
		if (!$this->upgrade_model->do_upgrade())
		{
			// clean the cache in case of failure
			$this->upgrade_model->clean();
			// show some kind of error
			log_message('error', 'system.php do_upgrade(): failed upgrade');
			flash_message('error', _('Upgrade failed: check file permissions.'));
		}

		// return an url
		$this->output->set_output(json_encode(array('href' => site_url('admin/system/upgrade'))));
	}


	function pastebin()
	{
		if (!isAjax())
		{
			show_404();
		}

		$response = '';

		if ($post = $this->input->post())
		{
			$api_dev_key = '04798e47893bd006f2061e736342a83b';
			$api_paste_private = '1';
			$api_paste_expire_date = '1H';
			$api_paste_format = 'text';
			$api_user_key = '';

			$this->load->library('curl');

			$this->curl->create('http://pastebin.com/api/api_post.php');

			$this->curl->options(array(
				'POST' => true,
				'RETURNTRANSFER' => 1,
				'VERBOSE' => 1,
				'NOBODY' => 0
			));

			$this->curl->post(array(
				'api_option' => 'paste',
				'api_user_key' => $api_user_key,
				'api_paste_private' => $api_paste_private,
				'api_paste_name' => 'FoOlSlide System Information Output',
				'api_paste_expire_date' => $api_paste_expire_date,
				'api_paste_format' => $api_paste_format,
				'api_dev_key' => $api_dev_key,
				'api_paste_code' => $post['output'],
			));

			$response = $this->curl->execute();
		}

		$this->output->set_output(json_encode(array('href' => $response)));
	}


}
