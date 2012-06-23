<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class FU_Dice_Roll extends Plugins_model
{


	function initialize_plugin()
	{
		$this->plugins->register_hook($this, 'fu_post_model_comment_alter_input', 4, 'roll');
		$this->plugins->register_hook($this, 'fu_radix_model_structure_alter', 4, function($structure){
			$structure['plugin_dice_roll_enable'] = array(
				'database' => TRUE,
				'boards_preferences' => TRUE,
				'type' => 'checkbox',
				'help' => __('Enable dice roll?')
			);

			return array('return' => $structure);
		});
	}

	function roll($board, $data)
	{
		if($board->plugin_dice_roll_enable == 0)
		{
			return array('return' => $data);
		}

		if ($data['email'] !== FALSE || $data['email'] != '')
		{
			if (preg_match('/dice[ +](\d+)[ d+](\d+)(([ +-]+?)(-?\d+))?/', $data['email'], $result))
			{
				$modifier = '';

				$dice = array(
					'total' => $result[1],
					'side' => $result[2],
					'modifier' => (isset($result[3]) ? $result[3] : NULL),
					'expr' => (isset($result[4]) ? $result[4] : '+'),
					'val' => (isset($result[5]) ? $result[5] : 0),
					'sum' => 0,
					'output' => array()
				);

				for ($d = 0; $d < $dice['total']; $d++)
				{
					$rand = mt_rand(1, $dice['side']);
					$dice['sum']  += $rand;
					$dice['num'][] = $rand;
				}

				if ($dice['modifier'] !== NULL)
				{
					if (strpos($dice['expr'], '-') !== FALSE)
					{
						$dice['val'] *= -1;
					}

					$dice['sum'] += $dice['val'];
					$modifier = ($dice['val'] >= 0 ? ' + ' : ' - ') . abs($dice['val']);
				}

				$output = '[b]rolled ' . implode(', ', $dice['num']) . $modifier . ' = ' . $dice['sum'] . '[/b]';
				$data['comment'] = trim($output . "\n\n" . $data['comment']);
			}
		}

		return array('return' => $data);
	}

}