<?php

namespace Foolz\Foolframe\Controller\Admin\Plugins;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GeoipRegionLock extends \Foolz\Foolframe\Controller\Admin
{

	public function before(Request $request)
	{
		if ( ! \Auth::has_access('maccess.admin'))
		{
			\Response::redirect('admin');
		}

		parent::before($request);

		$this->param_manager->setParam('controller_title', __('GeoIP Region Lock'));
	}

	public function action_manage()
	{
		$this->param_manager->setParam('method_title', 'Manage');

		$form = [];

		$form['open'] = [
			'type' => 'open'
		];

		$form['paragraph'] = [
			'type' => 'paragraph',
			'help' => __('You can add board-specific locks by browsing the board preferences.')
		];

		$form['foolfuuka.plugins.geoip_region_lock.allow_comment'] = [
			'label' => _('Countries allowed to post'),
			'type' => 'textarea',
			'preferences' => true,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('If you allow a nation, all other nations won\'t be able to comment.'),
		];

		$form['foolfuuka.plugins.geoip_region_lock.disallow_comment'] = [
			'label' => _('Countries disallowed to post'),
			'type' => 'textarea',
			'preferences' => true,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('Disallowed nations won\'t be able to comment.'),
		];

		$form['foolfuuka.plugins.geoip_region_lock.allow_view'] = [
			'label' => _('Countries allowed to view the site'),
			'type' => 'textarea',
			'preferences' => true,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('If you allow a nation, all other nations won\'t be able to reach the interface.'),
		];

		$form['foolfuuka.plugins.geoip_region_lock.disallow_view'] = [
			'label' => _('Countries disallowed to view the site.'),
			'type' => 'textarea',
			'preferences' => true,
			'validation' => 'trim',
			'class' => 'span6',
			'style' => 'height:60px',
			'help' => __('Comma separated list of GeoIP 2-letter nation codes.') . ' ' . __('Disallowed nations won\'t be able to reach the interface.'),
		];

		$form['separator-1'] = [
			'type' => 'separator'
		];

		$form['foolfuuka.plugins.geoip_region_lock.allow_logged_in'] = [
			'label' => _('Allow logged in users to post regardless.'),
			'type' => 'checkbox',
			'preferences' => true,
			'help' => __('Allow all logged in users to post regardless of region lock? (Mods and Admins are always allowed to post)'),
		];

		$form['separator'] = [
			'type' => 'separator'
		];

		$form['submit'] = [
			'type' => 'submit',
			'value' => __('Submit'),
			'class' => 'btn btn-primary'
		];

		$form['close'] = [
			'type' => 'close'
		];


		$data['form'] = $form;

		\Preferences::submit_auto($data['form']);

		// create a form
		$this->builder->createPartial('body', 'form_creator')
			->getParamManager()->setParams($data);

		return new Response($this->builder->build());
	}
}