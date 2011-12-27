<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * READER CONTROLLER
 *
 * This file allows you to override the standard FoOlSlide controller to make
 * your own URLs for your theme, and to make sure your theme keeps working
 * even if the FoOlSlide default theme gets modified.
 *
 * For more information, refer to the support sites linked in your admin panel.
 */

class Theme_Controller {

	function __construct() {
		$this->CI = & get_instance();
	}


	public function thread($num)
	{
		$num = str_replace('S', '', $num);
		if (!is_numeric($num) || !$num > 0)
		{
			show_404();
		}

		$num = intval($num);

		$thread = $this->CI->post->get_thread($num);

		if (!is_array($thread))
		{
			show_404();
		}

		$this->CI->template->title('/' . get_selected_board()->shortname . '/ - ' . get_selected_board()->name . ' - Thread #' . $num);
		$this->CI->template->set('posts', $thread);

		$this->CI->template->set('thread_id', $num);
		//$this->CI->template->set_partial('post_reply', 'post_reply', array('thread_id' => $num, 'post_data' => $post_data));
		$this->CI->template->build('board');
	}

	public function thread_o_matic()
	{
		show_404();
	}

}