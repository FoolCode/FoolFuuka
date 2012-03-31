<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class MY_Controller extends CI_Controller
{


	function __construct()
	{
		parent::__construct();

		if (!file_exists(FCPATH . "config.php"))
		{
			if ($this->uri->segment(1) != "install")
				show_error("If you are here, and have no clue why " . FOOL_NAME . " is not working, start by reading the <a href='" . FOOL_MANUAL_INSTALL_URL . "'>installation manual</a>.");
		}
		else
		{
			//$this->output->enable_profiler(TRUE);
			$this->load->database();
			$this->load->library('session');
			$this->load->library('tank_auth');

			// plugin system as early we can without losing on security
			$this->load->model('plugins');
			$this->plugins->load_plugins();

			// loads variables from database for get_setting()
			load_settings();

			// load the radixes (boards)
			$this->load->model('radix');
			$this->load->model('vote');

			// create an array for the set_notice system
			$this->notices = array();
			$this->flash_notice_data = array();

			// This is the first chance we get to load the right translation file
			if (get_setting('fs_gen_lang'))
			{
				$locale = get_setting('fs_gen_lang');
				putenv("LANG=$locale");
				if ($locale != "tr_TR.utf8")
				{
					setlocale(LC_ALL, $locale);
				}
				else // workaround to make turkish work with FoOlSlide
				{
					setlocale(LC_COLLATE, $locale);
					setlocale(LC_MONETARY, $locale);
					setlocale(LC_NUMERIC, $locale);
					setlocale(LC_TIME, $locale);
					setlocale(LC_MESSAGES, $locale);
					setlocale(LC_CTYPE, "sk_SK.utf8");
				}
				bindtextdomain("default", FCPATH . "assets/locale");
				textdomain("default");
			}

			// a good time to change some of the defauly settings dynamically
			$this->config->config['tank_auth']['allow_registration'] = !get_setting('fs_reg_disabled');

			$this->config->config['tank_auth']['email_activation'] = ((get_setting('fs_reg_email_disabled'))
						? FALSE : TRUE);

			$captcha_public = get_setting('fs_reg_recaptcha_public');
			if ($captcha_public != "")
			{
				$captcha_secret = get_setting('fs_reg_recaptcha_secret');
				if ($captcha_secret != "")
				{
					$this->config->config['tank_auth']['use_recaptcha'] = TRUE;
					$this->config->config['tank_auth']['recaptcha_public_key'] = $captcha_public;
					$this->config->config['tank_auth']['recaptcha_secret_key'] = $captcha_secret;
				}
			}

			$this->cron();
		}
	}


	/**
	 * Alternative remap function that works with the plugin system
	 * 
	 * @param string $method
	 * @param type $params
	 * @return type 
	 */
	public function _remap($method, $params = array())
	{
		if ($method == 'plugin')
		{
			// don't let people go directly to the plugin system
			show_404();
		}

		if ($this->plugins->is_controller_function($this->uri->segment_array()))
		{
			$plugin_controller = $this->plugins->get_controller_function($this->uri->segment_array());

			return call_user_func_array(array($plugin_controller['plugin'], $plugin_controller['method']),
					array());
		}
		
		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}

	
	/**
	 * Controller for cron triggered by any visit
	 * Currently defaulted crons:
	 * -updates every 13 hours the blocked IPs
	 *
	 * @author Woxxy
	 */
	public function cron()
	{
		$last_check = get_setting('fs_cron_stopforumspam');

		// every 13 hours
		if (time() - $last_check > 86400)
		{
			$this->db->query('
				INSERT
				INTO ' . $this->db->protect_identifiers('preferences',
					TRUE) . '
				(name, value) VALUES (?, ?)
				ON DUPLICATE KEY UPDATE
				value = VALUES(value)
			',
				array('fs_cron_stopforumspam', time()));

			$url = 'http://www.stopforumspam.com/downloads/listed_ip_90.zip';
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
					'MY_Controller cron(): impossible to get the update from stopforumspam');
				$this->db->update('preferences', array('value' => time()),
					array('name' => 'fs_cron_stopforumspam'));
				return FALSE;
			}

			delete_files('content/cache/stopforumspam/', TRUE);
			if (!is_dir('content/cache/stopforumspam'))
				mkdir('content/cache/stopforumspam');
			write_file('content/cache/stopforumspam/stopforumspam.zip', $zip);
			$this->load->library('unzip');
			$this->unzip->extract('content/cache/stopforumspam/stopforumspam.zip');
			$ip_list = file_get_contents('content/cache/stopforumspam/listed_ip_90.txt');

			$this->db->truncate('stopforumspam');
			$ip_array = array();
			foreach (preg_split("/((\r?\n)|(\r\n?))/", $ip_list) as $line)
			{
				$ip_array[] = '(INET_ATON(' . $this->db->escape($line) . '))';
			}
			$this->db->query('
				INSERT IGNORE INTO ' . $this->db->protect_identifiers('stopforumspam',
					TRUE) . '
				VALUES ' . implode(',', $ip_array) . ';');


			delete_files('content/cache/stopforumspam/', TRUE);
		}
	}

}