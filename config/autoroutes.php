<?php
return array(
	'_root_'  => 'foolfuuka/chan/index',  // The default route
	'api/chan/(:any)' => 'foolfuuka/api/chan/$1',
	'admin/boards/(:any)' => 'foolfuuka/admin/boards/$1',
	'search/(:any)' => 'foolfuuka/chan/search',
	'(?!(admin|api|content|assets|search))(\w+)' => 'foolfuuka/chan/$2/page',
	'(?!(admin|api|content|assets|search))(\w+)/(:any)' => 'foolfuuka/chan/$2/$3',
	'_404_'   => 'foolfuuka/chan/404',    // The main 404 route
);