<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = "reader";
$route['sitemap.xml'] = "reader/sitemap";
$route['rss.xml'] = "reader/feeds";
$route['atom.xml'] = "reader/feeds/atom";
$route['admin'] = "admin/series";
$route['admin/series/series/(:any)'] = "admin/series/serie/$1";
$route['account'] = "account/index/profile";
$route['account/profile'] = "account/index/profile";
$route['account/teams'] = "account/index/teams";
$route['account/leave_team/(:any)'] = "account/index/leave_team/$1";
$route['account/request/(:any)'] = "account/index/request/$1";
$route['account/leave_leadership/(:any)'] = "account/index/leave_leadership/$1";
$route['reader/list'] = 'reader/lista';
$route['reader/list/(:num)'] = 'reader/lista/$1';
$route['admin/members/members'] = 'admin/members/membersa';

// added for compatibility on upgrade 0.8.1 -> 0.8.2 on 30/09/2011
$route['admin/upgrade'] = 'admin/system/upgrade';
$route['404_override'] = '';


/* End of file routes.php */
/* Location: ./application/config/routes.php */