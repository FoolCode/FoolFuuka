<?php

\Autoloader::add_classes(array(
    'StringParser_BBCode' => __DIR__.'/library/stringparser_bbcode.class.php',
));


/*

		// add list of bbcode for formatting
		$codes[] = array('code', 'simple_replace', NULL, array('start_tag' => '<code>', 'end_tag' => '</code>'), 'code',
			array('block', 'inline'), array());
		$codes[] = array('spoiler', 'simple_replace', NULL,
			array('start_tag' => '<span class="spoiler">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));
		$codes[] = array('sub', 'simple_replace', NULL, array('start_tag' => '<sub>', 'end_tag' => '</sub>'), 'inline',
			array('block', 'inline'), array('code'));
		$codes[] = array('sup', 'simple_replace', NULL, array('start_tag' => '<sup>', 'end_tag' => '</sup>'), 'inline',
			array('block', 'inline'), array('code'));
		$codes[] = array('b', 'simple_replace', NULL, array('start_tag' => '<b>', 'end_tag' => '</b>'), 'inline',
			array('block', 'inline'), array('code'));
		$codes[] = array('i', 'simple_replace', NULL, array('start_tag' => '<em>', 'end_tag' => '</em>'), 'inline',
			array('block', 'inline'), array('code'));
		$codes[] = array('m', 'simple_replace', NULL, array('start_tag' => '<tt class="code">', 'end_tag' => '</tt>'),
			'inline', array('block', 'inline'), array('code'));
		$codes[] = array('o', 'simple_replace', NULL, array('start_tag' => '<span class="overline">', 'end_tag' => '</span>'),
			'inline', array('block', 'inline'), array('code'));
		$codes[] = array('s', 'simple_replace', NULL,
			array('start_tag' => '<span class="strikethrough">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));
		$codes[] = array('u', 'simple_replace', NULL,
			array('start_tag' => '<span class="underline">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));
		$codes[] = array('EXPERT', 'simple_replace', NULL,
			array('start_tag' => '<span class="expert">', 'end_tag' => '</span>'), 'inline', array('block', 'inline'),
			array('code'));

		foreach($codes as $code)
		{
			if($strip)
			{
				$code[1] = 'callback_replace';
				$code[2] = 'strip_unused_bbcode';
			}

			$bbcode->addCode($code[0], $code[1], $code[2], $code[3], $code[4], $code[5], $code[6]);
		}

		// if $special == TRUE, add special bbcode
		if ($special === TRUE)
		{
			if ($CI->theme->get_selected_theme() == 'fuuka' || $CI->theme->get_selected_theme() == 'yotsuba')
			{
				$bbcode->addCode('moot', 'simple_replace', NULL,
					array('start_tag' => '<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">', 'end_tag' => '</div>'),
					'inline', array('block', 'inline'), array());
			}
			else
			{
				$bbcode->addCode('moot', 'simple_replace', NULL, array('start_tag' => '', 'end_tag' => ''), 'inline',
					array('block', 'inline'), array());
			}
		}

		return $bbcode->parse($str);
	}

}

if (!function_exists('strip_unused_bbcode'))
{
	/**
	 * Callback for parse_bbcode to filter out tags without content inside
	 * It also changes <code> to <pre> in case there's multiple lines into it
	 *
	 * @param type $action
	 * @param type $attributes
	 * @param type $content
	 * @param type $params
	 * @param type $node_object
	 * @return string
	 *//*
	function strip_unused_bbcode($action, $attributes, $content, $params, &$node_object)
	{
		if($content === '' || $content === FALSE)
			return '';

		// if <code> has multiple lines, wrap it in <pre> instead
		if($params['start_tag'] == '<code>')
		{
			if(count(array_filter(preg_split('/\r\n|\r|\n/', $content))) > 1)
			{
				return '<pre>' . $content . '</pre>';
			}
		}

		return $params['start_tag'] . $content . $params['end_tag'];
	}
}*/