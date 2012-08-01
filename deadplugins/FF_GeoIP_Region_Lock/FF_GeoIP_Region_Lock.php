<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');


class FF_GeoIP_Region_Lock extends Plugins_model
{


	function initialize_plugin()
	{
		// don't add the admin panels if the user is not an admin
		if ($this->auth->is_admin())
		{
			Plugins::register_controller_function($this,
				array('admin', 'plugins', 'geoip_region_lock'), 'manage');

			Plugins::register_admin_sidebar_element('plugins',
				array(
					"content" => array(
						"geoip_region_lock" => array(
							"level" => "admin", 
							"name" => __("GeoIP Region Lock"), 
							"icon" => 'icon-flag'
						),
					)
				)
			);
		}

		if (!(get_setting('ff_plugins_geoip_region_lock_allow_logged_in') && $this->auth->is_logged_in())
			|| !Auth::has_access('maccess.mod'))
		{
			Plugins::register_hook($this, 'ff_my_controller_after_load_settings', 1, 'block_country_view');
			Plugins::register_hook($this, 'fu_post_model_replace_comment', 5, 'block_country_comment');
		}

		Plugins::register_hook($this, 'fu_radix_model_structure_alter', 8, function($structure){
			$structure['plugin_geo_ip_region_lock_allow_comment'] = array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'type' => 'input',
				'class' => 'span3',
				'label' => 'Nations allowed to post comments',
				'help' => __('Comma separated list of GeoIP 2-letter nation codes.'),
				'default_value' => FALSE
			);

			$structure['plugin_geo_ip_region_lock_disallow_comment'] = array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'type' => 'input',
				'class' => 'span3',
				'label' => 'Nations disallowed to post comments',
				'help' => __('Comma separated list of GeoIP 2-letter nation codes.'),
				'default_value' => FALSE
			);

			return array('return' => $structure);
		});
	}

	function block_country_comment($board)
	{
		// globally allowed and disallowed
		$allow = get_setting('ff_plugins_geoip_region_lock_allow_comment');
		$disallow = get_setting('ff_plugins_geoip_region_lock_disallow_comment');

		$board_allow = trim($board->plugin_geo_ip_region_lock_allow_comment, " ,");
		$board_disallow = trim($board->plugin_geo_ip_region_lock_disallow_comment, " ,");

		// allow board settings to override global
		if ($board_allow || $board_disallow)
		{
			$allow = $board_allow;
			$disallow = $board_disallow;
		}

		if($allow || $disallow)
		{
			$country = strtolower(geoip_country_code_by_name(inet_dtop($this->input->ip_address())));

			if($allow)
			{
				$allow = array_filter(explode(',', $allow));

				foreach($allow as $al)
				{
					if(strtolower(trim($al)) == $country)
						return NULL;
				}

				return array('return' => array(
					'error' => __('Your nation has been blocked from posting.') .
						'<br/><br/>This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com/'
				)
				);
			}

			if($disallow)
			{
				$disallow = array_filter(explode(',', $disallow));

				foreach($disallow as $disal)
				{
					if(strtolower(trim($disal)) == $country)
					{
						return array('return' => array(
							'error' => __('Your nation has been blocked from posting.') .
								'<br/><br/>This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com/'
						)
						);
					}
				}
			}
		}

		return NULL;
	}


	function block_country_view()
	{
		$allow = get_setting('ff_plugins_geoip_region_lock_allow_view');
		$disallow = get_setting('ff_plugins_geoip_region_lock_disallow_view');

		if($allow || $disallow)
		{
			$country = strtolower(geoip_country_code_by_name(inet_dtop($this->input->ip_address())));
		}

		if($allow)
		{
			$allow = explode(',', $allow);

			foreach($allow as $al)
			{
				if(strtolower(trim($al)) == $country)
					return NULL;
			}

			return show_404();
		}

		if($disallow)
		{
			$disallow = explode(',', $disallow);

			foreach($disallow as $disal)
			{
				if(strtolower(trim($disal)) == $country)
					return show_404();
			}
		}

		return NULL;
	}


	function manage()
	{
		$this->viewdata["function_title"] = __("GeoIP Region Lock");

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['paragraph'] = array(
			'type' => 'paragraph',
			'help' => __('You can add board-specific locks by browsing the board preferences.')
		);

		$form['ff_plugins_geoip_region_lock_allow_comment'] = array(
			'label' => _('Countries allowed to post'),
			'type' => 'textarea',
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('If you allow a nation, all other nations won\'t be able to comment.'),

		);

		$form['ff_plugins_geoip_region_lock_disallow_comment'] = array(
			'label' => _('Countries disallowed to post'),
			'type' => 'textarea',
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('Disallowed nations won\'t be able to comment.'),

		);

		$form['ff_plugins_geoip_region_lock_allow_view'] = array(
			'label' => _('Countries allowed to view the site'),
			'type' => 'textarea',
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('If you allow a nation, all other nations won\'t be able to reach the interface.'),

		);

		$form['ff_plugins_geoip_region_lock_disallow_view'] = array(
			'label' => _('Countries disallowed to view the site.'),
			'type' => 'textarea',
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('Disallowed nations won\'t be able to reach the interface.'),

		);

		$form['separator-1'] = array(
			'type' => 'separator'
		);

		$form['ff_plugins_geoip_region_lock_allow_logged_in'] = array(
			'label' => _('Allow logged in users to post regardless.'),
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => __('Allow all logged in users to post regardless of region lock? (Mods and Admins are always allowed to post)'),

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

		$this->preferences->submit_auto($form);

		$data['form'] = $form;

		// create a form
		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator", $data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}

}