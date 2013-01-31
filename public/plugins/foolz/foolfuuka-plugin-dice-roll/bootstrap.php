<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/dice_roll')
	->setCall(function($result) {

		\Autoloader::add_classes([
			'Foolfuuka\\Plugins\\Dice_Roll\\Dice_Roll' => __DIR__.'/classes/model/dice_roll.php'
		]);

		\Foolz\Plugin\Event::forge('fu.comment.insert.alter_input_after_checks')
			->setCall('Foolfuuka\\Plugins\\Dice_Roll\\Dice_Roll::roll')
			->setPriority(4);

		\Foolz\Plugin\Event::forge('fu.radix.structure.structure_alter')
			->setCall(function($result){
				$structure = $result->getParam('structure');
				$structure['plugin_dice_roll_enable'] = [
					'database' => true,
					'boards_preferences' => true,
					'type' => 'checkbox',
					'help' => __('Enable dice roll?')
				];
				$result->setParam('structure', $structure)->set($structure);
			})->setPriority(4);

	});