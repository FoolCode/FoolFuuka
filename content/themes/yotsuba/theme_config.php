<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


// name, description of this theme...
$config['directory'] = 'yotsuba';
$config['name'] = 'Yotsuba';
$config['description'] = 'The Yotsuba theme';
$config['tags'] = array('Yotsuba', 'blue');
// for the default theme, this is the last FoOlSlide version there were changes to it
$config['version'] = '0.1.0';

// some personal data on the author
$config['author'] = 'FoOlRulez';
$config['author_email'] = 'support@foolz.us';
$config['author_site'] = 'http://www.foolz.us';

// license
$config['license'] = 'Apache License 2.0';
$config['license_url'] = 'http://www.apache.org/licenses/LICENSE-2.0.html';


// some general theme configuration

// which theme should this theme extend? Insert the folder name of the other theme
$config['extends'] = 'default'; // it's ok to refer it to itself, it means there's no fallback

// do you want to keep the extended theme's CSS and overwrite just what you need to?
// if this is TRUE, in your own theme's CSS you have just to override the extended
// theme's CSS.
$config['extends_css'] = FALSE;

