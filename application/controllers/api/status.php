<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Status extends REST_Controller
{
	function info_get()
	{
		$result = array();
		$result["title"] = get_setting('fs_gen_site_title');
		$result["version"] = FOOLSLIDE_VERSION;
		$result["home_team"] = get_home_team()->to_array();
		$this->response($result, 200); // 200 being the HTTP response code
	}


}