<?php

$model_dir = APPPATH.'modules/foolfuuka/classes/Foolz/Foolfuuka/Model/';

\Autoloader::add_classes(array(
	'Foolz\Foolfuuka\Model\Radix' => $model_dir.'Radix.php',
	'Foolz\Foolfuuka\Model\Board' => $model_dir.'Board.php',
	'Foolz\Foolfuuka\Model\Search' => $model_dir.'Search.php',
	'Foolz\Foolfuuka\Model\Comment' => $model_dir.'Comment.php',
	'Foolz\Foolfuuka\Model\CommentInsert' => $model_dir.'CommentInsert.php',
	'Foolz\Foolfuuka\Model\Media' => $model_dir.'Media.php',
	'Foolz\Foolfuuka\Model\Extra' => $model_dir.'Extra.php',
	'Foolz\Foolfuuka\Model\Report' => $model_dir.'Report.php',
	'Foolz\Foolfuuka\Model\Ban' => $model_dir.'Ban.php',
	'Foolfuuka\\Tasks\\Fool' => APPPATH.'modules/foolfuuka/classes/task/fool.php',
));

\Autoloader::add_core_namespace('Foolz\\Foolfuuka\\Model');

\Profiler::mark('Start sphinxql initialization');
\Profiler::mark_memory(false, 'Start sphinxql initialization');

\Package::load('sphinxql');

\Profiler::mark('End sphinxql, Start stringparser-bbcode initialization');
\Profiler::mark_memory(false, 'End sphinxql, Start stringparser-bbcode initialization');

\Package::load('stringparser-bbcode', APPPATH.'modules/foolfuuka/packages/stringparser-bbcode/');

\Profiler::mark('End stringparser-bbcode initialization, start geoip_codes initialization');
\Profiler::mark_memory(false, 'End stringparser-bbcode initialization, start geoip_codes initialization');

\Config::load('foolfuuka::geoip_codes', 'geoip_codes');

\Profiler::mark('End geoip_codes initialization');
\Profiler::mark_memory(false, 'End  geoip_codes initialization');

if (\Auth::has_access('comment.reports'))
{
	\Foolz\Foolfuuka\Model\Report::preload();
}

$theme = \Theme::forge('foolfuuka');
$theme->set_module('foolfuuka');
$theme->set_theme(\Input::get('theme', \Cookie::get('theme')) ? : 'default');
$theme->set_layout('chan');