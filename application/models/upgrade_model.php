<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Upgrade_model extends CI_Model
{


	function __construct()
	{
		// Call the Model constructor
		parent::__construct();
	}


	/**
	 * Connects to GitHub to retrieve which is the latest tag from the API
	 *
	 * @param type $force forces returning the download even if FoOlSlide is up to date
	 * @return type FALSE or the download URL
	 */
	function check_latest($force = FALSE)
	{
		if (function_exists('curl_init'))
		{
			$this->load->library('curl');
			$result = $this->curl->simple_get(FOOL_GIT_TAGS_URL);
		}
		else
			$result = file_get_contents(FOOL_GIT_TAGS_URL);
		if (!$result)
		{
			set_notice('error',
				__('FoOlPod server could not be contacted: impossible to check for new versions.'));
			return FALSE;
		}
		$data = json_decode($result);

		$new_versions = array();
		foreach ($data as $new)
		{
			// if it's not a development system don't consider development versions
			if (ENVIRONMENT != 'development' && strpos($new->name, '-dev-') !== FALSE)
				continue;

			// ignore versions older than the current
			if (!$this->is_bigger_version(FOOL_VERSION, $new->name))
				continue;
			$new_versions[] = $new;
		}

		if (!empty($new_versions))
		{
			usort($new_versions, array($this, 'is_bigger_version'));
			return $new_versions;
		}

		if ($force)
		{
			$latest_version = null;
			foreach ($data as $new)
			{
				// if it's not a development system don't consider development versions
				if (ENVIRONMENT != 'development' && strpos($new->name, '-dev-') !== FALSE)
					continue;

				// if this is an older version (string match means that same version is OK)
				if (!$this->is_bigger_version(FOOL_VERSION, $new->name) && $new->name != FOOL_VERSION)
					continue;

				$latest_version = $new;
			}

			return array($latest_version);
		}

		return FALSE;
	}


	function get_changelog()
	{
		return file_get_contents(FOOL_GIT_CHANGELOG_URL);
	}


	/**
	 * Compares two versions and returns TRUE if second parameter is bigger than first, else FALSE
	 *
	 * @param type $maybemin
	 * @param type $maybemax
	 * @return bool
	 */
	function is_bigger_version($maybemin, $maybemax)
	{
		if(is_object($maybemin) && !isset($maybemin->version))
			$maybemin = $maybemin->name;
		if(is_object($maybemax) && !isset($maybemax->version))
			$maybemax = $maybemax->name;

		if (is_string($maybemin))
			$maybemin = $this->version_to_object($maybemin);
		if (is_string($maybemax))
			$maybemax = $this->version_to_object($maybemax);

		if (
			$maybemax->version > $maybemin->version
			|| (
				$maybemax->version == $maybemin->version
				&& $maybemax->subversion > $maybemin->subversion
			)
			|| (
				$maybemax->version == $maybemin->version
				&& $maybemax->subversion == $maybemin->subversion
				&& $maybemax->subsubversion > $maybemin->subsubversion
			)
			|| (
				$maybemax->version == $maybemin->version
				&& $maybemax->subversion == $maybemin->subversion
				&& $maybemax->subsubversion == $maybemin->subsubversion
				&& (
					$maybemax->devversion == 0 || $maybemax->devversion > $maybemin->devversion
				)
			)
		)
		{
			return -1;
		}
		return 1;
	}


	/**
	 * Converts the version from string separated by dots to object
	 *
	 * @author Woxxy
	 * @param type $string
	 * @return object
	 */
	function version_to_object($string)
	{
		if (substr($string, 0, 1) == 'v')
			$string = substr($string, 1);
		$version = explode('.', $string);

		$current = new stdClass();
		$current->version = $version[0];
		$current->subversion = $version[1];
		$current->subsubversion = $version[2];
		if (strpos($current->subsubversion, '-dev-') !== FALSE)
		{
			$dev_version = explode('-dev-', $current->subsubversion);
			$current->subsubversion = $dev_version[0];
			$current->devversion = $dev_version[1];
		}
		else
		{
			$current->devversion = 0;
		}
		return $current;
	}


	/**
	 *
	 * @author Woxxy
	 * @param string $url
	 * @return bool
	 */
	function get_file($url)
	{
		$this->clean();
		if (function_exists('curl_init'))
		{
			$this->load->library('curl');
			$zip = $this->curl->simple_get($url);
		}
		else
		{
			$zip = file_get_contents($url);
		}
		if (!$zip)
		{
			log_message('error',
				'upgrade_model get_file(): impossible to get the update from FoOlPod');
			flash_notice('error',
				__('Can\'t get the update file from FoOlPod. It might be a momentary problem, or a problem with your server security configuration. Browse <a href="http://foolrulez.com/pod/human">http://foolrulez.com/pod/human</a> to check if it\'s a known issue.'));
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
	function check_files()
	{
		if (!is_writable('.'))
		{
			return FALSE;
		}

		if (!is_writable('index.php'))
		{
			return FALSE;
		}

		if (!is_writable('application/models/upgrade2_model.php'))
		{
			return FALSE;
		}

		return TRUE;
	}


	function permissions_suggest()
	{
		$prob = FALSE;
		if (!is_writable('.'))
		{
			$whoami = FALSE;
			if ($this->_exec_enabled())
				$whoami = exec('whoami');
			if (!$whoami && is_writable('content') && function_exists('posix_getpwid'))
			{
				write_file('content/testing_123.txt', 'testing_123');
				$whoami = posix_getpwuid(fileowner('content/testing_123.txt'));
				$whoami = $whoami['name'];
				unlink('content/testing_123.txt');
			}
			if ($whoami != "")
				set_notice('warn',
					sprintf(__('The %s directory would be better if writable, in order to deliver automatic updates. Use this command in your shell if possible: %s'),
						FCPATH, '<br/><b><code>chown -R ' . $whoami . ' ' . FCPATH . '</code></b>'));
			else
				set_notice('warn',
					sprintf(__('The %s directory would be better if writable, in order to deliver automatic updates.<br/>It was impossible to determine the user running PHP. Use this command in your shell if possible: %s where www-data is an example (usually it\'s www-data or Apache)'),
						FCPATH, '<br/><b><code>chown -R www-data ' . FCPATH . '</code></b><br/>'));
			set_notice('warn',
				sprintf(__('If you can\'t do the above, you can follow the manual upgrade instructons at %sthis link%s.'),
					'<a href="http://trac.foolrulez.com/foolslide/wiki/installation_guide#Manualupgradeorifautomaticupgradebrokeeverything">',
					'</a>'));
			$prob = TRUE;
		}

		if ($prob)
		{
			set_notice('notice',
				'If you made any changes, just refresh this page to recheck the directory permissions.');
		}
	}


	function _exec_enabled()
	{
		$disabled = explode(',', ini_get('disable_functions'));
		return !in_array('exec', $disabled);
	}


	/**
	 * Hi, I herd you liek upgrading, so I put an update for your upgrade, so you
	 * can update the upgrade before upgrading.
	 *
	 * @author Woxxy
	 * @return bool
	 */
	function update_upgrade()
	{
		if (!file_exists('content/cache/upgrade/application/models/upgrade2_model.php'))
		{
			return FALSE;
		}

		unlink('application/models/upgrade2_model.php');
		copy('content/cache/upgrade/application/models/upgrade2_model.php',
			'application/models/upgrade2_model.php');

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
	function do_upgrade()
	{
		if (!$this->check_files())
		{
			log_message('error', 'upgrade.php:_do_upgrade() check_files() failed');
			return false;
		}

		$new_versions = $this->upgrade->check_latest(TRUE);
		if ($new_versions === FALSE)
			return FALSE;

		// Pick the newest version
		$latest = end($new_versions);

		$this->upgrade->get_file($latest->zipball_url);

		enable_maintenance(TRUE);
		$this->upgrade->update_upgrade();

		$this->load->model('upgrade2_model', 'upgrade2');
		if (!$this->upgrade2->do_upgrade())
		{
			disable_maintenance(TRUE);
			return FALSE;
		}
		disable_maintenance(TRUE);

		$this->upgrade->clean();

		return TRUE;
	}


	/**
	 * Cleans up the upgrade folder
	 *
	 * @author Woxxy
	 */
	function clean()
	{
		delete_files('content/cache/upgrade/', TRUE);
	}

}