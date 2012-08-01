<?php

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Controller_Theme_Fu_Yotsubatwo_Chan extends \Controller_Common
{
	
	public function page($page = 1)
	{
		return array('parameters' => array($page, FALSE, array('per_page' => 24, 'type' => 'by_thread')));
	}

}
