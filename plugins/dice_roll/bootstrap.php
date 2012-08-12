<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Autoloader::add_classes(array(
	'Foolfuuka\\Plugins\\Dice_Roll\\Dice_Roll' => __DIR__.'/classes/model/dice_roll.php'
));

\Plugins::register_hook('fu.comment.insert.alter_input_after_checks', 
	'Foolfuuka\\Plugins\\Dice_Roll\\Dice_Roll::roll', 4);

\Plugins::register_hook('fu.radix.structure.structure_alter', function($structure){
	$structure['plugin_dice_roll_enable'] = array(
		'database' => TRUE,
		'boards_preferences' => TRUE,
		'type' => 'checkbox',
		'help' => __('Enable dice roll?')
	);

	return array('return' => $structure);
}, 4);