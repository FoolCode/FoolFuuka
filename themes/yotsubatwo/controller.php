<?php

namespace Foolfuuka\Themes\Yotsubatwo;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Controller_Theme_Fu_Yotsubatwo_Chan extends \Foolfuuka\Controller_Chan
{
	public function action_page($page = 1)
	{		
		$order = \Cookie::get('default_theme_page_mode_'. ($this->_radix->archive ? 'archive' : 'board')) === 'by_thread'
			? 'by_thread' : 'by_post';

		$options = array(
			'per_page' => 24,
			'per_thread' => 6,
			'order' => $order
		);

		return $this->latest($page, $options);
	}

}
