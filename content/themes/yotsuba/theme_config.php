<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


// name, description of this theme...
$config['directory'] = 'yotsuba';
$config['name'] = 'Yotsuba';
$config['description'] = 'The 4chan Yotsuba theme';
$config['tags'] = array('4chan', 'original');
// for the default theme, this is the last FoOlSlide version there were changes to it
$config['version'] = '0.1.0';

// some personal data on the author
$config['author'] = 'Woxxy';
$config['author_email'] = 'woxxy@foolrulez.org';
$config['author_site'] = 'http://foolrulez.org';

// license
$config['license'] = 'Apache License 2.0';
$config['license_url'] = 'http://www.apache.org/licenses/LICENSE-2.0.html';


// some general theme configuration 

// which theme should this theme extend? Insert the folder name of the other theme
$config['extends'] = 'yotsuba'; // it's ok to refer it to itself, it means there's no fallback

// do you want to keep the extended theme's CSS and overwrite just what you need to?
// if this is TRUE, in your own theme's CSS you have just to override the extended
// theme's CSS.
$config['extends_css'] = FALSE;

