<?php

\Autoloader::add_classes(array(
	'Foolfuuka\\Model\\Radix' => APPPATH.'modules/foolfuuka/classes/model/radix.php',
	'Foolfuuka\\Model\\Board' => APPPATH.'modules/foolfuuka/classes/model/board.php',
	'Foolfuuka\\Model\\Comment' => APPPATH.'modules/foolfuuka/classes/model/comment.php',
	'Foolfuuka\\Model\\Media' => APPPATH.'modules/foolfuuka/classes/model/media.php',
));

Autoloader::add_core_namespace('Foolfuuka\\Model');


$theme = \Theme::forge('foolfuuka');
$theme->set_module('foolfuuka');
$theme->set_theme(\Cookie::get('theme')?:'default');
$theme->set_layout('chan');