<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

/*
 * Here you should complete the required fields
 */

// class name case sensitive
$info['slug'] = 'FF_GeoIP_Region_Lock';
// name under which to display your plugin
$info['name'] = 'GeoIP Region Lock';
// short description about what the plugin does
$info['description'] = __('Allow or disallow nations to reach the board or post comments. Requires the GeoIP PECL module.');

// version in x.x.x format, important for plugin core files upgrade
$info['version'] = '0.7.4';
// database revision in number format, important for database schema changes
$info['revision'] = '0';

// various info
$info['author'] = 'Woxxy';