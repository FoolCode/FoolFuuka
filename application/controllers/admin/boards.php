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

	
	function asagi()
	{
		$this->load->model('asagi');
		
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
			$this->asagi->stop();
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
	sql_query = \ 
		SELECT doc_id, 1 AS board, num, subnum, name, trip, email, media, (CASE parent WHEN 0 THEN num ELSE parent END) AS tnum,        \
		CAST(capcode AS UNSIGNED) AS cap, (media !=	\'\' AND media IS NOT NULL) AS has_image, (subnum != 0) AS is_internal,   \
		spoiler AS is_spoiler, deleted AS is_deleted, sticky as is_sticky, (parent = 0) AS is_op, timestamp, title, comment \
		FROM a LIMIT 1

	sql_attr_uint = num
	sql_attr_uint = subnum
	sql_attr_uint = tnum
	sql_attr_uint = cap
	sql_attr_uint = board
	sql_attr_bool = has_image
	sql_attr_bool = is_internal
	sql_attr_bool = is_spoiler
	sql_attr_bool = is_deleted
	sql_attr_bool = is_sticky
	sql_attr_bool = is_op
	sql_attr_timestamp = timestamp

	sql_query_info =
	sql_query_post_index =
}
		';

		foreach ($this->radix->get_all() as $key => $board)
		{
			if ($board->sphinx)
			{
				$config .= $this->_generate_sphinx_definition_source($board);
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

	charset_table=0..9, A..Z->a..z, _, a..z, _,   \
	U+410..U+42F->U+430..U+44F, U+430..U+44F, \
	U+C0->a, U+C1->a, U+C2->a, U+C3->a, U+C7->c, U+C8->e, U+C9->e, U+CA->e, U+CB->e, U+CC->i, U+CD->i, \
	U+CE->i, U+CF->i, U+D2->o, U+D3->o, U+D4->o, U+D5->o, U+D9->u, U+DA->u, U+DB->u, U+E0->a, U+E1->a, \
	U+E2->a, U+E3->a, U+E7->c, U+E8->e, U+E9->e, U+EA->e, U+EB->e, U+EC->i, U+ED->i, U+EE->i, U+EF->i, \
	U+F2->o, U+F3->o, U+F4->o, U+F5->o, U+F9->u, U+FA->u, U+FB->u, U+FF->y, U+102->a, U+103->a, U+15E->s, \
	U+15F->s, U+162->t, U+163->t, U+178->y,   \
	U+FF10..U+FF19->0..9, U+FF21..U+FF3A->a..z, \
	U+FF41..U+FF5A->a..z, U+4E00..U+9FCF, U+3400..U+4DBF, \
	U+20000..U+2A6DF, U+3040..U+309F, U+30A0..U+30FF, U+3000..U+303F, U+3042->U+3041, \
	U+3044->U+3043, U+3046->U+3045, U+3048->U+3047, U+304A->U+3049, \
	U+304C->U+304B, U+304E->U+304D, U+3050->U+304F, U+3052->U+3051, \
	U+3054->U+3053, U+3056->U+3055, U+3058->U+3057, U+305A->U+3059, \
	U+305C->U+305B, U+305E->U+305D, U+3060->U+305F, U+3062->U+3061, \
	U+3064->U+3063, U+3065->U+3063, U+3067->U+3066, U+3069->U+3068, \
	U+3070->U+306F, U+3071->U+306F, U+3073->U+3072, U+3074->U+3072, \
	U+3076->U+3075, U+3077->U+3075, U+3079->U+3078, U+307A->U+3078, \
	U+307C->U+307B, U+307D->U+307B, U+3084->U+3083, U+3086->U+3085, \
	U+3088->U+3087, U+308F->U+308E, U+3094->U+3046, U+3095->U+304B, \
	U+3096->U+3051, U+30A2->U+30A1, U+30A4->U+30A3, U+30A6->U+30A5, \
	U+30A8->U+30A7, U+30AA->U+30A9, U+30AC->U+30AB, U+30AE->U+30AD, \
	U+30B0->U+30AF, U+30B2->U+30B1, U+30B4->U+30B3, U+30B6->U+30B5, \
	U+30B8->U+30B7, U+30BA->U+30B9, U+30BC->U+30BB, U+30BE->U+30BD, \
	U+30C0->U+30BF, U+30C2->U+30C1, U+30C5->U+30C4, U+30C7->U+30C6, \
	U+30C9->U+30C8, U+30D0->U+30CF, U+30D1->U+30CF, U+30D3->U+30D2, \
	U+30D4->U+30D2, U+30D6->U+30D5, U+30D7->U+30D5, U+30D9->U+30D8, \
	U+30DA->U+30D8, U+30DC->U+30DB, U+30DD->U+30DB, U+30E4->U+30E3, \
	U+30E6->U+30E5, U+30E8->U+30E7, U+30EF->U+30EE, U+30F4->U+30A6, \
	U+30AB->U+30F5, U+30B1->U+30F6, U+30F7->U+30EF, U+30F8->U+30F0, \
	U+30F9->U+30F1, U+30FA->U+30F2, U+30AF->U+31F0, U+30B7->U+31F1, \
	U+30B9->U+31F2, U+30C8->U+31F3, U+30CC->U+31F4, U+30CF->U+31F5, \
	U+30D2->U+31F6, U+30D5->U+31F7, U+30D8->U+31F8, U+30DB->U+31F9, \
	U+30E0->U+31FA, U+30E9->U+31FB, U+30EA->U+31FC, U+30EB->U+31FD, \
	U+30EC->U+31FE, U+30ED->U+31FF, U+FF66->U+30F2, U+FF67->U+30A1, \
	U+FF68->U+30A3, U+FF69->U+30A5, U+FF6A->U+30A7, U+FF6B->U+30A9, \
	U+FF6C->U+30E3, U+FF6D->U+30E5, U+FF6E->U+30E7, U+FF6F->U+30C3, \
	U+FF71->U+30A1, U+FF72->U+30A3, U+FF73->U+30A5, U+FF74->U+30A7, \
	U+FF75->U+30A9, U+FF76->U+30AB, U+FF77->U+30AD, U+FF78->U+30AF, \
	U+FF79->U+30B1, U+FF7A->U+30B3, U+FF7B->U+30B5, U+FF7C->U+30B7, \
	U+FF7D->U+30B9, U+FF7E->U+30BB, U+FF7F->U+30BD, U+FF80->U+30BF, \
	U+FF81->U+30C1, U+FF82->U+30C3, U+FF83->U+30C6, U+FF84->U+30C8, \
	U+FF85->U+30CA, U+FF86->U+30CB, U+FF87->U+30CC, U+FF88->U+30CD, \
	U+FF89->U+30CE, U+FF8A->U+30CF, U+FF8B->U+30D2, U+FF8C->U+30D5, \
	U+FF8D->U+30D8, U+FF8E->U+30DB, U+FF8F->U+30DE, U+FF90->U+30DF, \
	U+FF91->U+30E0, U+FF92->U+30E1, U+FF93->U+30E2, U+FF94->U+30E3, \
	U+FF95->U+30E5, U+FF96->U+30E7, U+FF97->U+30E9, U+FF98->U+30EA, \
	U+FF99->U+30EB, U+FF9A->U+30EC, U+FF9B->U+30ED, U+FF9C->U+30EF, \
	U+FF9D->U+30F3

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
				$config .= $this->_generate_sphinx_definition_index($board,
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
# /" . $board->shortname . "/
source " . $board->shortname . "_main : main
{
	sql_query = SELECT doc_id, " . $board->id . " AS board, num, subnum, name, trip, (ascii(capcode)) as int_capcode, (subnnum != 0) as is_internal, deleted as is_deleted, timestamp, title, comment FROM " . $board->table . " WHERE doc_id >= \$start AND doc_id <= \$end
	sql_query_info = SELECT * FROM " . $board->table . " WHERE doc_id = \$id
	sql_query_range = SELECT (SELECT max_ancient_id FROM `" . $this->db->database . "`." . $this->db->protect_identifiers('boards', TRUE) . " WHERE id = " . $board->id . "), (SELECT MAX(doc_id) FROM " . $board->table . ")
	sql_query_post_index = UPDATE `" . $this->db->database . "`." . $this->db->protect_identifiers('boards', TRUE) . " SET max_indexed_id = \$maxid WHERE id = " . $board->id . "
}

source " . $board->shortname . "_ancient : " . $board->shortname . "_main
{
	sql_query_range = SELECT MIN(doc_id), MAX(doc_id) FROM " . $board->table . "
	sql_query_post_index = UPDATE `" . $this->db->database . "`." . $this->db->protect_identifiers('boards', TRUE) . " SET max_ancient_id =  \$maxid WHERE id = " . $board->id . "
}

source " . $board->shortname . "_delta : " . $board->shortname . "_main
{
	sql_query_range = SELECT (SELECT max_ancient_id FROM `" . $this->db->database . "`." . $this->db->protect_identifiers('boards', TRUE) . " WHERE id = " . $board->id . "), (SELECT MAX(doc_id) FROM " . $board->table . ")
	sql_query_post_index =
}
		";
	}


	function _generate_sphinx_definition_index($board, $path)
	{
		return "
# /" . $board->shortname . "/
index " . $board->shortname . "_main : main
{
	source = " . $board->shortname . "_main
	path = " . $path . "/" . $board->shortname . "_main
}

index " . $board->shortname . "_ancient : " . $board->shortname . "_main
{
	source = " . $board->shortname . "_ancient
	path = " . $path . "/" . $board->shortname . "_ancient
}

index " . $board->shortname . "_delta : " . $board->shortname . "_main
{
	source = " . $board->shortname . "_delta
	path = " . $path . "/" . $board->shortname . "_delta
}
		";
	}

}

/* End of file boards.php */
/* Location: ./application/controllers/admin/boards.php */