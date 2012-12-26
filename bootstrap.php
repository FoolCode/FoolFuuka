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

$theme_instance = \Foolz\Theme\Loader::forge('foolfuuka');
$theme_instance->addDir('foolz', VENDPATH.'foolz/foolfuuka/'.\Foolz\Config\Config::get('foolz/foolfuuka', 'package', 'directories.themes'));
$theme_instance->setBaseUrl(\Uri::base().'foolfuuka/');
$theme_instance->setPublicDir(DOCROOT.'foolfuuka/');