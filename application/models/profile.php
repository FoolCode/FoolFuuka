<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Profile extends CI_Model
{


	function __construct($id = NULL)
	{
		parent::__construct();
	}


	function structure()
	{
		$arr = array(
			'open' => array(
				'type' => 'open',
			),
			'user_id' => array(
				'type' => 'hidden',
				'database' => TRUE,
				'validation' => 'required|trim',
				'validation_func' => function($input, $form_internal)
				{
					// check that the ID exists
					$CI = & get_instance();

					// keep out who tries to edit others' profile without being mod or admin
					if ($CI->tank_auth->get_user_id() !== $input['user_id'] && !$CI->tank_auth->is_admin())
					{
						return array(
							'error_code' => 'NOT_OWN_USER_ID',
							'error' => _('Non-admin user can\'t to change the data of another user.'),
							'critical' => TRUE
						);
					}

					// normal existence check
					$query = $CI->db->where('user_id', $input['user_id'])->get('profiles');
					if ($query->num_rows() != 1)
					{
						return array(
							'error_code' => 'USER_ID_NOT_FOUND',
							'error' => _('Couldn\'t find the user with the submitted ID.'),
							'critical' => TRUE
						);
					}

					return array('success' => TRUE);
				}
			)
		);


		// only admins get to change the groups
		if ($this->tank_auth->is_admin())
		{
			$arr['group_id'] = array(
				'type' => 'radio',
				'database' => TRUE,
				'radio_values' => array(
					'1' => _('Administrator'),
					'2' => _('Member'),
					'3' => _('Moderator')
				),
				'validation' => 'trim|less_than[1]|greater_than[3]'
			);
		}
		
		$arr['display_name'] = array(
			'type' => 'text',
			'database' => TRUE,
			'validation' => 'min_length[3]|max_length[32]|alpha_dash'
		);
		
		
		$arr['twitter'] = array(
			'type' => 'text',
			'database' => TRUE,
			'validation' => 'min_length[3]|max_length[32]|alpha_dash'
		);
		
		
		$arr['bio'] = array(
			'type' => 'textarea',
			'database' => TRUE,
			'validation' => 'min_length[3]|max_length[140]'
		);
		
		
		$arr['separator-2'] = array(
			'type' => 'separator-short'
		);
		$arr['submit'] = array(
			'type' => 'submit',
			'class' => 'btn-primary',
			'value' => _('Submit')
		);
		$arr['close'] = array(
			'type' => 'close'
		);
	}

	
	
	function save($data)
	{
		// data must be already sanitized through the form array
		$this->db->where('user_id', $data['user_id'])->update('profiles', $data);
	}
}