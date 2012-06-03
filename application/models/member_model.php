<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


/**
 * FoOlFrame Members Model
 *
 * The Members Model deals with the members created by Tank Auth
 *
 * @package        	FoOlFrame
 * @subpackage    	Models
 * @category    	Models
 * @author        	FoOlRulez
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Member_model extends CI_Model
{


	/**
	 * Nothing special here 
	 */
	public function __construct()
	{
		parent::__construct();
	}


	/**
	 * The functions with 'p_' prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	public function __call($name, $parameters)
	{
		$before = $this->plugins->run_hook('fu_member_model_before_' . $name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the 
		// replaced function wont' be run
		$replace = $this->plugins->run_hook('fu_member_model_replace_' . $name, $parameters, array($parameters));

		if ($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			switch (count($parameters))
			{
				case 0:
					$return = $this->{'p_' . $name}();
					break;
				case 1:
					$return = $this->{'p_' . $name}($parameters[0]);
					break;
				case 2:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1]);
					break;
				case 3:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2]);
					break;
				case 4:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
					break;
				case 5:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
					break;
				default:
					$return = call_user_func_array(array(&$this, 'p_' . $name), $parameters);
					break;
			}
		}

		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = $this->plugins->run_hook('fu_member_model_after_' . $name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}


	/**
	 * Get all the users
	 * 
	 * @return array the row objects 
	 */
	private function p_get_all()
	{
		$query = $this->db->get('users');

		if ($query->num_rows() == 0)
		{
			return array();
		}

		return $query->result();
	}


	/**
	 * Get all the users together with their profile data
	 * 
	 * @return array the row objects with the profile data 
	 */
	private function p_get_all_with_profile()
	{
		$this->db->from('users');
		$this->db->join('profiles', 'users.id = profiles.user_id');
		$query = $this->db->get();

		if ($query->num_rows() == 0)
		{
			return array();
		}

		return $query->result();
	}


	/**
	 * Get data with profile for a single user by ID
	 * 
	 * @param int $id the user ID
	 * @return bool|object FALSE if not found, the row with profile if found 
	 */
	private function p_get($id)
	{
		// this actually calls members
		$query = $this->db->where('users.id', $id)
				->join('profiles', 'users.id = profiles.user_id')->get('users');

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}

}

/* End of file members_model.php */
/* Location: ./application/models/members_model.php */