<?php

namespace Foolz\Foolfuuka\Controller\Admin;

class Posts extends \Foolz\Foolframe\Controller\Admin
{

	/**
	 * Check that the user is an admin or a moderator and
	 */
	public function before()
	{
		parent::before();

		if ( ! \Auth::has_access('comment.reports'))
		{
			\Response::redirect('admin');
		}

		$this->_views['controller_title'] = __('Posts');
	}

	/**
	 * Lists the post reports
	 *
	 * @return  Response
	 */
	public function action_reports()
	{
		$this->_views['method_title'] = __('Reports');

		$theme = \Theme::forge('foolfuuka');
		$theme->set_module('foolz/foolfuuka');
		$theme->set_theme('default');
		$theme->set_layout('chan');
		$theme->bind('modifiers', array(
			'post_show_board_name' => true,
			'post_show_view_button' => true
		));

		$reports = \Report::getAll();

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

		$pass = \Cookie::get('reply_password');
		$name = \Cookie::get('reply_name');
		$email = \Cookie::get('reply_email');

		// get the password needed for the reply field
		if(!$pass || $pass < 3)
		{
			$pass = \Str::random('alnum', 7);
			\Cookie::set('reply_password', $pass, 60*60*24*30);
		}

		// KEEP THIS IN SYNC WITH THE ONE IN THE CHAN CONTROLLER
		$backend_vars = array(
			'user_name' => $name,
			'user_email' => $email,
			'user_pass' => $pass,
			'site_url'  => \Uri::base(),
			'default_url'  => \Uri::base(),
			'archive_url'  => \Uri::base(),
			'system_url'  => \Uri::base(),
			'api_url'   => \Uri::base(),
			'cookie_domain' => \Foolz\Config\Config::get('foolz/foolframe', 'package', 'config.cookie_domain'),
			'cookie_prefix' => \Foolz\Config\Config::get('foolz/foolframe', 'package', 'config.cookie_prefix'),
			'selected_theme' => $theme->get_selected_theme(),
			'csrf_token_key' => \Config::get('security.csrf_token_key'),
			'images' => array(
				'banned_image' => \Uri::base().$theme->fallback_asset('images/banned-image.png'),
				'banned_image_width' => 150,
				'banned_image_height' => 150,
				'missing_image' => \Uri::base().$theme->fallback_asset('images/missing-image.jpg'),
				'missing_image_width' => 150,
				'missing_image_height' => 150,
			),
			'gettext' => array(
				'submit_state' => __('Submitting'),
				'thread_is_real_time' => __('This thread is being displayed in real time.'),
				'update_now' => __('Update now')
			)
		);

		$this->_views['main_content_view'] = \View::forge('foolz/foolfuuka::admin/reports/manage', array(
			'backend_vars' => $backend_vars,
			'theme' => $theme, 'reports' => $reports
		));
		return \Response::forge(\View::forge('foolz/foolframe::admin/default', $this->_views));
	}


	public function action_bans($page = 1)
	{
		$this->_views["method_title"] = __('Manage bans');

		if ($page < 1 || ! ctype_digit((string) $page))
		{
			$page = 1;
		}

		$bans = \Ban::getPagedBy('start', 'desc', $page);

		$this->_views['main_content_view'] = \View::forge('foolz/foolfuuka::admin/reports/bans', array(
			'bans' => $bans,
			'page' => $page,
			'page_url' => \Uri::create('admin/posts/bans')
		));
		return \Response::forge(\View::forge('foolz/foolframe::admin/default', $this->_views));
	}

	public function action_appeals($page = 1)
	{
		$this->_views["method_title"] = __('Manage pending appeals');

		if ($page < 1 || ! ctype_digit((string) $page))
		{
			$page = 1;
		}

		$bans = \Ban::getAppealsPagedBy('start', 'desc', $page);

		$this->_views['main_content_view'] = \View::forge('foolz/foolfuuka::admin/reports/bans', array(
			'bans' => $bans,
			'page' => $page,
			'page_url' => \Uri::create('admin/posts/appeals')
		));
		return \Response::forge(\View::forge('foolz/foolframe::admin/default', $this->_views));
	}

	public function action_find_ban($ip = null)
	{
		$this->_views["method_title"] = __('Manage bans');

		if (\Input::post('ip'))
		{
			\Response::redirect('admin/posts/find_ban/'.trim(\Input::post('ip')));
		}

		if ($ip === null)
		{
			throw new \HttpNotFoundException;
		}

		$ip = trim($ip.'.'.\Input::extension());

		if ( ! filter_var($ip, FILTER_VALIDATE_IP))
		{
			throw new \HttpNotFoundException;
		}

		try
		{
			$bans = \Ban::getByIp(\Inet::ptod($ip));
		}
		catch (\Foolz\Foolfuuka\Model\BanException $e)
		{
			$bans = array();
		}


		$this->_views['main_content_view'] = \View::forge('foolz/foolfuuka::admin/reports/bans', array('bans' => $bans));
		return \Response::forge(\View::forge('foolz/foolframe::admin/default', $this->_views));
	}

	public function action_ban_manage($action, $id)
	{
		try
		{
			$ban = \Ban::getById($id);
		}
		catch (\Foolz\Foolfuuka\Model\BanException $e)
		{
			throw new \HttpNotFoundException;
		}

		if (\Input::post() && ! \Security::check_token())
		{
			\Notices::set('warning', __('The security token wasn\'t found. Try resubmitting.'));
		}
		elseif (\Input::post())
		{
			switch ($action)
			{
				case 'unban':
					$ban->delete();
					\Notices::set_flash('success', \Str::tr(__('The poster with IP :ip has been unbanned.'), array('ip' => \Inet::dtop($ban->ip))));
					\Response::redirect('admin/posts/bans');
					break;

				case 'reject_appeal':
					$ban->appealReject();
					\Notices::set_flash('success', \Str::tr(__('The appeal of the poster with IP :ip has been rejected.'), array('ip' => \Inet::dtop($ban->ip))));
					\Response::redirect('admin/posts/bans');
					break;

				default:
					throw new \HttpNotFoundException;
			}
		}

		switch ($action)
		{
			case 'unban':
				$this->_views["method_title"] = __('Unbanning').' '.\Inet::dtop($ban->ip);
				$data['alert_level'] = 'warning';
				$data['message'] = __('Do you want to unban this user?');
				break;

			case 'reject_appeal':
				$this->_views["method_title"] = __('Rejecting appeal for').' '.\Inet::dtop($ban->ip);
				$data['alert_level'] = 'warning';
				$data['message'] = __('Do you want to reject the appeal of this user? He won\'t be able to appeal again.');
				break;

			default:
				throw new \HttpNotFoundException;
		}

		$this->_views["main_content_view"] = \View::forge('foolz/foolframe::admin/confirm', $data);
		return \Response::forge(\View::forge('foolz/foolframe::admin/default', $this->_views));

	}

}