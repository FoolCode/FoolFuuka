<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

return array(
	
	'slug' => 'nginx_cache_purge',
	
	'name' => 'Nginx Cache Purge',
	
	'description' => sprintf(
		__('Remove deleted images from the Nginx proxy cache when deleted locally. Requires Nginx compiled with %s to work.'), 
		'<a href="https://github.com/FRiCKLE/ngx_cache_purge" target="_blank">ngx_cache_purge</a>'
	),	
	
	'identifier' => 'fu',
	
	'version' => '1.5.0',
	
	'revision' => '0',
	
	'author' => 'Foolz',
	
	'author_link' => 'http://www.foolz.us',
	
	'support_link' => '',
	
	'license' => 'Apache License 2.0',
	
	'license_link' => 'http://www.apache.org/licenses/LICENSE-2.0.html',
	
);