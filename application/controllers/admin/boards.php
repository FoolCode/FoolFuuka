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
		;
	}


	function index()
	{
		return $this->manage();
	}


	function manage()
	{
		$this->viewdata["function_title"] = _('Manage boards');

		$data["boards"] = $this->radix->get_all();
		;

		$this->viewdata["main_content_view"] = $this->load->view("admin/boards/manage.php",
			$data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function board($shortname = NULL)
	{
		$data['form'] = $this->radix->board_structure();

		if ($this->input->post())
		{
			$result = $this->form_validate($data['form']);
			if (isset($result['error']))
			{
				set_notice('warning', $result['error']);
			}
			else
			{
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
		$data['form'] = $this->radix->board_structure();

		// the actual POST is in the board() function
		$data['form']['open']['action'] = site_url('admin/boards/board');

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


	function sphinx()
	{
		$this->viewdata["function_title"] = _('Sphinx');

		$form = array();

		$form['open'] = array(
			'type' => 'open'
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
				$connection = @$CI->sphinxql->SetServer($sphinx_ip_port[0],
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

		if ($post = $this->input->post())
		{
			$result = $this->form_validate($form);
			if (isset($result['error']))
			{
				set_notice('warning', $result['error']);
			}
			else
			{
				if (isset($result['warning']))
				{
					set_notice('warning', $result['warning']);
				}
				$this->submit_preferences($result['success']);
			}
		}

		// create the form
		$data['form'] = $form;

		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator",
			$data, TRUE);
		$this->viewdata["main_content_view"] .= '<pre>' . $this->_generate_sphinx_config() . '</pre>';
		$this->load->view("admin/default", $this->viewdata);
	}


	function _generate_sphinx_config()
	{
		$config = '
########################################################
## Sphinx Configuration for FoOlFuuka
########################################################
		';

		$config .= '
########################################################
## data source definition
########################################################

source main
{
	# data source type. mandatory, no default value
	# known types are mysql, pgsql, mssql, xmlpipe, xmlpipe2, odbc
	type = mysql

	# SQL source information
	sql_host =
	sql_user =
	sql_pass =
	sql_db =
	sql_port =

	sql_query_pre = SET NAMES utf8
	sql_range_step = 10000
	sql_query =

	sql_attr_uint = num
	sql_attr_uint = subnum
	sql_attr_uint = int_capcode
	sql_attr_bool = has_image
	sql_attr_bool = is_internal
	sql_attr_bool = is_deleted
	sql_attr_timestamp = timestamp

	sql_query_info =
	sql_query_post_index =
}
		';

		foreach ($this->radix->get_all() as $key => $board)
		{
			if ($board->sphinx)
			{
				$config .= $this->_generate_sphinx_definition_source($board->shortname);
			}
		}

		$config .= '
########################################################
## index definition
########################################################

index main
{
	source = main
	path = ' . get_setting('fu_sphinx_dir') . '/data/main
	docinfo = extern
	mlock = 0
	morphology = none
	min_word_len = ' . get_setting('fu_sphinx_mem_limit',
				FOOL_PREF_SPHINX_MEMORY) . 'M
	charset_type = utf-8

	charset_table =

	min_prefix_len = 3
	prefix_fields = comment, title
	enable_star = 1
	html_strip = 0
}
		';

		foreach ($this->radix->get_all() as $key => $board)
		{
			if ($board->sphinx)
			{
				$config .= $this->_generate_sphinx_definition_index($board->shortname,
					((get_setting('fu_sphinx_dir')) ? get_setting('fu_sphinx_dir') : '/usr/local/sphinx/var' ) . '/data');
			}
		}

		$config .= '
########################################################
## indexer settings
########################################################

indexer
{
	mem_limit = ' . ((get_setting('fu_sphinx_mem_limit'))
					? get_setting('fu_sphinx_mem_limit') : '2047M') . '
	max_xmlpipe2_field = 4M
	write_buffer = 5M
	max_file_field_buffer = 32M
}
';

		$config .= '
########################################################
## searchd settings
########################################################

searchd
{
	listen = ' . ((get_setting('fu_sphinx_listen'))
		? get_setting('fu_sphinx_listen') . ':sphinx' : '127.0.0.1:9312:sphinx') . '
	listen = ' . ((get_setting('fu_sphinx_listen_mysql'))
		? get_setting('fu_sphinx_listen_mysql') . ':mysql41' : '127.0.0.1:9312:mysql41') . '
	log = ' . ((get_setting('fu_sphinx_dir'))
		? get_setting('fu_sphinx_dir') : '/usr/local/sphinx/var' ) . '/log/searchd.log
	query_log = ' . ((get_setting('fu_sphinx_dir'))
		? get_setting('fu_sphinx_dir') : '/usr/local/sphinx/var' ) . '/log/query.log
	read_timeout = 5
	client_timeout = 300
	max_children = 10
	pid_file = ' . ((get_setting('fu_sphinx_dir'))
		? get_setting('fu_sphinx_dir') : '/usr/local/sphinx/var' ) . '/searchd.pid
	max_matches = 5000
	seamless_rotate = 1
	preopen_indexes = 1
	unlink_old = 1
	mva_updates_pool = 1M
	max_packet_size = 8M
	max_filters = 256
	max_filter_values = 4096
	max_batch_queries = 32
	workers = threads
	binlog_path = ' . ((get_setting('fu_sphinx_dir'))
		? get_setting('fu_sphinx_dir') : '/usr/local/sphinx/var' ) . '/data
	collation_server = utf8_general_ci
	collation_libc_locale = en_US.UTF-8
	compat_sphinxsql_magics = 0
}
		';

		$config .= '
# --eof--
		';

		return $config;
	}


	function _generate_sphinx_definition_source($board)
	{
		return "
# /" . $board . "/
source " . $board . "_main : main
{
	sql_query = SELECT doc_id, num, subnum, name, trip, (ascii(capcode)) as int_capcode, (subnnum != 0) as is_internal, deleted as is_deleted, timestamp, title, comment FROM " . $board . " WHERE doc_id >= \$start AND doc_id <= \$end
	sql_query_info = SELECT * FROM " . $board . " WHERE doc_id = \$id
	sql_query_range = SELECT (SELECT val FROM index_counters WHERE id = 'max_ancient_id_" . $board . "'), (SELECT MAX(doc_id) FROM " . $board . ")
	sql_query_post_index = REPLACE INTO index_counters (id, val) VALUES ('max_index_id_" . $board . ", \$maxid)
}

source " . $board . "_ancient : " . $board . "_main
{
	sql_query_range = SELECT MIN(doc_id), MAX(doc_id) FROM " . $board . "
	sql_query_post_index = REPLACE INTO index_counters (id, val) VALUES ('max_ancient_id_" . $board . ", \$maxid)
}

source " . $board . "_delta : " . $board . "_main
{
	sql_query_range = SELECT (SELECT val FROM index_counters WHERE id = 'max_ancient_id_'" . $board . "'), (SELECT MAX(doc_id) FROM " . $board . ")
	sql_query_post_index =
}
		";
	}


	function _generate_sphinx_definition_index($board, $path)
	{
		return "
# /" . $board . "/
index " . $board . "_main : main
{
	source = " . $board . "_main
	path = " . $path . "/" . $board . "_main
}

index " . $board . "_ancient : " . $board . "_main
{
	source = " . $board . "_ancient
	path = " . $path . "/" . $board . "_ancient
}

index " . $board . "_delta : " . $board . "_main
{
	source = " . $board . "_delta
	path = " . $path . "/" . $board . "_delta
}
		";
	}

}