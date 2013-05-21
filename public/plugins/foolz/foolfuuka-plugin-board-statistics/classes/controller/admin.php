<?php

namespace Foolz\Foolframe\Controller\Admin\Plugins;

use \Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics as BS;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BoardStatistics extends \Foolz\Foolframe\Controller\Admin
{
	public function before(Request $request)
	{
		if ( ! \Auth::has_access('maccess.admin'))
		{
			\Response::redirect('admin');
		}

		parent::before($request);

		$this->param_manager->setParam('controller_title', __('Plugins'));
	}

	protected function structure()
	{
		$arr = [
			'open' => [
				'type' => 'open',
			],
			'foolfuuka.plugins.board_statistics.enabled' => [
				'type' => 'checkbox_array',
				'label' => 'Enabled statistics',
				'help' => __('Select the statistics to enable. Some might be too slow to process, so you should disable them. Some statistics don\'t use extra processing power so they are enabled by default.'),
				'checkboxes' => []
			],
			'separator-2' => [
				'type' => 'separator-short'
			],
			'submit' => [
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => __('Submit')
			],
			'close' => [
				'type' => 'close'
			],
		];

		foreach(BS::getStats() as $key => $stat)
		{
			$arr['foolfuuka.plugins.board_statistics.enabled']['checkboxes'][] = [
				'type' => 'checkbox',
				'label' => $key,
				'help' => sprintf(__('Enable %s statistics'), $stat['name']),
				'array_key' => $key,
				'preferences' => true,
			];
		}

		return $arr;
	}

	public function action_manage()
	{
		$this->param_manager->setParam('method_title', [__('FoolFuuka'), __("Board Statistics"),__('Manage')]);

		$data['form'] = $this->structure();

		\Preferences::submit_auto($data['form']);

		// create a form
		$this->builder->createPartial('body', 'form_creator')
			->getParamManager()->setParams($data);

		return new Response($this->builder->build());
	}
}