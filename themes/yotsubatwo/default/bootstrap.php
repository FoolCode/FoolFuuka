<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

\Autoloader::add_classes(array(
	'Foolfuuka\\Themes\\Default_\\Views\\Board_comment' => __DIR__.'/views/board_comment.php',
));