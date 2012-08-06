<?php

namespace \Foolfuuka\Themes\Fuuka;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Theme_Fu_Fuuka extends \Model_Base
{
	public static function greentext($html)
	{
		return '\\1<span class="greentext">\\2</span>\\3';
	}

	public static function process_internal_links_html($data, $html, $previous_result = NULL)
	{
		// a plugin with higher priority modified this
		if(!is_null($previous_result))
		{
			return array('return' => $previous_result);
		}

		return array('return' => array(
			'tags' => array('<span class="unkfunc">', '</span>'),
			'hash' => '',
			'attr' => 'class="backlink" onclick="replyHighlight(' . $data->num . ');"',
			'attr_op' => 'class="backlink"',
			'attr_backlink' => 'class="backlink"',
		));
	}
	
	

	public static function process_crossboard_links_html($data, $html, $previous_result = NULL)
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
}