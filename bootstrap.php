<?php

\Autoloader::add_core_namespace('Foolz\Foolfuuka\Model');

\Package::load('stringparser-bbcode', __DIR__.'/packages/stringparser-bbcode/');

if (\Auth::has_access('comment.reports'))
{
	\Foolz\Foolfuuka\Model\Report::preload();
}

$theme = \Theme::forge('foolfuuka');
$theme->set_module('foolz/foolfuuka');
$theme->set_theme(\Input::get('theme', \Cookie::get('theme')) ? : 'default');
$theme->set_layout('chan');