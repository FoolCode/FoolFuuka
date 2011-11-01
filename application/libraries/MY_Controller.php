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
				show_error("If you are here, and have no clue why FoOlSlide is not working, start by reading the <a href='http://trac.foolrulez.com/foolslide/wiki/installation_guide'>installation manual</a>.");
		} else
		{
			$this->load->database();
			$this->load->library('session');
			$this->load->library('datamapper');
			$this->load->library('tank_auth');

			// loads variables from database for get_setting()
			load_settings();
			
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

			// set the nationality where possible, and leave ignored ips without a nation
			$ignored_ips = array();
			if (get_setting('fs_balancer_ips'))
				$ignored_ips = @unserialize(get_setting('fs_balancer_ips'));
			$ignored_ips[] = '127.0.0.1';
			$remote_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
			if ($this->session->userdata('nation') !== FALSE || !in_array($remote_addr, $ignored_ips))
			{
				// If the user doesn't have a nation set, let's grab it
				require_once("assets/geolite/GeoIP.php");
				$gi = geoip_open("assets/geolite/GeoIP.dat", GEOIP_STANDARD);
				$nation = geoip_country_code_by_addr($gi, $remote_addr);
				geoip_close($gi);
				$this->session->set_userdata('nation', $nation);
			}
			else if ($this->session->userdata('nation'))
			{
				$this->session->set_userdata('nation', '');
			}

			// a good time to change some of the defauly settings dynamically
			$this->config->config['tank_auth']['allow_registration'] = !get_setting('fs_reg_disabled');

			$this->config->config['tank_auth']['email_activation'] = ((get_setting('fs_reg_email_disabled')) ? FALSE : TRUE);

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
		}
	}


}