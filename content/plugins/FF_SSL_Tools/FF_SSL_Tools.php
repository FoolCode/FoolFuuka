<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class FF_SSL_Tools extends Plugins_model
{


	function initialize_plugin()
	{
		// this plugin works with indexes that are useless for CLI
		if ($this->input->is_cli_request())
		{
			return TRUE;
		}
		
		// don't add the admin panels if the user is not an admin
		if ($this->auth->is_admin())
		{
			$this->plugins->register_controller_function($this, array('admin', 'plugins', 'ssl_tools'), 'manage');

			$this->plugins->register_admin_sidebar_element('plugins',
			array(
				"content" => array(
					"ssl_tools" => array("level" => "admin", "name" => __("SSL Tools"), "icon" => 'icon-lock'),
					)
				)
			);
		}
	 
		// if we want to run some really early commands, we can run them here!
		$this->plugins->register_hook($this, 'ff_my_controller_after_load_settings', 2, function(){
			$CI = & get_instance();
			
			if(!isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off'))
			{
				if(get_setting('ff_ssl_force_everyone')
					|| (get_setting('ff_ssl_force_for_logged') && $CI->auth->is_logged_in())
					|| (get_setting('ff_ssl_sticky') && $CI->input->cookie('ff_sticky_ssl')))
				{
					// redirect to itself
					header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
					die();
				}
			}
			else
			{
				if(get_setting('ff_ssl_sticky') && !$CI->input->cookie('ff_sticky_ssl'))
				{
					$CI->input->set_cookie('ff_sticky_ssl', '1', 30);
				}
			}
			
		});
		
		$this->plugins->register_hook($this, 'fu_themes_generic_top_nav_buttons', 4, function($top_nav){
			if(get_setting('ff_ssl_enable_top_link') && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off'))
			{
				$CI = & get_instance();
				$top_nav[] = array('href' => 'https' . substr(site_url($CI->uri->uri_string()), 4), 'text' => '<i class="icon-lock"></i> '.__('SSL'));
			}
				return array('return' => $top_nav);
		});
		
		$this->plugins->register_hook($this, 'fu_themes_generic_bottom_nav_buttons', 4, function($top_nav){
			if(get_setting('ff_ssl_enable_bottom_link') && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off'))
			{
				$CI = & get_instance();
				$top_nav[] = array('href' => 'https' . substr(site_url($CI->uri->uri_string()), 4), 'text' => '<i class="icon-lock"></i> '.__('SSL'));
			}
			return array('return' => $top_nav);
		});
	}


	function manage()
	{
		$this->viewdata["function_title"] = __("SSL");

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['fs_ssl_available'] = array(
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => __('Does the server have SSL available (does your site support https:// protocol)?'),
			'sub' => array(
				'ff_ssl_force_for_logged' => array(
					'type' => 'checkbox',
					'preferences' => TRUE,
					'help' => __('Redirect the logged in users to the SSL version of the site')
				),
				'ff_ssl_force_everyone' => array(
					'type' => 'checkbox',
					'preferences' => TRUE,
					'help' => __('Redirect every user to the SSL version of the site')
				),
				'ff_ssl_sticky' => array(
					'type' => 'checkbox',
					'preferences' => TRUE,
					'help' => __('Set a cookie for users that browsed the site with https:// so they get redirected to the https:// version of the site')
				),
				'ff_ssl_enable_top_link' => array(
					'type' => 'checkbox',
					'preferences' => TRUE,
					'help' => __('Show a link to SSL in the header if the user is browsing in http://')
				),
				'ff_ssl_enable_bottom_link' => array(
					'type' => 'checkbox',
					'preferences' => TRUE,
					'help' => __('Show a link to SSL in the footer if the user is browsing in http://')
				),
			)
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