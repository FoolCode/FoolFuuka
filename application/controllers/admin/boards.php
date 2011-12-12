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
		$this->viewdata["function_title"] = _('Reports');
		$reports = new Report();
		
		$data["reports"] = $reports;
		
		$this->viewdata["main_content_view"] = $this->load->view("admin/boards/reports.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
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