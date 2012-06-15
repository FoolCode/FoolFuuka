<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * FoOlFrame Profile Model
 *
 * The Profile Model deals with the Tank Auth profiles to give some
 * extra profile data for the users and change the group of the user
 *
 * @package        	FoOlFrame
 * @subpackage    	Models
 * @category    	Models
 * @author        	FoOlRulez
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Profile_model extends CI_Model
{


	/**
	 * Nothing special here 
	 */
	function __construct()
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
		$before = $this->plugins->run_hook('fu_profile_model_before_' . $name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the 
		// replaced function wont' be run
		$replace = $this->plugins->run_hook('fu_profile_model_replace_' . $name, $parameters, array($parameters));

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
		$after = $this->plugins->run_hook('fu_profile_model_after_' . $name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}
	

	/**
	 * The structure of a profile
	 * 
	 * @return array the structure 
	 */
	private function p_structure()
	{
		$arr = array(
			'open' => array(
				'type' => 'open',
			),
			'user_id' => array(
				'type' => 'hidden',
				'database' => TRUE,
				'label' => __('User ID'),
				'help' => __('The ID of the user'),
				'validation' => 'required|trim',
				'validation_func' => function($input, $form_internal)
				{
					// check that the ID exists
					$CI = & get_instance();

					// keep out who tries to edit others' profile without being mod or admin
					if ($CI->auth->get_user_id() !== $input['user_id'] && !$CI->auth->is_mod_admin())
					{
						return array(
							'error_code' => 'NOT_OWN_USER_ID',
							'error' => __('Non-admin user can\'t to change the data of another user.'),
							'critical' => TRUE
						);
					}

					// normal existence check
					$query = $CI->db->where('user_id', $input['user_id'])->get('profiles');
					if ($query->num_rows() != 1)
					{
						return array(
							'error_code' => 'USER_ID_NOT_FOUND',
							'error' => __('Couldn\'t find the user with the submitted ID.'),
							'critical' => TRUE
						);
					}

					return array('success' => TRUE);
				}
			)
		);


		// only admins get to change the groups
		if ($this->auth->is_admin())
		{
			$arr['group_id'] = array(
				'type' => 'radio',
				'database' => TRUE,
				'label' => __('Group'),
				'help' => __('Choose the permission level of the member'),
				'radio_values' => array(
					FOOL_AUTH_GROUP_ID_ADMIN => __('Administrator'),
					FOOL_AUTH_GROUP_ID_MOD => __('Moderator'),
					FOOL_AUTH_GROUP_ID_MEMBER => __('Member'),
				),
				'validation' => 'trim|greater_than[0]|less_than[4]'
			);
		}

		$arr['display_name'] = array(
			'type' => 'input',
			'database' => TRUE,
			'label' => __('Display Name'),
			'help' => __('A name that is publicly shown beside the username'),
			'validation' => 'min_length[3]|max_length[32]|alpha_dash',
			'class' => 'span3'
		);


		$arr['twitter'] = array(
			'type' => 'input',
			'database' => TRUE,
			'label' => __('Twitter'),
			'help' => __('The twitter nickname'),
			'validation' => 'min_length[3]|max_length[32]|alpha_dash',
			'class' => 'span3'
		);


		$arr['bio'] = array(
			'type' => 'textarea',
			'database' => TRUE,
			'label' => __('Bio'),
			'help' => __('A short description of the user'),
			'validation' => 'min_length[3]|max_length[140]',
			'class' => 'span5'
		);


		$arr['separator-2'] = array(
			'type' => 'separator-short'
		);
		$arr['submit'] = array(
			'type' => 'submit',
			'class' => 'btn-primary',
			'value' => __('Submit')
		);
		$arr['close'] = array(
			'type' => 'close'
		);

		return $arr;
	}


	/**
	 * Update the profile in the database
	 * 
	 * @param array $data the columns to update for the user
	 */
	private function p_save($data)
	{
		// data must be already sanitized through the form array
		$this->db->where('user_id', $data['user_id'])->update('profiles', $data);
	}

}

/* End of file profile_model.php */
/* Location: ./application/models/profile_model.php */