<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Upgrade2_model extends CI_Model {

	function __construct() {
		// Call the Model constructor
		parent::__construct();
	}
	
	/**
	 * A second file check, different from upgrade_model, this time updated.
	 *
	 * @author Woxxy
	 * @return bool 
	 */
	function check_files() {
		if (!is_writable('.')) {
			return FALSE;
		}
		if (!is_writable('index.php')) {
			return FALSE;
		}
		if (!is_writable('application')) {
			return FALSE;
		}
		if (!is_writable('system')) {
			return FALSE;
		}
		if (!is_writable('content')) {
			return FALSE;
		}
		if (!is_writable('assets')) {
			return FALSE;
		}
		if (!is_writable('content/themes')) {
			return FALSE;
		}
		if (!is_writable('content/themes/default')) {
			return FALSE;
		}
		if (!is_writable('content/themes/fuuka')) {
			return FALSE;
		}
		if (!is_writable('content/cache')) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Actual upgrade for FoOlSlide, uses the check yet again and checks if all
	 * the files needed from the ZIP were extracted. Then it swaps the original
	 * files
	 * 
	 * THIS DOESN'T UPGRADE THE DATABASE. That happens by default every time the
	 * admin is in the admin panel, and gets asked for database update.
	 * 
	 * FoOlSlide won't be working until the admin accepts the database upgrade,
	 * for security reasons.
	 *
	 * @return type 
	 */
	function do_upgrade() {
		if (!$this->check_files()) {
			log_message('error', 'upgrade.php:_do_upgrade() check_files() failed');
			return false;
		}

		// Put FoOlSlide in maintenance
		$this->db->update('preferences', array('value' => 'fs_priv_maintenance'), array('name' => __("We're currently upgrading {{FOOL_NAME}}. This process usually takes few seconds or a couple minutes, check back soon!")));
		
		$this->load->helper('directory');
		$filenames = directory_map('content/cache/upgrade/', 1);
		$folder = "";
		foreach($filenames as $filename)
		{
		    if($filename != 'upgrade.zip')
		    {
			$folder = $filename;
			break;
		    }
		}
		log_message('error', $folder);
		if (!file_exists('content/cache/upgrade/' . $folder)) {
			return FALSE;
		}
		if (!file_exists('content/cache/upgrade/' . $folder . '/index.php')) {
			return FALSE;
		}
		if (!file_exists('content/cache/upgrade/' . $folder . '/application')) {
			return FALSE;
		}
		if (!file_exists('content/cache/upgrade/' . $folder . '/system')) {
			return FALSE;
		}
		if (!file_exists('content/cache/upgrade/' . $folder . '/assets')) {
			return FALSE;
		}
		if (!file_exists('content/cache/upgrade/' . $folder . '/content/themes/default')) {
			return FALSE;
		}
		if (!file_exists('content/cache/upgrade/' . $folder . '/content/themes/fuuka')) {
			return FALSE;
		}

		unlink('index.php');
		rename('content/cache/upgrade/' . $folder . '/index.php', 'index.php');
		delete_files('application/', TRUE);
		rename('content/cache/upgrade/' . $folder . '/application', 'application');
		delete_files('system/', TRUE);
		rename('content/cache/upgrade/' . $folder . '/system', 'system');
		delete_files('assets/', TRUE);
		rename('content/cache/upgrade/' . $folder . '/assets', 'assets');
		delete_files('content/themes/default/', TRUE);
		rename('content/cache/upgrade/' . $folder . '/content/themes/default', 'content/themes/default');
		delete_files('content/themes/fuuka/', TRUE);
		rename('content/cache/upgrade/' . $folder . '/content/themes/fuuka', 'content/themes/fuuka');
		delete_files('content/plugins/FS_Articles/', TRUE);
		rename('content/cache/upgrade/' . $folder . '/content/plugins/FS_Articles', 'content/plugins/FS_Articles');
		delete_files('content/plugins/FU_Nginx_Cache_Purge/', TRUE);
		rename('content/cache/upgrade/' . $folder . '/content/plugins/FU_Nginx_Cache_Purge', 'content/plugins/FU_Nginx_Cache_Purge');
		
		return TRUE;
	}

}