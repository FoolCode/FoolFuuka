<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * FoOlFuuka Poster Model
 *
 * The Poster Model deals with user bans
 *
 * @package        	FoOlFrame
 * @subpackage    	FoOlFuuka
 * @category    	Models
 * @author        	FoOlRulez
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Poster_model extends CI_Model
{


	public function __construct()
	{
		parent::__construct(NULL);
	}
	
	
	/**
	 * The functions with 'p_' prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	public function __call($name, $parameters)
	{
		$before = $this->plugins->run_hook('fu_poster_model_before_' . $name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the 
		// replaced function wont' be run
		$replace = $this->plugins->run_hook('fu_poster_model_replace_' . $name, $parameters, array($parameters));

		if($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			switch (count($parameters)) {
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
		$after = $this->plugins->run_hook('fu_poster_model_after_' . $name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}
	
	
	/**
	 * This bans people
	 * 
	 * @param int $decimal_ip the IP in decimal form
	 * @param int $length_in_hours the length in hours of the ban
	 * @param String $reason the reason of the ban
	 */
	private function p_ban($decimal_ip, $length_in_hours = NULL, $reason = NULL)
	{
		$query = $this->db->where('ip', $decimal_ip)->get('posters');
		
		if($query->num_rows() == 0)
		{
			$this->db->insert('posters', array(
				'ip' => $decimal_ip,
				'banned' => 1,
				'banned_start' => date('Y-m-d H:i:s'),
				'banned_length' => $length_in_hours,
				'banned_reason' => $reason
			));
		}
		else
		{
			$this->db->where('ip', $decimal_ip)->update('posters', array(
				'banned' => 1,
				'banned_start' => date('Y-m-d H:i:s'),
				'banned_length' => $length_in_hours,
				'banned_reason' => $reason
			));
		}
	}
	
	private function p_unban($decimal_ip)
	{
		$this->db->where('ip', $decimal_ip)->get('posters');
		
		if($this->num_rows() == 1)
		{
			$this->db->where('ip', $decimal_ip)->update('posters', array(
				'banned' => 0
			));
		}
	}
	
	/**
	 * Checks if a person is banned. Returns the poster object if banned, else FALSE
	 * 
	 * @param type $decimal_ip the IP in decimal form
	 * @return bool|object FALSE if not banned, the row if banned  
	 */
	private function p_is_banned($decimal_ip)
	{
		$query = $this->db->where('ip', $decimal_ip)->get('posters');
		
		if($query->num_rows() > 0)
		{
			$row = $query->row();
			
			if($row->banned && (
				strtotime($row->banned_start) + ($row->banned_length * 60 * 60) < time()
			))
			{
				return $row;
			}
		}
		
		return FALSE;
	}

}


/* End of file poster_model.php */
/* Location: ./application/models/poster_model.php */