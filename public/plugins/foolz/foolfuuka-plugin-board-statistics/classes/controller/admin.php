<?php

namespace Foolz\Foolframe\Controller\Admin\Plugins;

use \Foolz\Foolfuuka\Plugins\BoardStatistics\Model\BoardStatistics as BS;

class BoardStatistics extends \Foolz\Foolframe\Controller\Admin
{
	public function before()
	{
		if ( ! \Auth::has_access('maccess.admin'))
		{
			\Response::redirect('admin');
		}

		parent::before();
	}


	protected function structure()
	{
		$arr = array(
			'open' => array(
				'type' => 'open',
			),
			'fu.plugins.board_statistics.enabled' => array(
				'type' => 'checkbox_array',
				'label' => 'Enabled statistics',
				'help' => __('Select the statistics to enable. Some might be too slow to process, so you should disable them. Some statistics don\'t use extra processing power so they are enabled by default.'),
				'checkboxes' => array()
			),
			'separator-2' => array(
				'type' => 'separator-short'
			),
			'submit' => array(
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => __('Submit')
			),
			'close' => array(
				'type' => 'close'
			),
		);

		foreach(BS::get_stats() as $key => $stat)
		{
			$arr['fu.plugins.board_statistics.enabled']['checkboxes'][] = array(
				'type' => 'checkbox',
				'label' => $key,
				'help' => sprintf(__('Enable %s statistics'), $stat['name']),
				'array_key' => $key,
				'preferences' => true,
			);
		}

		return $arr;
	}

	public function action_manage()
	{
		$this->_views['controller_title'] = __("Board Statistics");
		$this->_views['method_title'] = __('Manage');

		$data['form'] = $this->structure();

		\Preferences::submit_auto($data['form']);

		// create a form
		$this->_views["main_content_view"] = \View::forge("foolz/foolframe::admin/form_creator", $data);
		return \Response::forge(\View::forge("foolz/foolframe::admin/default", $this->_views));
	}
}