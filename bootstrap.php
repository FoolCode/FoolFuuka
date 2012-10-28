<?php

$model_dir = __DIR__.'/classes/Foolz/Foolfuuka/Model/';

\Autoloader::add_classes([
	'Foolz\Foolfuuka\Model\Radix' => $model_dir.'Radix.php',
	'Foolz\Foolfuuka\Model\Board' => $model_dir.'Board.php',
	'Foolz\Foolfuuka\Model\Search' => $model_dir.'Search.php',
	'Foolz\Foolfuuka\Model\Comment' => $model_dir.'Comment.php',
	'Foolz\Foolfuuka\Model\CommentInsert' => $model_dir.'CommentInsert.php',
	'Foolz\Foolfuuka\Model\Media' => $model_dir.'Media.php',
	'Foolz\Foolfuuka\Model\Extra' => $model_dir.'Extra.php',
	'Foolz\Foolfuuka\Model\Report' => $model_dir.'Report.php',
	'Foolz\Foolfuuka\Model\Ban' => $model_dir.'Ban.php',
	'Foolz\Foolfuuka\Model\Schema' => $model_dir.'Schema.php',
	'Foolz\Foolfuuka\Tasks\Fool' => __DIR__.'/classes/Task/Fool.php',

	'Foolz\Foolfuuka\Controller\Chan' => __DIR__.'/classes/Foolz/Foolfuuka/Controller/Chan.php',
	'Foolz\Foolfuuka\Controller\Admin\Boards' => __DIR__.'/classes/Foolz/Foolfuuka/Controller/Api/Chan.php',
	'Foolz\Foolfuuka\Controller\Admin\Boards' => __DIR__.'/classes/Foolz/Foolfuuka/Controller/Admin/Boards.php',
	'Foolz\Foolfuuka\Controller\Admin\Posts' => __DIR__.'/classes/Foolz/Foolfuuka/Controller/Admin/Posts.php'
]);

\Autoloader::add_core_namespace('Foolz\Foolfuuka\Model');

\Package::load('stringparser-bbcode', __DIR__.'/packages/stringparser-bbcode/');
\Config::load('foolfuuka::geoip_codes', 'geoip_codes');

if (\Auth::has_access('comment.reports'))
{
	\Foolz\Foolfuuka\Model\Report::preload();
}

$theme = \Theme::forge('foolfuuka');
$theme->set_module('foolz/foolfuuka');
$theme->set_theme(\Input::get('theme', \Cookie::get('theme')) ? : 'default');
$theme->set_layout('chan');
