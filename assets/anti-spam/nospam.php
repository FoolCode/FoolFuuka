<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class NoSpam {


	var $spam_database = array();
	var $spam_compiled;


	function compile_spam_database($data = NULL)
	{
		switch ($data)
		{
			case "md5":
				break;

			default:
				$this->spam_database['urls'] = file_get_contents(FCPATH . "assets/anti-spam/databases/urls.dat");
		}
	}


	function is_spam($data = array(), $hash = FALSE)
	{
		if (!is_array($data) || empty($this->spam_database) || empty($data))
		{
			return FALSE;
		}

		foreach ($this->spam_database as $database => $keys)
		{
			$keys = explode("\n", $keys);
			foreach ($keys as $key)
			{
				if ($hash === TRUE)
				{
					$key = preg_quote(trim($key), '#');
					$pattern = "#$key#";
				}
				else
				{
					$key = preg_quote(strtolower(trim($key)), '#');
					$pattern = "#$key#i";
				}

				foreach ($data as $field => $name)
				{
					if (!is_array($name) && preg_match($pattern, $name))
					{
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}
}