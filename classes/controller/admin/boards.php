<?php

namespace Foolfuuka;

class Controller_Admin_Boards extends \Controller_Admin
{


	public function before()
	{
		parent::before();

		if (!\Auth::has_access('boards.edit'))
			\Response::redirect('admin');

		$this->_views['controller_title'] = __('Boards');
	}


	public function action_manage()
	{
		\Radix::preload(true);

		$this->_views['method_title'] = __('Login');
		$this->_views['main_content_view'] = \View::forge('admin/boards/manage', array('boards' => \Radix::get_all()));

		return \Response::forge(\View::forge('admin/default', $this->_views));
	}


	public function action_edit()
	{
		\Radix::preload(true);

		$this->_views['method_title'] = __('Login');
		$this->_views['main_content_view'] = \View::forge('admin/form_creator', array('form' => \Radix::get_all()));

		return \Response::forge(\View::forge('admin/default', $this->_views));
	}


	public function action_board($shortname = NULL)
	{
		$data['form'] = \Radix::structure();

		if (\Input::post())
		{
			$result = \Validation::form_validate($data['form']);
			if (isset($result['error']))
			{
				\Notices::set('warning', $result['error']);
			}
			else
			{
				// it's actually fully checked, we just have to throw it in DB
				\Radix::save($result['success']);
				if (is_null($shortname))
				{
					\Notices::set_flash('success', __('New board created!'));
					\Response::redirect('admin/boards/board/'.$result['success']['shortname']);
				}
				else if ($shortname != $result['success']['shortname'])
				{
					// case in which letter was changed
					\Notices::set_flash('success', __('Board information updated.'));
					\Response::redirect('admin/boards/board/'.$result['success']['shortname']);
				}
				else
				{
					\Notices::set('success', __('Board information updated.'));
				}
			}
		}

		$board = \Radix::get_by_shortname($shortname);
		if ($board === FALSE)
		{
			throw new \HttpNotFoundException;
		}

		$data['object'] = $board;

		$this->_views["method_title"] = __('Editing board:').' '.$board->shortname;
		$this->_views["main_content_view"] = \View::forge('admin/form_creator', $data);

		if (!$board->sphinx && !$board->myisam_search)
		{
			$this->_views["main_content_view"] = '
				<div class="alert">
					<a class="btn btn-warning" href="'.\Uri::create('admin/boards/search_table/create/'.$board->id).'">
						'.__('Create search table').'
					</a> '.__('This board doesn\'t have the search table. You can create it by follwing this button.').'
				</div>
			'.$this->_views["main_content_view"];
		}

		if ($board->sphinx && $board->myisam_search)
		{
			$this->_views["main_content_view"] = '
				<div class="alert">
					<a class="btn btn-warning" href="'.\Uri::create('admin/boards/search_table/remove/'.$board->id).'">
						'.__('Remove search table').'
					</a> '.__('You are using Sphinx Search for this board, so you can remove the search table.').'
				</div>
			'.$this->_views["main_content_view"];
		}

		return \Response::forge(\View::forge('admin/default', $this->_views));
	}


	function action_add_new()
	{
		$data['form'] = \Radix::structure();

		if (\Input::post())
		{
			$result = \Validation::form_validate($data['form']);
			if (isset($result['error']))
			{
				\Notices::set('warning', $result['error']);
			}
			else
			{
				// it's actually fully checked, we just have to throw it in DB
				\Radix::save($result['success']);
				\Notices::set_flash('success', __('New board created!'));
				\Response::redirect('admin/boards/board/'.$result['success']['shortname']);
			}
		}

		// the actual POST is in the board() function
		$data['form']['open']['action'] = \Uri::create('admin/boards/add_new');

		// panel for creating a new board
		$this->_views["method_title"] = __('Creating a new board');
		$this->_views["main_content_view"] = \View::forge('admin/form_creator', $data);

		return \Response::forge(\View::forge('admin/default', $this->_views));
	}


	function action_search_table($type = FALSE, $id = 0)
	{
		$board = \Radix::get_by_id($id);
		if ($board == FALSE)
		{
			throw new \HttpNotFoundException;
		}

		if (\Input::post())
		{
			switch ($type)
			{
				case("create"):
					if (!$this->radix->create_search($board))
					{
						\Notices::set_flash('error', sprintf(__('Failed to create the search table for the board %s.'), $board->shortname));
					}
					else
					{
						\Notices::set_flash('success',
							sprintf(__('The search table for the board %s has been created.'), $board->shortname));
					}
					\Response::redirect('admin/boards/board/'.$board->shortname);
					break;

				case("remove"):
					if (!\Radix::remove_search($board))
					{
						\Notices::set_flash('error', sprintf(__('Failed to remove the search table for the board %s.'), $board->shortname));
					}
					else
					{
						\Notices::set_flash('success',
							sprintf(__('The search table for the board %s has been removed.'), $board->shortname));
					}
					\Response::redirect('admin/boards/board/'.$board->shortname);
					break;
			}
		}

		switch ($type)
		{
			case('create'):
				$this->_views["model_title"] = __('Creating search table for board:').' '.$board->shortname;
				$data['alert_level'] = 'warning';
				$data['message'] =
					'<strong>'.__('Do you want to create the search table for this board?').'</strong><br/>'.
					__('Creating the search table can take time if you have a board with even just 100.000 entries.').
					'<br/>'.
					__('Normally, even if the page times out, the database will keep building it.').
					'<br/>'.
					__('To make sure your search table is fully created, you can execute the following via the command line of your server.').
					'<br/>'.
					'<pre>$ cd '.FCPATH.'
$ php index.php cli database create_search '.$board->shortname.'</pre>'.
					__('For very large boards, past a few millions of entries, this would could hours: you should use SphinxSearch instead, or anyway you should use the command line.');

				$this->_views["main_content_view"] = \View::forge('admin/confirm', $data);
				break;

			case('remove'):
				$this->_views["function_title"] = __('Removing search table for board:').' '.$board->shortname;
				$data['alert_level'] = 'warning';
				$data['message'] =
					'<strong>'.__('Do you want to remove the search table for this board?').'</strong><br/>'.
					__('The search table can be created at any time, though it can take a while to create if the board is large.');

				break;
		}

		$this->_views["main_content_view"] = \View::forge('admin/confirm', $data);
		return \Response::forge(\View::forge('admin/default', $this->_views));
	}


	function action_delete($type = FALSE, $id = 0)
	{
		$board = \Radix::get_by_id($id);
		if ($board == FALSE)
		{
			throw new \HttpNotFoundException;
		}

		if (\Input::post())
		{
			switch ($type)
			{
				case("board"):
					if (!\Radix::remove($id))
					{
						\Notices::set_flash('error', sprintf(__('Failed to delete the board %s.'), $board->shortname));
						\Response::redirect('admin/boards/manage');
					}
					\Notices::set_flash('success', sprintf(__('The board %s has been deleted.'), $board->shortname));
					\Response::redirect('admin/boards/manage');
					break;
			}
		}

		switch ($type)
		{
			case('board'):
				$this->_views["function_title"] = __('Removing board:').' '.$board->shortname;
				$data['alert_level'] = 'warning';
				$data['message'] = __('Do you really want to remove the board and all its data?').
					'<br/>'.
					__('Notice: due to its size, you will have to remove the image directory manually. The directory will have the "_removed" suffix. You can remove all the leftover "_removed" directories with the following command:').
					' <code>php index.php cli boards remove_leftover_dirs</code>';

				$this->_views["main_content_view"] = \View::forge('admin/confirm', $data);
				return \Response::forge(\View::forge('admin/default', $this->_views));
		}
	}


	function action_preferences()
	{
		$this->_views["method_title"] = __("Preferences");

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['fu.boards.directory'] = array(
			'type' => 'input',
			'label' => __('Boards directory'),
			'preferences' => TRUE,
			'help' => __('Overrides the default path to the boards directory (Example: /var/www/foolfuuka/boards)')
		);

		$form['fu.boards.url'] = array(
			'type' => 'input',
			'label' => __('Boards URL'),
			'preferences' => TRUE,
			'help' => __('Overrides the default url to the boards folder (Example: http://foolfuuka.site.com/there/boards)')
		);

		$form['fu.boards.db'] = array(
			'type' => 'input',
			'label' => __('Boards database'),
			'preferences' => TRUE,
			'help' => __('Overrides the default database. You should point it to your Asagi database if you have a separate one.')
		);

		$form['fu.boards.media_balancers'] = array(
			'type' => 'textarea',
			'label' => __('Media load balancers'),
			'preferences' => TRUE,
			'help' => __('Facultative. One per line the URLs where your images are reachable.'),
			'class' => 'span6'
		);

		$form['fu.boards.media_balancers_https'] = array(
			'type' => 'textarea',
			'label' => __('HTTPS media load balancers'),
			'preferences' => TRUE,
			'help' => __('Facultative. One per line the URLs where your images are reachable. This is used when the site is loaded via HTTPS protocol, and if empty it will fall back to HTTP media load balancers.'),
			'class' => 'span6'
		);

		$form['separator-2'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => __('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);

		\Preferences::submit_auto($form);

		$data['form'] = $form;

		// create a form
		$this->_views["main_content_view"] = \View::forge("admin/form_creator", $data);
		return \Response::forge(\View::forge("admin/default", $this->_views));
	}


	function action_sphinx()
	{
		$this->_views["model_title"] = 'Sphinx';

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['fu.sphinx.global'] = array(
			'type' => 'checkbox',
			'label' => 'Global SphinxSearch',
			'placeholder' => 'FoOlFuuka',
			'preferences' => TRUE,
			'help' => __('Activate Sphinx globally (enables crossboard search)')
		);

		$form['fu.sphinx.listen'] = array(
			'type' => 'input',
			'label' => 'Listen (Sphinx)',
			'placeholder' => FOOL_PREF_SPHINX_LISTEN,
			'preferences' => TRUE,
			'help' => __('Set the address and port to your Sphinx instance.'),
			'class' => 'span2',
			'validation' => 'trim|max_length[48]',
			'validation_func' => function($input, $form)
			{
				if (strpos($input['fu.sphinx.listen'], ':') === FALSE)
				{
					return array(
						'error_code' => 'MISSING_COLON',
						'error' => __('The Sphinx listening address and port aren\'t formatted correctly.')
					);
				}

				$sphinx_ip_port = explode(':', $input['fu.sphinx.listen']);

				if (count($sphinx_ip_port) != 2)
				{
					return array(
						'error_code' => 'WRONG_COLON_NUMBER',
						'error' => __('The Sphinx listening address and port aren\'t formatted correctly.')
					);
				}

				if (!intval($sphinx_ip_port[1]) <= 0)
				{
					return array(
						'error_code' => 'PORT_NOT_A_NUMBER',
						'error' => __('The port specified isn\'t a valid number.')
					);
				}

				$connection = @\SphinxQL::set_server($sphinx_ip_port[0], $sphinx_ip_port[1]);

				if ($connection === FALSE)
				{
					return array(
						'warning_code' => 'CONNECTION_NOT_ESTABLISHED',
						'warning' => __('The Sphinx server couldn\'t be contacted at the specified address and port.')
					);
				}

				return array('success' => TRUE);
			}
		);

		$form['fu.sphinx.listen_mysql'] = array(
			'type' => 'input',
			'label' => 'Listen (MySQL)',
			'placeholder' => FOOL_PREF_SPHINX_LISTEN_MYSQL,
			'preferences' => TRUE,
			'validation' => 'trim|max_length[48]',
			'help' => __('Set the address and port to your MySQL instance.'),
			'class' => 'span2'
		);

		$form['fu.sphinx.connection_flags'] = array(
			'type' => 'input',
			'label' => 'Connection Flags (MySQL)',
			'placeholder' => 0,
			'preferences' => TRUE,
			'validation' => 'trim|is_natural',
			'help' => __('Set the MySQL client connection flags to enable compression, SSL, or secure connection.'),
			'class' => 'span2'
		);

		$form['fu.sphinx.dir'] = array(
			'type' => 'input',
			'label' => 'Working Directory',
			'placeholder' => FOOL_PREF_SPHINX_DIR,
			'preferences' => TRUE,
			'help' => __('Set the working directory to your Sphinx working directory.'),
			'class' => 'span3',
			'validation' => 'trim',
			'validation_func' => function($input, $form)
			{
				if (!file_exists($input['fu.sphinx.dir']))
				{
					return array(
						'error_code' => 'SPHINX_WORKING_DIR_NOT_FOUND',
						'error' => __('Couldn\'t find the Sphinx working directory.')
					);
				}

				return array('success' => TRUE);
			}
		);

		$form['fu.sphinx.min_word_len'] = array(
			'type' => 'input',
			'label' => 'Minimum Word Length',
			'placeholder' => FOOL_PREF_SPHINX_MIN_WORD,
			'preferences' => TRUE,
			'help' => __('Set the minimum word length indexed by Sphinx.'),
			'class' => 'span1',
			'validation' => 'trim|is_natural_no_zero'
		);

		$form['fu.sphinx.mem_limit'] = array(
			'type' => 'input',
			'label' => 'Memory Limit',
			'placeholder' => FOOL_PREF_SPHINX_MEMORY,
			'validation' => 'is_natural|greater_than[256]',
			'preferences' => TRUE,
			'help' => __('Set the memory limit for the Sphinx instance in MegaBytes.'),
			'class' => 'span1'
		);

		$form['fu.sphinx.max_children'] = array(
			'type' => 'input',
			'label' => 'Max Children',
			'placeholder' => 0,
			'validation' => 'trim|is_natural',
			'preferences' => TRUE,
			'help' => __('Set the maximum number of children to fork for searchd.'),
			'class' => 'span1'
		);

		$form['fu.sphinx.max_matches'] = array(
			'type' => 'input',
			'label' => 'Max Matches',
			'placeholder' => 5000,
			'validation' => 'trim|is_natural',
			'preferences' => TRUE,
			'help' => __('Set the maximum amount of matches the search daemon keeps in RAM for each index and results returned to the client.'),
			'class' => 'span1'
		);

		$form['separator'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => __('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);

		\Preferences::submit_auto($form);

		// create the form
		$data['form'] = $form;

		$this->_views["main_content_view"] = \View::forge("admin/form_creator", $data);
		$this->_views["main_content_view"] .= '<pre>'.\SphinxQL::generate_sphinx_config(\Radix::get_all()).'</pre>';
		return \Response::forge(\View::forge("admin/default", $this->_views));
	}

}

/* end of file boards.php */