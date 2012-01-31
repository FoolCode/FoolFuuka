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
		$this->viewdata['controller_title'] = '<a href="'.site_url("admin/boards").'">' . _("Boards") . '</a>';;
	}


	function index()
	{
		return $this->manage();
	}


	function manage()
	{
		$this->viewdata["function_title"] = _('Manage');
		$boards = new Board();


		$boards->order_by('name', 'ASC');
		$boards->get();
		$data["boards"] = $boards;

		$this->viewdata["main_content_view"] = $this->load->view("admin/boards/manage.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function reports()
	{
		redirect('admin/reports/');
	}


	function board($shortname = NULL)
	{
		$board = new Board();
		$board->where("shortname", $shortname)->get();
		if ($board->result_count() == 0)
		{
			set_notice('warn', _('Sorry, the board you are looking for does not exist.'));
			$this->manage();
			return false;
		}

		$this->viewdata["function_title"] = '<a href="' . site_url('/admin/boards/manage/') . '">' . _('Manage') . '</a>';

		$data["board"] = $board;

		if ($this->input->post())
		{
			// Prepare for stub change in case we have to redirect instead of just printing the view
			$old_shortname = $board->shortname;
			$board->update_board_db($this->input->post());

			flash_notice('notice', sprintf(_('Updated board information for %s.'), $board->name));
			// Did we change the shortname of the board? We need to redirect to the new page then.
			if (isset($old_shortname) && $old_shortname != $board->shortname)
			{
				redirect('/admin/boards/board/' . $board->shortname);
			}
		}

		$table = ormer($board);
		$table = tabler($table);
		$data['table'] = $table;

		$this->viewdata["main_content_view"] = $this->load->view("admin/boards/board.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function sphinx()
	{
		$this->viewdata["function_title"] = '<a href="' . site_url('/admin/boards/sphinx/') . '">' . _('Sphinx') . '</a>';

		$form = array();

		$form[] = array(
			_('Path'),
			array(
				'type' => 'input',
				'name' => 'fu_sphinx_path',
				'id' => 'sphinx_path',
				'maxlength' => '200',
				'placeholder' => _('/usr/local/sphinx/data/'),
				'preferences' => 'fs_gen',
				'help' => _('Sets the title of your FoOlSlide. This appears in the title of every page.')
			)
		);

		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);
			redirect('admin/preferences/theme');
		}

		// create the form
		$table = tabler($form, FALSE);
		$data['form_title'] = _('Theme');
		$data['table'] = $table;

		$this->viewdata["main_content_view"] = $this->load->view("admin/boards/sphinx.php", $data, TRUE);
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
				path = ?
				docinfo = extern
				mlock = 0
				morphology = none
				min_word_len = ?
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
				$config .= $this->generate_sphinx_definition_source($board->shortname, '');
			}
		}

		$config .= '
			########################################################
			## indexer settings
			########################################################

			indexer
			{
				mem_limit = ?
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
				listen = ?
				listen = ?
				log = ?
				query_log =
				read_timeout = 5
				client_timeout = 300
				max_children = 10
				pid_file = ?
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
				binlog_path = ?
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
				sql_query_range = SELECT MIN(doc_id), MAX(doc_id) FROM " . $board ."
				sql_query_post_index = REPLACE INTO index_counters (id, val) VALUES ('max_ancient_id_" . $board .", \$maxid)
			}

			source " . $board . "_delta : " . $board . "_main
			{
				sql_query_range = SELECT (SELECT val FROM index_counters WHERE id = 'max_ancient_id_'" . $board . "'), (SELECT MAX(doc_id) FROM " . $board .")
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


	function add_new()
	{
		$this->viewdata["function_title"] = '<a href="#">' . _("Add New") . '</a>';
		$board = new Board();
		if ($this->input->post())
		{
			if ($board->add($this->input->post()))
			{
				flash_notice('notice', sprintf(_('The board %s has been added.'), $board->board));
				redirect('/admin/boards/board/' . $board->shortname);
			}
		}

		$table = ormer($board);

		$table = tabler($table, FALSE, TRUE);
		$data["form_title"] = _('Add New') . ' ' . _('Board');
		$data['table'] = $table;

		$this->viewdata["extra_title"][] = _("Board");
		$this->viewdata["main_content_view"] = $this->load->view("admin/form.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}

	function delete($type, $id = 0) {
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

}