<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Upgrade_model extends CI_Model {

	function __construct() {
		// Call the Model constructor
		parent::__construct();
		$this->pod = 'http://foolrulez.com/pod';
	}

	/**
	 * Connects to FoOlPod to retrieve which is the latest version from the API
	 * 
	 * @param type $force forces returning the download even if FoOlSlide is up to date
	 * @return type FALSE or the download URL
	 */
	function check_latest($force = FALSE) {
		if (function_exists('curl_init')) {
			$this->load->library('curl');
			$result = $this->curl->simple_post($this->pod . '/api/software/foolslide', array('url' => site_url(), 'version' => get_setting('fs_priv_version')));
		}
		else
			$result = file_get_contents($this->pod . '/api/software/foolslide');
		if (!$result) {
			set_notice('error', _('FoOlPod server could not be contacted: impossible to check for new versions.'));
			return FALSE;
		}
		$data = json_decode($result);

		$new_versions = array();
		foreach ($data->versions as $new) {
			if (!$this->is_bigger_version(FOOLSLIDE_VERSION, $new))
				break;
			$new_versions[] = $new;
		}
		if (!empty($new_versions))
			return $new_versions;

		if($force)
			return array($data->versions[0]);
		
		return FALSE;
	}

	/**
	 * Compares two versions and returns TRUE if second parameter is bigger than first, else FALSE
	 * 
	 * @param type $maybemin
	 * @param type $maybemax
	 * @return bool 
	 */
	function is_bigger_version($maybemin, $maybemax) {
		if (is_string($maybemin))
			$maybemin = $this->version_to_object($maybemin);
		if (is_string($maybemax))
			$maybemax = $this->version_to_object($maybemax);

		if ($maybemax->version > $maybemin->version ||
				($maybemax->version == $maybemin->version && $maybemax->subversion > $maybemin->subversion) ||
				($maybemax->version == $maybemin->version && $maybemax->subversion == $maybemin->subversion && $maybemax->subsubversion > $maybemin->subsubversion)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Converts the version from string separated by dots to object
	 * 
	 * @author Woxxy
	 * @param type $string
	 * @return object 
	 */
	function version_to_object($string) {
		$version = explode('.', $string);
		$current->version = $version[0];
		$current->subversion = $version[1];
		$current->subsubversion = $version[2];
		return $current;
	}

	/**
	 *
	 * @author Woxxy
	 * @param string $url
	 * @return bool 
	 */
	function get_file($url, $direct_url) {
		$this->clean();
		if (function_exists('curl_init')) {
			$this->load->library('curl');
			$zip = $this->curl->simple_post($url, array('url' => site_url(), 'version' => FOOLSLIDE_VERSION));
			if (!$zip) {
				$zip = $this->curl->simple_get($direct_url);
			}
		}
		else {
			$zip = file_get_contents($direct_url);
		}
		if (!$zip) {
			log_message('error', 'upgrade_model get_file(): impossible to get the update from FoOlPod');
			flash_notice('error', _('Can\'t get the update file from FoOlPod. It might be a momentary problem, or a problem with your server security configuration. Browse <a href="http://foolrulez.com/pod/human">http://foolrulez.com/pod/human</a> to check if it\'s a known issue.'));
			return FALSE;
		}

		if (!is_dir('content/cache/upgrade'))
			mkdir('content/cache/upgrade');
		write_file('content/cache/upgrade/upgrade.zip', $zip);
		$this->load->library('unzip');
		$this->unzip->extract('content/cache/upgrade/upgrade.zip');
		return TRUE;
	}

	/**
	 * Checks files permissions before upgrading
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
		
		if (!is_writable('application/models/upgrade2_model.php')) {
			return FALSE;
		}

		return TRUE;
	}

	function permissions_suggest() {
		$prob = FALSE;
		if (!is_writable('.')) {
			$whoami = FALSE;
			if ($this->_exec_enabled())
				$whoami = exec('whoami');
			if (!$whoami && is_writable('content') && function_exists('posix_getpwid')) {
				write_file('content/testing_123.txt', 'testing_123');
				$whoami = posix_getpwuid(fileowner('content/testing_123.txt'));
				$whoami = $whoami['name'];
				unlink('content/testing_123.txt');
			}
			if ($whoami != "")
				set_notice('warn', sprintf(_('The %s directory would be better if writable, in order to deliver automatic updates. Use this command in your shell if possible: %s'), FCPATH, '<br/><b><code>chown -R ' . $whoami . ' ' . FCPATH . '</code></b>'));
			else
				set_notice('warn', sprintf(_('The %s directory would be better if writable, in order to deliver automatic updates.<br/>It was impossible to determine the user running PHP. Use this command in your shell if possible: %s where www-data is an example (usually it\'s www-data or Apache)'), FCPATH, '<br/><b><code>chown -R www-data ' . FCPATH . '</code></b><br/>'));
			set_notice('warn', sprintf(_('If you can\'t do the above, you can follow the manual upgrade instructons at %sthis link%s.'), '<a href="http://trac.foolrulez.com/foolslide/wiki/installation_guide#Manualupgradeorifautomaticupgradebrokeeverything">', '</a>'));
			$prob = TRUE;
		}

		if ($prob) {
			set_notice('notice', 'If you made any changes, just refresh this page to recheck the directory permissions.');
		}
	}

	function _exec_enabled() {
		$disabled = explode(',', ini_get('disable_functions'));
		return!in_array('exec', $disabled);
	}

	/**
	 * Hi, I herd you liek upgrading, so I put an update for your upgrade, so you
	 * can update the upgrade before upgrading.
	 * 
	 * @author Woxxy
	 * @return bool 
	 */
	function update_upgrade() {
		if (!file_exists('content/cache/upgrade/application/models/upgrade2_model.php')) {
			return FALSE;
		}
		
		unlink('application/models/upgrade2_model.php');
		copy('content/cache/upgrade/application/models/upgrade2_model.php', 'application/models/upgrade2_model.php');
		
		return TRUE;
	}

	/**
	 * Does further checking, updates the upgrade2 "stage 2" file to accomodate
	 * changes to the upgrade script, updates the version number with the one
	 * from FoOlPod, and cleans up.
	 *
	 * @author Woxxy
	 * @return bool 
	 */
	function do_upgrade() {
		if (!$this->check_files()) {
			log_message('error', 'upgrade.php:_do_upgrade() check_files() failed');
			return false;
		}
		
		$new_versions = $this->upgrade_model->check_latest(TRUE);
		if ($new_versions === FALSE)
			return FALSE;

		// Pick the newest version
		$latest = $new_versions[0];

		$this->upgrade_model->get_file($latest->download, $latest->direct_download);
		
		$this->upgrade_model->update_upgrade();

		$this->load->model('upgrade2_model');
		if (!$this->upgrade2_model->do_upgrade()) {
			return FALSE;
		}

		// compatibility for FoOlSlide < 0.8.9 - By Woxxy at 20/10/2011
		$this->db->update('preferences', array('value' => $latest->version . '.' . $latest->subversion . '.' . $latest->subsubversion), array('name' => 'fs_priv_version'));
		$this->upgrade_model->clean();

		return TRUE;
	}

	/**
	 * Cleans up the upgrade folder
	 * 
	 * @author Woxxy
	 */
	function clean() {
		delete_files('content/cache/upgrade/', TRUE);
	}

}