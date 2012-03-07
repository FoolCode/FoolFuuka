<?php

if (! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * MY_Loader
 */
class MY_Loader extends CI_Loader
{


	public function database($params = '', $return = FALSE, $active_record = NULL)
	{
		// Grab the super object
		$CI =& get_instance();

		// Do we even need to load the database class?
		if (class_exists('CI_DB') AND $return == FALSE AND $active_record == NULL AND isset($CI->db) AND is_object($CI->db))
		{
			return FALSE;
		}


		// Check if custom DB file exists, else include core one
		if (file_exists(APPPATH.'core/'.config_item('subclass_prefix').'DB'.EXT))
		{
			require_once(APPPATH.'core/'.config_item('subclass_prefix').'DB'.EXT);
		}
		else
		{
			require_once(BASEPATH.'database/DB'.EXT);
		}

		if ($return === TRUE)
		{
			return DB($params, $active_record);
		}

		// Initialize the db variable. Needed to prevent
		// reference errors with some configurations
		$CI->db = '';

		// Load the DB class
		$CI->db =& DB($params, $active_record);
	}


}
