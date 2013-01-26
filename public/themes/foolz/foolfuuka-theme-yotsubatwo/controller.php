<?php

namespace Foolz\Foolfuuka\Themes\Yotsubatwo\Controller;

if ( ! defined('DOCROOT'))
	exit('No direct script access allowed');

class Chan extends \Foolz\Foolfuuka\Controller\Chan
{
	public function action_page($page = 1)
	{
		$order = \Cookie::get('default_theme_page_mode_'. ($this->_radix->archive ? 'archive' : 'board')) === 'by_thread'
			? 'by_thread' : 'by_post';

		$options = [
			'per_page' => 24,
			'per_thread' => 6,
			'order' => $order
		];

		return $this->latest($page, $options);
	}
}