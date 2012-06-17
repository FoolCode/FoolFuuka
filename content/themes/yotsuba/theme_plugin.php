<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Theme_Plugin_yotsuba extends Plugins_model
{

	function __construct()
	{
		parent::__construct();
	}


	function initialize_plugin()
	{
		// use hooks for manipulating comments
		$this->plugins->register_hook($this, 'fu_post_model_process_comment_greentext_result', 8, '_greentext');
		$this->plugins->register_hook($this, 'fu_post_model_process_internal_links_html_result', 8,
			'_process_internal_links_html');

		$this->plugins->register_hook($this, 'fu_post_model_process_crossboard_links_html_result', 8,
			'_process_crossboard_links_html');

		$this->plugins->register_hook($this, 'fu_chan_controller_before_page', 3, 'page');
		$this->plugins->register_hook($this, 'fu_chan_controller_before_gallery', 3, function(){ show_404(); });
	}


	function _greentext($html)
	{
		return '\\1<span class="quote">\\2</span>\\3';
	}


	function _process_internal_links_html($data, $html, $previous_result = NULL)
	{
		// a plugin with higher priority modified this
		if(!is_null($previous_result))
		{
			return array('return' => $previous_result);
		}

		return array('return' => array(
			'tags' => array('<span class="quote">', '</span>'),
			'hash' => '',
			'attr' => 'class="quotelink"',
			'attr_op' => 'class="backlink"',
			'attr_backlink' => 'class="backlink"',
		));
	}


	function _process_crossboard_links_html($data, $html, $previous_result = NULL)
	{
		// a plugin with higher priority modified this
		if(!is_null($previous_result))
		{
			return array('return' => $previous_result);
		}

		return array('return' => array(
			'tags' => array('<span class="unkfunc">', 'suffix' => '</span>'),
			'backlink' => ''
		));
	}


	/**
	 * @param int $page
	 */
	public function page($page = 1)
	{
		return array('parameters' => array($page, FALSE, array('per_page' => 24, 'type' => 'by_thread')));
	}


}
