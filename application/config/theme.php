<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 *  This is a fake config file that redirects to the current theme's config file
 */

// load default config
require(FCPATH . 'content/themes/default/reader_config.php');

// overtwrite with theme specific configuration
if (file_exists(FCPATH . 'content/themes/' . get_setting('fs_theme_dir') . '/reader_config.php'))
	require(FCPATH . 'content/themes/' . get_setting('fs_theme_dir') . '/reader_config.php');
	