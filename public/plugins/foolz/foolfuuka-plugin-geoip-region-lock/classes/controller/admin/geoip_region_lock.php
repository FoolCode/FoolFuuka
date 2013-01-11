<?php

namespace Foolfuuka\Plugins\Geoip_Region_Lock;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Controller_Plugin_Fu_Geoip_Region_Lock_Admin_Geoip_Region_Lock extends \Controller_Admin
{

	public function before()
	{
		if ( ! \Auth::has_access('maccess.admin'))
		{
			\Response::redirect('admin');
		}

		parent::before();

		$this->_views['controller_title'] = __('GeoIP Region Lock');
	}

	public function action_manage()
	{
		$this->_views['method_title'] = 'Manage';

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['paragraph'] = array(
			'type' => 'paragraph',
			'help' => __('You can add board-specific locks by browsing the board preferences.')
		);

		$form['fu.plugins.geoip_region_lock.allow_comment'] = array(
			'label' => _('Countries allowed to post'),
			'type' => 'textarea',
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('If you allow a nation, all other nations won\'t be able to comment.'),

		);

		$form['fu.plugins.geoip_region_lock.disallow_comment'] = array(
			'label' => _('Countries disallowed to post'),
			'type' => 'textarea',
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('Disallowed nations won\'t be able to comment.'),

		);

		$form['fu.plugins.geoip_region_lock.allow_view'] = array(
			'label' => _('Countries allowed to view the site'),
			'type' => 'textarea',
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('If you allow a nation, all other nations won\'t be able to reach the interface.'),

		);

		$form['fu.plugins.geoip_region_lock.disallow_view'] = array(
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

		$form['fu.plugins.geoip_region_lock.allow_logged_in'] = array(
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


		$data['form'] = $form;

		\Preferences::submit_auto($data['form']);

		// create a form
		$this->_views["main_content_view"] = \View::forge("admin/form_creator", $data);
		return \Response::forge(\View::forge("admin/default", $this->_views));
	}

}