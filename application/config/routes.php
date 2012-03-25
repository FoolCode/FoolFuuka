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

$route['default_controller'] = "chan";
$route['install'] = "install";
$route['api'] = "api";
$route['cli'] = "cli";
$route['admin'] = "admin/preferences";
$route['admin/members/members'] = 'admin/members/membersa';
$route['admin/plugins/(.*?)'] = "admin/plugins_admin/$1";

$route_admin_controllers = glob(APPPATH . 'controllers/admin/*.php');

foreach($route_admin_controllers as $key => $item)
{
	$item = str_replace(APPPATH . 'controllers/admin/', '', $item);
	$route_admin_controllers[$key] = substr($item, 0, strlen($item) - 4);
}
$route_admin_controllers[] = 'plugins';


// routes to allow plugin.php to catch the files, could be automated...
$route['admin/(?!(' . implode('|', $route_admin_controllers) . '))(\w+)'] = "admin/plugin/$2/";
$route['admin/(?!(' . implode('|', $route_admin_controllers) . '))(\w+)/(.*?)'] = "admin/plugin/$2/$3";

$protected_radixes = implode('|', unserialize(FOOL_PROTECTED_RADIXES));
$route['(?!(' . $protected_radixes . '))(\w+)/(.*?).xml'] = "chan/$2/feeds/$3";
$route['(?!(' . $protected_radixes . '))(\w+)/(.*?)'] = "chan/$2/$3";
$route['(\w+)'] = "chan/$1/page";

$route['404_override'] = 'plugin';


/* End of file routes.php */
/* Location: ./application/config/routes.php */