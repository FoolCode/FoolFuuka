<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

return array(
	
	'slug' => 'geoip_region_lock',
	
	'name' => 'GeoIP Region Lock',
	
	'description' => __('Allow or disallow nations to reach the board or post comments. Requires the GeoIP PECL module.'),	
	
	'identifier' => 'fu',
	
	'version' => '1.5.0',
	
	'revision' => '0',
	
	'author' => 'Foolz',
	
	'author_link' => 'http://www.foolz.us',
	
	'support_link' => '',
	
	'license' => 'Apache License 2.0',
	
	'license_link' => 'http://www.apache.org/licenses/LICENSE-2.0.html',
	
);