<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class FU_Dice_Roll extends Plugins_model
{


	function __construct()
	{
		// KEEP THIS EMPTY, use the initialize_plugin method instead

		parent::__construct();
	}

	function initialize_plugin()
	{
		$this->plugins->register_hook($this, 'fu_post_model_alter_comment_input', 4, 'roll');
	}

	function roll($data)
	{
		if ($data['email'] !== FALSE || $data['email'] != '')
		{
			if (preg_match('/dice[ +](\d+)[ d+](\d+)(([ +-]+?)(-?\d+))?/', $data['email'], $result))
			{
				$modifier = '';

				$dice = array(
					'total' => $result[1],
					'side' => $result[2],
					'modifier' => $result[3],
					'expr' => $result[4],
					'val' => $result[5],
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
					if (strpos($dice['modifier'], '-') !== FALSE)
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