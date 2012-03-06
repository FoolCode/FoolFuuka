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


	function _board_structure()
	{
		return array(
			'open' => array(
				'type' => 'open',
				'hidden' => array('id' => NULL)
			),
			'name' => array(
				'type' => 'input',
				'label' => _('Name'),
				'help' => _('Insert the name of the board normally shown as title.'),
				'placeholder' => _('Required'),
				'class' => 'span3',
				'validation' => 'required|max_length[128]'
			),
			'shortname' => array(
				'type' => 'input',
				'label' => _('Shortname'),
				'help' => _('Insert the shorter name of the board. Reserved: "api", "cli", "admin".'),
				'placeholder' => _('Required'),
				'class' => 'span1',
				'validation' => 'required|max_length[5]'
			),
			'separator-1' => array(
				'type' => 'separator'
			),
			'archive' => array(
				'type' => 'checkbox',
				'help' => _('Is this a 4chan archiving board?')
			),
			'thumbnails' => array(
				'type' => 'checkbox',
				'help' => _('Display the thumbnails?')
			),
			'delay_thumbnails' => array(
				'type' => 'checkbox',
				'help' => _('Hide the thumbnails for 24 hours? (for moderation purposes)')
			),
			'sphinx' => array(
				'type' => 'checkbox',
				'help' => _('Use SphinxSearch as search engine?')
			),
			'hidden' => array(
				'type' => 'checkbox',
				'help' => _('Hide the board from public access? (only admins and mods will be able to browse it)')
			),
			'separator-2' => array(
				'type' => 'separator-short'
			),
			'submit' => array(
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => _('Submit')
			),
			'close' => array(
				'type' => 'close'
			),
		);
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
		$data['form'] = $this->_board_structure();

		if (is_null($shortname))
		{
			// creating a new board
			$this->viewdata["function_title"] = _('Creating a new board');

			if ($this->input->post())
			{
				$result = $this->form_validate($data['form']);
				if(isset($result['error']))
				{
					set_notice('warning', $result['error']);
				}
				else
				{
					// we aren't yet finished doing checks, there's still the 
					// database and overriding part
					$this->radix->save($form);
				}
			}

			$this->viewdata["main_content_view"] = $this->load->view('admin/form_creator', $data, TRUE);
			$this->load->view('admin/default', $this->viewdata);
			
			return TRUE;
		}

		$board = $this->radix->get_by_shortname($shortname);
		if ($board === FALSE)
		{
			show_404();
		}

		$data['object'] = $board;

		$this->viewdata["function_title"] = _('Editing board:') . ' ' . $board->shortname;

		$data["board"] = $board;

		if ($this->input->post())
		{
			// Prepare for stub change in case we have to redirect instead of just printing the view
			$old_shortname = $board->shortname;
			$board->update_board_db($this->input->post());

			flash_notice('notice',
				sprintf(_('Updated board information for %s.'), $board->name));
			// Did we change the shortname of the board? We need to redirect to the new page then.
			if (isset($old_shortname) && $old_shortname != $board->shortname)
			{
				redirect('/admin/boards/board/' . $board->shortname);
			}
		}

		$this->viewdata["main_content_view"] = $this->load->view('admin/form_creator', $data, TRUE);
		$this->load->view('admin/default', $this->viewdata);
	}


	function delete($type, $id = 0)
	{
		if (!isAjax())
		{
			$this->output->set_output(_('You can\'t delete from outside the admin panel through this link.'));
			log_message("error", "Controller: board.php/remove: failed serie removal");
			return false;
		}
		$id = intval($id);

		switch ($type)
		{
			case("board"):
				$board = new Board();
				$board->where('id', $id)->get();
				$title = $board->name;
				if (!$board->remove())
				{
					flash_notice('error', sprintf(_('Failed to delete the board %s.'), $title));
					log_message("error", "Controller: board.php/remove: failed board removal");
					echo json_encode(array('href' => site_url("admin/boards/manage")));
					return false;
				}
				flash_notice('notice', sprintf(_('The board %s has been deleted.'), $title));
				$this->output->set_output(json_encode(array('href' => site_url("admin/boards/manage"))));
				break;
		}
	}


	function sphinx()
	{
		$this->viewdata["function_title"] = '<a href="' . site_url('/admin/boards/sphinx/') . '">' . _('Sphinx') . '</a>';

		$form = array();

		$form[] = array(
			_('Listen (Sphinx)'),
			array(
				'type' => 'input',
				'name' => 'fu_sphinx_listen',
				'id' => 'sphinx_listen',
				'maxlength' => '200',
				'placeholder' => _('127.0.0.1:9312'),
				'preferences' => 'fs_gen',
			)
		);

		$form[] = array(
			_('Listen (MySQL)'),
			array(
				'type' => 'input',
				'name' => 'fu_sphinx_listen_mysql',
				'id' => 'sphinx_listen_mysql',
				'maxlength' => '200',
				'placeholder' => _('127.0.0.1:9306'),
				'preferences' => 'fs_gen',
				'help' => _('Sets the title of your FoOlSlide. This appears in the title of every page.')
			)
		);

		$form[] = array(
			_('Working Directory'),
			array(
				'type' => 'input',
				'name' => 'fu_sphinx_path',
				'id' => 'sphinx_path',
				'maxlength' => '200',
				'placeholder' => _('/usr/local/sphinx/var'),
				'preferences' => 'fs_gen',
				'help' => _('Sets the title of your FoOlSlide. This appears in the title of every page.')
			)
		);

		$form[] = array(
			_('Minimum Word Length'),
			array(
				'type' => 'input',
				'name' => 'fu_sphinx_min_word_len',
				'id' => 'sphinx_min_word_len',
				'maxlength' => '200',
				'placeholder' => _('3'),
				'preferences' => 'fs_gen',
				'help' => _('Sets the title of your FoOlSlide. This appears in the title of every page.')
			)
		);

		$form[] = array(
			_('Memory Limit'),
			array(
				'type' => 'input',
				'name' => 'fu_sphinx_mem_limit',
				'id' => 'sphinx_mem_limit',
				'maxlength' => '200',
				'placeholder' => _('2047M'),
				'preferences' => 'fs_gen',
				'help' => _('Sets the title of your FoOlSlide. This appears in the title of every page.')
			)
		);

		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);
			redirect('admin/boards/sphinx');
		}

		// create the form
		$table = tabler($form, FALSE);
		$data['form_title'] = _('Theme');
		$data['table'] = $table;

		$data['config'] = str_replace("\t\t\t", '', $this->generate_sphinx_config());

		$this->viewdata["main_content_view"] = $this->load->view("admin/boards/sphinx.php",
			$data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function generate_sphinx_config()
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
				$config .= $this->generate_sphinx_definition_source($board->shortname);
			}
		}

		$config .= '
			########################################################
			## index definition
			########################################################

			index main
			{
				source = main
				path = ' . get_setting('fu_sphinx_path') . '/data/main
				docinfo = extern
				mlock = 0
				morphology = none
				min_word_len = ' . ((get_setting('fu_sphinx_mem_limit'))
					? get_setting('fu_sphinx_mem_limit') : '2047M') . '
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
				$config .= $this->generate_sphinx_definition_index($board->shortname,
					((get_setting('fu_sphinx_path')) ? get_setting('fu_sphinx_path') : '/usr/local/sphinx/var' ) . '/data');
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
				log = ' . ((get_setting('fu_sphinx_path'))
					? get_setting('fu_sphinx_path') : '/usr/local/sphinx/var' ) . '/log/searchd.log
				query_log = ' . ((get_setting('fu_sphinx_path'))
					? get_setting('fu_sphinx_path') : '/usr/local/sphinx/var' ) . '/log/query.log
				read_timeout = 5
				client_timeout = 300
				max_children = 10
				pid_file = ' . ((get_setting('fu_sphinx_path'))
					? get_setting('fu_sphinx_path') : '/usr/local/sphinx/var' ) . '/searchd.pid
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
				binlog_path = ' . ((get_setting('fu_sphinx_path'))
					? get_setting('fu_sphinx_path') : '/usr/local/sphinx/var' ) . '/data
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


	function generate_sphinx_definition_source($board)
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


	function generate_sphinx_definition_index($board, $path)
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