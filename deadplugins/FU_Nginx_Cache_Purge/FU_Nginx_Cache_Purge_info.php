<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

/*
 * Here you should complete the required fields
 */

// class name case sensitive
$info['slug'] = 'FU_Nginx_Cache_Purge';
// name under which to display your plugin
$info['name'] = 'Nginx Cache Purge';
// short description about what the plugin does
$info['description'] = sprintf(
	__('Remove deleted images from the Nginx proxy cache when deleted locally. Requires Nginx compiled with %s to work.'), 
	'<a href="https://github.com/FRiCKLE/ngx_cache_purge" target="_blank">ngx_cache_purge</a>'
);

// version in x.x.x format, important for plugin core files upgrade
$info['version'] = '0.7.0';
// database revision in number format, important for database schema changes
$info['revision'] = '0';

// various info
$info['author'] = 'Woxxy';