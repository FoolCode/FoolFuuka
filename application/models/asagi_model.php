<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Asagi_model extends CI_Model
{
	var $asagi_git_url = 'https://github.com/downloads/woxxy/asagi/asagi.jar';
	
	function __construct()
	{
		parent::__construct();
	}
	
	function is_installed()
	{
		return file_exists('content/asagi/asagi.jar');
	}
	
	
	function get_settings()
	{
		$archives = $this->radix->get_archives();
		
		$settings = array(
			'settings' => array(
				'default' => array(
					'engine' => 'Mysql',
					'host' => 'localhost',
					'database' => get_setting('fs_fuuka_boards_db')?:$this->db->database,
					'username' => $this->db->username,
					'password' => $this->db->password,
					'path' => FCPATH . 'content/boards',
					'webserverGroup'=> get_webserver_group()?:'_www',
					'thumbThreads' => 5,
					'mediaThreads' => 5,
					'newThreadsThreads' => 5,
					'threadRefreshRate' =>3,
					'pageSettings' => array(
						array('delay' => 30, 'pages' => array(0, 1, 2)),
						array('delay' => 120, 'pages' => array(3, 4, 5, 6, 7, 8, 9, 10, 11, 12)),
						array('delay' => 30, 'pages' => array(13, 14,15))
					)
				)
			)
		);
		
		foreach($archives as $archive)
		{
			$settings['settings'][$archive->shortname] = array(
				'thumbThreads' => $archive->thumb_threads,
				'mediaThreads' => $archive->media_threads,
				'newThreadsThreads' => $archive->new_threads_threads,
				'threadRefreshRate' => $archive->thread_refresh_rate,
			);
			
			if(!get_setting('fs_fuuka_boards_db'))
			{
				$settings['settings'][$archive->shortname]['table'] = $this->db->dbprefix('board_' . $archive->shortname);
			}
			
			if($archive->page_settings)
				$settings['settings'][$archive->shortname]['pageSettings'] = @json_decode($archive->page_settings);
		}
		
		return $settings;
	}
	
	
	function install()
	{
		$this->load->helper('directory');
		$this->remove();
		
		if (function_exists('curl_init'))
		{
			$this->load->library('curl');
			$zip = $this->curl->simple_get($this->asagi_git_url);
		}
		else
		{
			$zip = file_get_contents($this->asagi_git_url);
		}
		
		if(!$zip)
		{
			return array('error' => __('The Asagi ZIP file couldn\'t be downloaded.'));
		}
		
		if (!is_dir('content/asagi'))
			mkdir('content/asagi');
		
		/*
		 * @todo: Rather use a ZIP with complete GPLv3 contents, or maybe having it in admin panel is enough 
		 */
		write_file('content/asagi/asagi.jar', $zip);
	}
	
	function remove()
	{
		delete_files('content/asagi', TRUE);
	}
	
	
	function is_running()
	{
		$output = array();
		exec('screen -ls', $output);
		if(count($output) > 3)
		{
			array_shift($output);
			array_pop($output); // number of sockets
			array_pop($output); // an empty line
			
			foreach($output as $o)
			{
				if(strpos($o, '.asagi ') !== FALSE)
				{
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	
	
	function run()
	{
		if(!$this->is_running())
			exec('screen -dmS "asagi" sh -c "cd ' .  FCPATH . 'content/asagi/; while true; do php ' . FCPATH . 'index.php cli asagi_get_settings | java -jar asagi.jar; done"');		
	}
	
	
	function kill()
	{
		exec('screen -S asagi -X quit');
	}
	
}