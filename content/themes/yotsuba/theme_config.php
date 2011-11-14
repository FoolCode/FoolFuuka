<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


// name, description of this theme...
$config['theme_directory'] = 'yotsuba';
$config['theme_name'] = 'Yotsuba';
$config['theme_description'] = 'The 4chan Yotsuba theme';
$config['theme_tags'] = array('4chan', 'original');
// for the default theme, this is the last FoOlSlide version there were changes to it
$config['theme_version'] = '0.1.0';

// some personal data on the author
$config['theme_author'] = 'Woxxy';
$config['theme_author_email'] = 'woxxy@foolrulez.org';
$config['theme_author_site'] = 'http://foolrulez.org';

// license
$config['theme_license'] = 'Apache License 2.0';
$config['theme_license_url'] = 'http://www.apache.org/licenses/LICENSE-2.0.html';


// some general theme configuration 

// which theme should this theme extend? Insert the folder name of the other theme
$config['theme_extends'] = 'yotsuba'; // it's ok to refer it to itself, it means there's no fallback

// do you want to keep the extended theme's CSS and overwrite just what you need to?
// if this is TRUE, in your own theme's CSS you have just to override the extended
// theme's CSS.
$config['theme_extends_css'] = FALSE;

