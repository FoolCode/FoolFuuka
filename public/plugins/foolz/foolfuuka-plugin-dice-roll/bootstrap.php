<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-dice-roll')
	->setCall(function($result) {

		\Autoloader::add_classes([
			'Foolz\Foolfuuka\Plugins\DiceRoll\Model\Dice' => __DIR__.'/classes/model/dice.php'
		]);

		\Foolz\Plugin\Event::forge('foolfuuka.comment.insert.alter_input_after_checks')
			->setCall('Foolz\Foolfuuka\Plugins\DiceRoll\Model\Dice::roll')
			->setPriority(4);

		\Foolz\Plugin\Event::forge('foolfuuka.radix.structure.structure_alter')
			->setCall(function($result) {
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