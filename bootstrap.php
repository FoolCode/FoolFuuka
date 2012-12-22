<?php

class_alias('Foolz\\Foolfuuka\\Model\\Ban', 'Ban');
class_alias('Foolz\\Foolfuuka\\Model\\Board', 'Board');
class_alias('Foolz\\Foolfuuka\\Model\\Comment', 'Comment');
class_alias('Foolz\\Foolfuuka\\Model\\CommentInsert', 'CommentInsert');
class_alias('Foolz\\Foolfuuka\\Model\\Extra', 'Extra');
class_alias('Foolz\\Foolfuuka\\Model\\Media', 'Media');
class_alias('Foolz\\Foolfuuka\\Model\\Radix', 'Radix');
class_alias('Foolz\\Foolfuuka\\Model\\Report', 'Report');
class_alias('Foolz\\Foolfuuka\\Model\\Search', 'Search');


\Package::load('stringparser-bbcode', __DIR__.'/packages/stringparser-bbcode/');

if (\Auth::has_access('comment.reports'))
{
	\Foolz\Foolfuuka\Model\Report::preload();
}

$theme = \Foolz\Foolframe\Model\Theme::forge('foolfuuka');
$theme->set_module('foolz/foolfuuka');
$theme->set_theme(\Input::get('theme', \Cookie::get('theme')) ? : 'default');
$theme->set_layout('chan');