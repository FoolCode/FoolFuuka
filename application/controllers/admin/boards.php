<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Boards extends Admin_Controller
{


	function __construct()
	{
		parent::__construct();
		$this->tank_auth->is_admin() or redirect('admin');

		// title on top
		$this->viewdata['controller_title'] = '<a href="' . site_url("admin/boards") . '">' . _("Boards") . '</a>';
	}


	function index()
	{
		return $this->manage();
	}


	function manage()
	{
		$this->viewdata["function_title"] = _('Manage boards');

		$data["boards"] = $this->radix->get_all();

		$this->viewdata["main_content_view"] = $this->load->view("admin/boards/manage.php",
			$data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function board($shortname = NULL)
	{
		$data['form'] = $this->radix->structure();

		if ($this->input->post())
		{
			$this->load->library('form_validation');
			$result = $this->form_validation->form_validate($data['form']);
			if (isset($result['error']))
			{
				set_notice('warning', $result['error']);
			}
			else
			{;
				// it's actually fully checked, we just have to throw it in DB
				$this->radix->save($result['success']);
				if (is_null($shortname))
				{
					flash_notice('success', _('New board created!'));
					redirect('admin/boards/board/' . $result['success']['shortname']);
				}
				else if ($shortname != $result['success']['shortname'])
				{
					// case in which letter was changed
					flash_notice('success', _('Board information updated.'));
					redirect('admin/boards/board/' . $result['success']['shortname']);
				}
				else
				{
					set_notice('success', _('Board information updated.'));
				}
			}
		}

		$board = $this->radix->get_by_shortname($shortname);
		if ($board === FALSE)
		{
			show_404();
		}

		$data['object'] = $board;

		$this->viewdata["function_title"] = _('Editing board:') . ' ' . $board->shortname;
		$this->viewdata["main_content_view"] = $this->load->view('admin/form_creator',
			$data, TRUE);
		$this->load->view('admin/default', $this->viewdata);
	}


	function add_new()
	{
		$data['form'] = $this->radix->structure();

		if ($this->input->post())
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
				$this->radix->save($result['success']);
				flash_notice('success', _('New board created!'));
				redirect('admin/boards/board/' . $result['success']['shortname']);
			}
		}
		
		// the actual POST is in the board() function
		$data['form']['open']['action'] = site_url('admin/boards/add_new');

		// panel for creating a new board
		$this->viewdata["function_title"] = _('Creating a new board');
		$this->viewdata["main_content_view"] = $this->load->view('admin/form_creator',
			$data, TRUE);
		$this->load->view('admin/default', $this->viewdata);

		return TRUE;
	}


	function delete($type, $id = 0)
	{
		$board = $this->radix->get_by_id($id);
		if ($board == FALSE)
		{
			show_404();
		}

		if ($this->input->post())
		{
			switch ($type)
			{
				case("board"):
					if (!$this->radix->remove($id))
					{
						flash_notice('error',
							sprintf(_('Failed to delete the board %s.'), $board->shortname));
						log_message("error", "Controller: board.php/remove: failed board removal");
						redirect('admin/boards/manage');
					}
					flash_notice('success',
						sprintf(_('The board %s has been deleted.'), $board->shortname));
					redirect('admin/boards/manage');
					break;
			}
		}

		switch ($type)
		{
			case('board'):
				$this->viewdata["function_title"] = _('Removing board:') . ' ' . $board->shortname;
				$data['alert_level'] = 'warning';
				$data['message'] = _('Do you really want to remove the board and all its data?') .
					'<br/>' .
					_('Notice: due to its size, you will have to remove the image folder manually. The folder will have the "removed_" prefix.');

				$this->viewdata["main_content_view"] = $this->load->view('admin/confirm',
					$data, TRUE);
				$this->load->view('admin/default', $this->viewdata);
				break;
		}
	}
	
	
	function preferences()
	{
		$this->viewdata["function_title"] = _("Preferences");

		$form = array();
		
		$form['open'] = array(
			'type' => 'open'
		);

		$form['fs_fuuka_boards_directory'] = array(
			'type' => 'input',
			'label' => _('Boards directory'),
			'preferences' => TRUE,
			'help' => _('Overrides the default path to the boards directory (Example: /var/www/foolfuuka/boards)')
		);

		$form['fs_fuuka_boards_url'] = array(
			'type' => 'input',
			'label' => _('Boards URL'),
			'preferences' => TRUE,
			'help' => _('Overrides the default url to the boards folder (Example: http://foolfuuka.site.com/there/boards)')
		);

		$form['fs_fuuka_boards_db'] = array(
			'type' => 'input',
			'label' => _('Boards database'),
			'preferences' => TRUE,
			'help' => _('Overrides the default database. You should point it to your Asagi database if you have a separate one.')
		);

		$form['separator-2'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => _('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);

		$this->submit_preferences_auto($form);

		$data['form'] = $form;

		// create a form
		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator",
			$data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}

	
	function asagi()
	{
		$this->load->model('asagi_model', 'asagi');
		
		if($this->input->post('install') || $this->input->post('upgrade'))
		{
			$this->asagi->install();
			set_notice('success', _('Downloaded and installed the latest version of Asagi.'));
		}
		
		if($this->asagi->is_installed() && $this->input->post('remove'))
		{
			$this->asagi->remove();
			set_notice('success', _('Asagi has been removed.'));
		}
		
		if($this->asagi->is_installed() && $this->input->post('update_settings'))
		{
			$this->asagi->update_settings();
			set_notice('success', _('Settings updated.'));
		}
		
		if($this->asagi->is_installed() && $this->input->post('run'))
		{
			$this->asagi->run();
			set_notice('success', _('Ran Asagi.'));
		}
		
		if($this->asagi->is_installed() && $this->input->post('kill'))
		{
			$this->asagi->kill();
			set_notice('success', _('Stopped Asagi.'));
		}
		
		if($this->asagi->is_installed() && $this->input->post('enable_autorun'))
		{
			$this->submit_preferences(array('fs_asagi_autorun_enabled' => 1));
			set_notice('success', _('Enabled Asagi autorun.'));
		}
		
		if($this->asagi->is_installed() && $this->input->post('disable_autorun'))
		{
			$this->submit_preferences(array('fs_asagi_autorun_enabled' => 0));
			set_notice('success', _('Disabled Asagi autorun.'));
		}
		
		if($this->asagi->is_installed())
		{
			$this->viewdata["function_title"] = _('Asagi');
			$this->viewdata["main_content_view"] = $this->load->view('admin/boards/asagi',
					NULL, TRUE);
			$this->load->view('admin/default', $this->viewdata);
			return TRUE;
		}
		else
		{
			$this->viewdata["function_title"] = _('Asagi installation');
			$this->viewdata["main_content_view"] = $this->load->view('admin/boards/asagi_install',
					NULL, TRUE);
			$this->load->view('admin/default', $this->viewdata);
		}
	}

	function sphinx()
	{
		$this->viewdata["function_title"] = 'Sphinx';
		$this->load->library('SphinxQL');
		
		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);
		
		$form['fs_sphinx_global'] = array(
			'type' => 'checkbox',
			'label' => 'Global SphinxSearch',
			'placeholder' => 'FoOlFuuka',
			'preferences' => TRUE,
			'help' => _('Activate Sphinx globally (enables crossboard search)')
		);

		$form['fu_sphinx_listen'] = array(
			'type' => 'input',
			'label' => 'Listen (Sphinx)',
			'placeholder' => FOOL_PREF_SPHINX_LISTEN,
			'preferences' => TRUE,
			'help' => _('Set the address and port to your Sphinx instance.'),
			'class' => 'span2',
			'validation' => 'trim|max_length[48]',
			'validation_func' => function($input, $form)
			{
				if (strpos($input['fu_sphinx_listen'], ':') === FALSE)
				{
					return array(
						'error_code' => 'MISSING_COLON',
						'error' => _('The Sphinx listening address and port aren\'t formatted correctly.')
					);
				}

				$sphinx_ip_port = explode(':', $input['fu_sphinx_listen']);

				if (count($sphinx_ip_port) != 2)
				{
					return array(
						'error_code' => 'WRONG_COLON_NUMBER',
						'error' => _('The Sphinx listening address and port aren\'t formatted correctly.')
					);
				}

				if (!is_natural($sphinx_ip_port[1]))
				{
					return array(
						'error_code' => 'PORT_NOT_A_NUMBER',
						'error' => _('The port specified isn\'t a valid number.')
					);
				}

				$CI = & get_instance();
				$CI->load->library('SphinxQL');
				$connection = @$CI->sphinxql->set_server($sphinx_ip_port[0],
						$sphinx_ip_port[1]);

				if ($connection === FALSE)
				{
					return array(
						'warning_code' => 'CONNECTION_NOT_ESTABLISHED',
						'warning' => _('The Sphinx server couldn\'t be contacted at the specified address and port.')
					);
				}

				return array('success' => TRUE);
			}
		);

		$form['fu_sphinx_listen_mysql'] = array(
			'type' => 'input',
			'label' => 'Listen (MySQL)',
			'placeholder' => FOOL_PREF_SPHINX_LISTEN_MYSQL,
			'preferences' => TRUE,
			'validation' => 'trim|max_length[48]',
			'help' => _('Set the address and port to your MySQL instance.'),
			'class' => 'span2'
		);

		$form['fu_sphinx_dir'] = array(
			'type' => 'input',
			'label' => 'Working Directory',
			'placeholder' => FOOL_PREF_SPHINX_DIR,
			'preferences' => TRUE,
			'help' => _('Set the working directory to your Sphinx working directory.'),
			'class' => 'span3',
			'validation' => 'trim',
			'validation_func' => function($input, $form)
			{
				if (!file_exists($input))
				{
					return array(
						'error_code' => 'SPHINX_WORKING_DIR_NOT_FOUND',
						'error' => _('Couldn\'t find the Sphinx working directory.')
					);
				}

				return array('success' => TRUE);
			}
		);

		$form['fu_sphinx_min_word_len'] = array(
			'type' => 'input',
			'label' => 'Minimum Word Length',
			'placeholder' => FOOL_PREF_SPHINX_MIN_WORD,
			'preferences' => TRUE,
			'help' => _('Set the minimum word length indexed by Sphinx.'),
			'class' => 'span1',
			'validation' => 'trim|is_natural_no_zero'
		);

		$form['fu_sphinx_mem_limit'] = array(
			'type' => 'input',
			'label' => 'Memory Limit',
			'placeholder' => FOOL_PREF_SPHINX_MEMORY,
			'validation' => 'is_natural|greater_than[256]',
			'preferences' => TRUE,
			'help' => _('Set the memory limit for the Sphinx instance in MegaBytes.'),
			'class' => 'span1'
		);

		$form['separator'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => _('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);

		$this->submit_preferences_auto($form);

		// create the form
		$data['form'] = $form;

		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator",
			$data, TRUE);
		$this->viewdata["main_content_view"] .= '<pre>' . $this->sphinxql->generate_sphinx_config($this->radix->get_all()) . '</pre>';
		$this->load->view("admin/default", $this->viewdata);
	}

}

/* End of file boards.php */
/* Location: ./application/controllers/admin/boards.php */