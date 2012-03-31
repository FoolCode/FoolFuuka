<?php

/**
 * READER FUNCTIONS
 *
 * This file allows you to add functions and plain procedures that will be
 * loaded every time the public reader loads.
 *
 * If this file doesn't exist, the default theme's reader_functions.php will
 * be loaded.
 *
 * For more information, refer to the support sites linked in your admin panel.
 */

function get_banner() {
	$banners = glob('content/themes/yotsuba/images/banners/*.*');
	return site_url() . $banners[array_rand($banners)];
}