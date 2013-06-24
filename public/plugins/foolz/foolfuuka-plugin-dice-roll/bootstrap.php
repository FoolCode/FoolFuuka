<?php

\Foolz\Plugin\Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-dice-roll')
	->setCall(function($result) {

		\Autoloader::add_classes([
			'Foolz\Foolfuuka\Plugins\DiceRoll\Model\Dice' => __DIR__.'/classes/model/dice.php'
		]);

		\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\CommentInsert::insert.call.after.input_checks')
			->setCall('Foolz\Foolfuuka\Plugins\DiceRoll\Model\Dice::roll')
			->setPriority(4);

		\Foolz\Plugin\Event::forge('Foolz\Foolfuuka\Model\Radix::structure.result')
			->setCall(function($result) {
				$structure = $result->getParam('structure');
				$structure['plugin_dice_roll_enable'] = [
					'database' => true,
					'boards_preferences' => true,
					'type' => 'checkbox',
					'help' => _i('Enable dice roll?')
				];
				$result->setParam('structure', $structure)->set($structure);
			})->setPriority(4);

		\Foolz\Foolfuuka\Model\Radix::preload();
	});