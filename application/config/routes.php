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

// if we're using special subdomains or if we're under system stuff
if(
	!defined('FOOL_SUBDOMAINS_ENABLE')
	|| strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_SYSTEM) !== FALSE
)
{	
	$route['install'] = "install";
	$route['api'] = "api";
	$route['cli'] = "cli";
	$route['search'] = "chan/search";
	$route['search/(.*?)'] = "chan/search/$1";
	$route['admin'] = "admin/boards";
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
}

// if we're using special subdomains or if we're under boards/archives:
if(
	!defined('FOOL_SUBDOMAINS_ENABLE')
	|| strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_BOARD) !== FALSE
	|| strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_ARCHIVE) !== FALSE
)
{
	if(!defined('FOOL_SUBDOMAINS_ENABLE'))
	{
		$protected_radixes = implode('|', unserialize(FOOL_PROTECTED_RADIXES));
		$route['(?!(' . $protected_radixes . '))(\w+)/(.*?).xml'] = "chan/$2/feeds/$3";
		$route['(?!(' . $protected_radixes . '))(\w+)/(.*?)'] = "chan/$2/$3";
	}
	else
	{
		$route['(\w+)/(.*?).xml'] = "chan/$1/feeds/$2";
		$route['(\w+)/(.*?)'] = "chan/$1/$2";
	}
	$route['(\w+)'] = "chan/$1/page";
}

$route['404_override'] = 'plugin';


/* End of file routes.php */
/* Location: ./application/config/routes.php */