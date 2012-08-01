<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');


class FU_Nginx_Cache_Purge extends Plugins_model
{
	
	
	function structure()
	{
		return array(
			'open' => array(
				'type' => 'open',
			),
			'fu_plugins_nginx_cache_purge_urls' => array(
				'type' => 'textarea',
				'preferences' => TRUE,
				'label' => __('Cache cleaning URLs'),
				'help' => __('Insert the URLs that Nginx Cache Purge will have to contact and their eventual Basic Auth passwords. Make sure you "allow" only the IP from this server on the Nginx Cache Purge configuration block. The following is the format:') . 
				'<pre style="margin-top:8px">http://0-cdn-archive.yourdomain.org/purge/:username1:yourpass
http://1-cdn-archive.yourdomain.org/purge/
http://2-cdn-archive.yourdomain.org/purge/:username2:password</pre>',
				'class' => 'span8',
				'validation' => 'trim'
			),
			'separator-2' => array(
				'type' => 'separator-short'
			),
			'submit' => array(
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => __('Submit')
			),
			'close' => array(
				'type' => 'close'
			),
		);
	}

	function initialize_plugin()
	{
		// don't add the admin panels if the user is not an admin
		if ($this->auth->is_admin())
		{
			Plugins::register_controller_function($this,
				array('admin', 'plugins', 'nginx_cache_purge'), 'manage');

			Plugins::register_admin_sidebar_element('plugins',
				array(
					"content" => array(
						"nginx_cache_purge" => array(
							"level" => "admin", 
							"name" => __("Nginx Cache Purge"), 
							"icon" => 'icon-leaf'
						),
					)
				)
			);
		}
		
		Plugins::register_hook($this, 'fu_post_model_before_delete_media', 5, 'before_delete_media');
	}


	function manage()
	{
		$this->viewdata['controller_title'] = '<a href="' . Uri::create("admin/plugins/nginx_cache_purge") . '">' . __("Nginx Cache Purge") . '</a>';
		$this->viewdata['function_title'] = __('Manage');

		if($this->input->post())
		{
			set_setting('fu_plugins_nginx_cache_purge_urls', $this->input->post('fu_plugins_nginx_cache_purge_urls'));
		}
		
		$data['form'] = $this->structure();
		
		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}
	
	
	function before_delete_media($board, $post, $media = TRUE, $thumb = TRUE)
	{
		$dir = array();
		if($media)
		{
			$dir['full'] = $this->post->get_media_link($board, $post, FALSE);
		}
		
		if($thumb)
		{
			$dir['thumb'] = $this->post->get_media_link($board, $post, TRUE);
		}
		
		$url_user_password = $this->parse_urls();
		
		// Phil Sturgeon's curl library
		$this->load->library('curl');
		
		foreach($url_user_password as $item)
		{
			foreach($dir as $d)
			{
				$this->curl->create($item['url'] . parse_url($d, PHP_URL_PATH));
				if(isset($item['password']))
					$this->curl->http_login($item['username'], $item['password']);
				$this->curl->execute();
			}
		}
		
		return NULL;
	}
	
	
	function parse_urls()
	{
		$text = get_setting('fu_plugins_nginx_cache_purge_urls');
		
		if(!$text)
		{
			return array();
		}
		
		$lines = preg_split('/\r\n|\r|\n/', $text);
		
		$lines_exploded = array();
		
		foreach($lines as $key => $line)
		{
			$explode = explode(':', $line);

			if(count($explode) == 0)
			{
				continue;
			}
			
			if(count($explode) > 1)
				$lines_exploded[$key]['url'] = rtrim(array_shift($explode) . ':' . array_shift($explode), '/');
			
			if(count($explode) > 1)
			{
				$lines_exploded[$key]['username'] = array_shift($explode);
				$lines_exploded[$key]['password'] = implode(':', $explode);
			}
		}
		
		return $lines_exploded;
	}


}