<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{


	function __construct()
	{
		parent::__construct();
		
		// create an array for the set_notice system
		$this->notices = array();
		$this->flash_notice_data = array();

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
			$this->load->model('plugins_model', 'plugins');
			$this->plugins->load_plugins();

			// loads variables from database for get_setting()
			load_settings();

			// load the radixes (boards)
			$this->load->model('radix_model', 'radix');
			$this->load->model('vote_model', 'vote');

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

			MY_Controller::cron();
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
			return FALSE;
		}

		
		if ($this->plugins->is_controller_function($this->uri->segment_array()))
		{
			$plugin_controller = $this->plugins->get_controller_function($this->uri->segment_array());
			$uri_array = $this->uri->segment_array();
			array_shift($uri_array);
			array_shift($uri_array);
			array_shift($uri_array);

			return call_user_func_array(array($plugin_controller['plugin'], $plugin_controller['method']),
				$uri_array);
		}
		
		/*
		 * @todo figure out if this was necessary: it currently goes running functions 
		 * from routes that sent here because the function didn't exist in first place
		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		*/
		
		return FALSE;
	}
	
	/**
	 * Returns the basic variables that are used by the public interface and the admin panel for dealing with posts
	 * 
	 * @return array the settings to be sent directly to JSON 
	 */
	public function get_backend_vars()
	{
		return array(
				'site_url'  => site_url(),
				'default_url'  => site_url('@default'),
				'archive_url'  => site_url('@archive'),
				'system_url'  => site_url('@system'),
				'api_url'   => site_url('@system'),
				'cookie_domain' => config_item('cookie_domain'),
				'csrf_hash' => $this->security->get_csrf_hash(),
				'images' => array(
					'banned_image' => site_url() . 'content/themes/default/images/banned-image.png',
					'banned_image_width' => 150,
					'banned_image_height' => 150,
					'missing_image' => site_url() . 'content/themes/default/images/missing-image.jpg',
					'missing_image_width' => 150,
					'missing_image_height' => 150,
				),
				'gettext' => array(
					'submit_state' => __('Submitting'),
					'thread_is_real_time' => __('This thread is being displayed in real time.'),
					'update_now' => __('Update now')
				)
			);
	}

	/**
	 * A function shared between admin panel and boards for admins to manage the posts
	 */
	public function mod_post_actions()
	{
		if (!$this->tank_auth->is_allowed())
		{
			$this->output->set_status_header(403);
			$this->output->set_output(json_encode(array('error' => __('You\'re not allowed to perform this action'))));
		}

		if (!$this->input->post('actions') || !$this->input->post('doc_id') || !$this->input->post('board'))
		{
			$this->output->set_status_header(404);
			$this->output->set_output(json_encode(array('error' => __('Missing arguments'))));
		}


		// action should be an array
		// array('ban_md5', 'ban_user', 'remove_image', 'remove_post', 'remove_report');
		$actions = $this->input->post('actions');
		if (!is_array($actions))
		{
			$this->output->set_status_header(404);
			$this->output->set_output(json_encode(array('error' => __('Invalid action'))));
		}

		$doc_id = $this->input->post('doc_id');
		$board = $this->radix->get_by_shortname($this->input->post('board'));

		$this->load->model('post_model', 'post');
		$post = $this->post->get_by_doc_id($board, $doc_id);

		if ($post === FALSE)
		{
			$this->output->set_status_header(404);
			$this->output->set_output(json_encode(array('error' => __('Post not found'))));
		}

		if (in_array('ban_md5', $actions))
		{
			$this->post->ban_media($post->media_hash);
			$actions = array_diff($actions, array('remove_image'));
		}

		if (in_array('remove_post', $actions))
		{
			$this->post->delete(
				$board,
				array(
				'doc_id' => $post->doc_id,
				'password' => '',
				'type' => 'post'
				)
			);

			$actions = array_diff($actions, array('remove_image', 'remove_report'));
		}

		// if we banned md5 we already removed the image
		if (in_array('remove_image', $actions))
		{
			$this->post->delete_media($board, $post);
		}

		if (in_array('ban_user', $actions))
		{
			$this->load->model('poster_model', 'poster');
			$this->poster->ban(
				$post->id, isset($data['length']) ? $data['length'] : NULL,
				isset($data['reason']) ? $data['reason'] : NULL
			);
		}

		if (in_array('remove_report', $actions))
		{
			$this->load->model('report_model', 'report');
			$this->report->remove_by_doc_id($board, $doc_id);
		}

		$this->output->set_output(json_encode(array('success' => TRUE)));
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

		// every 10 minutes
		// only needed for asagi autorun
		if(get_setting('fs_asagi_autorun_enabled') && time() - $last_check > 600)
		{
			set_setting('fs_cron_10m', time());
			
			if('fs_asagi_autorun_enabled')
			{
				$this->load->model('asagi_model', 'asagi');
				$this->asagi->run();
			}
			
		}
		
		// every 13 hours
		if (false && time() - $last_check > 86400)
		{
			set_setting('fs_cron_13h', time());

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
				set_setting('fs_cron_13h', time());
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