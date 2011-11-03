<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Post extends DataMapper
{

	var $table = 'boardaposts';
	var $has_one = array();
	var $has_many = array();
	var $validation = array(
		'subnum' => array(
			'rules' => array(),
			'label' => 'Password'
		),
		'post_id' => array(
			'rules' => array(),
			'label' => 'Email',
			'type' => 'input'
		),
		'timestamp' => array(
			'rules' => array(),
			'label' => 'Activated'
		),
		'preview' => array(
			'rules' => array(),
			'label' => 'Banned'
		),
		'preview_w' => array(
			'rules' => array(),
			'label' => 'Ban reason'
		),
		'preview_h' => array(
			'rules' => array(),
			'label' => 'New password key'
		),
		'media' => array(
			'rules' => array(),
			'label' => 'New password request'
		),
		'media_w' => array(
			'rules' => array(),
			'label' => 'New email'
		),
		'media_h	' => array(
			'rules' => array(),
			'label' => 'New email key'
		),
		'media_size' => array(
			'rules' => array(),
			'label' => 'Last IP'
		),
		'media_hash' => array(
			'rules' => array(),
			'label' => 'Last login'
		),
		'media_filename' => array(
			'rules' => array(),
			'label' => 'Modified'
		),
		'spoiler' => array(
			'rules' => array(),
			'label' => 'New password key'
		),
		'deleted' => array(
			'rules' => array(),
			'label' => 'New password request'
		),
		'capcode' => array(
			'rules' => array(),
			'label' => 'New email'
		),
		'email' => array(
			'rules' => array(),
			'label' => 'New email key'
		),
		'name' => array(
			'rules' => array(),
			'label' => 'Last IP'
		),
		'trip' => array(
			'rules' => array(),
			'label' => 'Last login'
		),
		'title' => array(
			'rules' => array(),
			'label' => 'Modified'
		),
		'comment' => array(
			'rules' => array(),
			'label' => 'Last login'
		),
		'delpass' => array(
			'rules' => array(),
			'label' => 'Modified'
		)
	);

	function __construct($id = NULL)
	{
		parent::__construct($id);
	}


	function post_model_init($from_cache = FALSE)
	{
		
	}


	function get_thumbnail()
	{
		$echo = '';
		$number = $this->id;
		while (strlen((string) $number) < 9)
		{
			$number = '0' . $number;
		}

		return site_url() . 'board/a/thumb/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . $this->preview;
	}


	function get_comment()
	{
		$CI = & get_instance();
		$find = array(
			"(>>(\d+(?:,\d+)?))",
			"((\r?\n|^)(&gt;.*?)(?=$|\r?\n))",
			"'\[aa\](.*?)\[/aa\]'is",
			"'\[spoiler](.*?)\[/spoiler]'is",
			"'\[sup\](.*?)\[/sup\]'is",
			"'\[sub\](.*?)\[/sub\]'is",
			"'\[b\](.*?)\[/b\]'is",
			"'\[i\](.*?)\[/i\]'is",
			"'\[u\](.*?)\[/u\]'is",
			"'\[s\](.*?)\[/s\]'is",
			"'\[o\](.*?)\[/o\]'is",
			"'\[m\](.*?)\[/m\]'i",
			"'\[code\](.*?)\[/code\]'i",
			"'\[EXPERT\](.*?)\[/EXPERT\]'i",
			"'\[banned\](.*?)\[/banned\]'i",
		);

		$replace = array(
			'<a href="'.site_url($CI->fu_board . '/post/').'\\1">&gt;&gt;\\1</a>',
			'<span class="greentext">&gt;\\1</span>',
			'<span class="aa">\\1</span>',
			'<span class="spoiler">\\1</span>',
			'<sup>\\1</sup>',
			'<sub>\\1</sub>',
			'<strong>\\1</strong>',
			'<em>\\1</em>',
			'<span class="u">\\1</span>',
			'<span class="s">\\1</span>',
			'<span class="o">\\1</span>',
			'<tt class="code">\\1</tt>',
			'<code>\\1</code>',
			'<b><span class="u"><span class="o">\\1</span></span></b>',
			'<span class="banned">\\1</span>',
		);

		return nl2br(preg_replace($find, $replace, $this->comment));
	}


}