<?php

namespace Foolfuuka;

class Controller_Admin_Posts extends \Controller_Admin
{
	
	public function before()
	{
		parent::before();

		if (!\Auth::has_access('boards.edit'))
			\Response::redirect('admin');

		$this->_views['controller_title'] = __('Posts');
	}

	public function action_reports()
	{
		$this->_views['method_title'] = __('Reports');
		
		$theme = \Theme::instance('foolfuuka');
		$theme->set_theme('default');
		
		$reports = \Report::get_all();
		
		foreach ($reports as $key => $report)
		{
			foreach ($reports as $k => $r)
			{
				if ($key < $k && $report->doc_id === $r->doc_id && $report->board_id === $r->board_id)
				{
					unset($reports[$k]);
				}
			}
		}
		
		$this->_views['main_content_view'] = \View::forge('admin/reports/manage', array('theme' => $theme, 'reports' => $reports));
		return \Response::forge(\View::forge('admin/default', $this->_views));
	}
	
	
}

/* end of file posts.php */