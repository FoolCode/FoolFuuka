<?php

use Foolz\Foolframe\Model\Autoloader;
use Foolz\Plugin\Event;

Event::forge('Foolz\Plugin\Plugin::execute.foolz/foolfuuka-plugin-dice-roll')
    ->setCall(function($result) {

        /* @var Context $context */
        $context = $result->getParam('context');
        /** @var Autoloader $autoloader */
        $autoloader = $context->getService('autoloader');

        $autoloader->addClass('Foolz\Foolfuuka\Plugins\DiceRoll\Model\Dice', __DIR__.'/classes/model/dice.php');

        Event::forge('Foolz\Foolfuuka\Model\CommentInsert::insert.call.after.input_checks')
            ->setCall('Foolz\Foolfuuka\Plugins\DiceRoll\Model\Dice::roll')
            ->setPriority(4);

        Event::forge('Foolz\Foolfuuka\Model\Radix::structure.result')
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
    });
