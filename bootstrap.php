<?php

\Autoloader::add_classes(array(
	'Foolfuuka\\Model\\Radix' => APPPATH.'modules/foolfuuka/classes/model/radix.php',
	'Foolfuuka\\Model\\Board' => APPPATH.'modules/foolfuuka/classes/model/board.php',
	'Foolfuuka\\Model\\Search' => APPPATH.'modules/foolfuuka/classes/model/search.php',
	'Foolfuuka\\Model\\Comment' => APPPATH.'modules/foolfuuka/classes/model/comment.php',
	'Foolfuuka\\Model\\Media' => APPPATH.'modules/foolfuuka/classes/model/media.php',
	'Foolfuuka\\Model\\Extra' => APPPATH.'modules/foolfuuka/classes/model/extra.php',
	'Foolfuuka\\Model\\Report' => APPPATH.'modules/foolfuuka/classes/model/report.php',
	'Foolfuuka\\Model\\Ban' => APPPATH.'modules/foolfuuka/classes/model/ban.php',
	'Foolfuuka\\Tasks\\Fool' => APPPATH.'modules/foolfuuka/classes/task/fool.php',
));

\Autoloader::add_core_namespace('Foolfuuka\\Model');

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
	\Foolfuuka\Model\Report::preload();
}

$theme = \Theme::forge('foolfuuka');
$theme->set_module('foolfuuka');
$theme->set_theme(\Input::get('theme', \Cookie::get('theme')) ? : 'default');
$theme->set_layout('chan');
