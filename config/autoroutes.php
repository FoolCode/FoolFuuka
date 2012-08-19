<?php
return array(
	'_root_' => 'foolfuuka/chan/index',  // The default route
	'api/chan/(:any)' => 'foolfuuka/api/chan/$1',
	'admin/boards/(:any)' => 'foolfuuka/admin/boards/$1',
	'_/theme/(:any)' => 'foolfuuka/chan/theme/$1',
	'_/search/(:any)' => 'foolfuuka/chan/search',
	'_/notfound/action404' => 'foolfuuka/chan/404', // we need to properly redirect the 404
	'(?!(admin|api|_))(\w+)' => 'foolfuuka/chan/$2/page',
	'(?!(admin|api|_))(\w+)/(:any)' => 'foolfuuka/chan/$2/$3',
	'_404_'=> '_/notfound/action404',    // The main 404 route
);