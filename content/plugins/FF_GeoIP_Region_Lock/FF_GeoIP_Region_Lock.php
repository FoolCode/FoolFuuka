<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class FF_GeoIP_Region_Lock extends Plugins_model
{


	function __construct()
	{
		// KEEP THIS EMPTY, use the initialize_plugin method instead

		parent::__construct();
	}
	
	function initialize_plugin()
	{
		$this->plugins->register_controller_function($this,
			array('admin', 'plugins', 'geoip_region_lock'), 'manage');
		
		$this->plugins->register_admin_sidebar_element('plugins',
			array(
				"content" => array(
					"geoip_region_lock" => array("level" => "admin", "name" => __("GeoIP Region Lock"), "icon" => 'icon-flag'),
				)
			)
		);
		
		$this->plugins->register_hook($this, 'ff_my_controller_after_load_settings', 1, 'block_country_view');
		$this->plugins->register_hook($this, 'fu_post_model_before_comment', 5, 'block_country_comment');
	}
	
	function block_country_comment()
	{
		$allow = get_setting('ff_plugins_geoip_region_lock_allow_comment');
		$disallow = get_setting('ff_plugins_geoip_region_lock_disallow_comment');
		
		if($allow || $disallow)
		{
			$country = geoip_country_code_by_name(inet_dtop($this->input->ip_address()));
		}
		
		if($allow)
		{
			$allow = explode(',', $allow);
			
			foreach($allow as $al)
			{
				if(strtolower(trim($al)) == $country)
					return NULL;
			}
		}
		
		if($disallow)
		{
			$disallow = explode(',', $disallow);
			
			foreach($disallow as $disal)
			{
				if(strtolower(trim($disal)) == $country)
				{
					return array('return' => array('error' => __('Your nation has been blocked from posting.') . 
						' This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com/')
					);
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
			$country = geoip_country_code_by_name(inet_dtop($this->input->ip_address()));
		}
		
		if($allow)
		{
			$allow = explode(',', $allow);
			
			foreach($allow as $al)
			{
				if(strtolower(trim($al)) == $country)
					return NULL;
			}
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