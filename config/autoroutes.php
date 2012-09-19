<?php
return array(
	'_root_' => 'foolfuuka/chan/index',  // The default route
	'_/api/chan/(:any)' => 'foolfuuka/api/chan/$1',
	'api/chan/(:any)' => 'foolfuuka/api/chan/$1',
	'admin/boards/(:any)' => 'foolfuuka/admin/boards/$1',
	'admin/posts/(:any)' => 'foolfuuka/admin/posts/$1',
	'_/advanced_search' => 'foolfuuka/chan/advanced_search',
	'_/theme/(:any)' => 'foolfuuka/chan/theme/$1',
	'_/language/(:any)' => 'foolfuuka/chan/language/$1',
	'_/opensearch' => 'foolfuuka/chan/opensearch/',
	'_/search' => 'foolfuuka/chan/search',
	'search' => 'foolfuuka/chan/search',
	'_/search/(:any)' => 'foolfuuka/chan/search',
	'search/(:any)' => 'foolfuuka/chan/search',
	'_/notfound/action404' => 'foolfuuka/chan/404', // we need to properly redirect the 404
	'(?!(admin|_))(\w+)' => 'foolfuuka/chan/$2/page',
	'(?!(admin|_))(\w+)/(:any)' => 'foolfuuka/chan/$2/$3',
	'_404_'=> '_/notfound/action404',    // The main 404 route
);