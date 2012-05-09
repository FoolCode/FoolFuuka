<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Profile_model extends CI_Model
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
				'label' => __('User ID'),
				'help' => __('The ID of the user'),
				'validation' => 'required|trim',
				'validation_func' => function($input, $form_internal)
				{
					// check that the ID exists
					$CI = & get_instance();

					// keep out who tries to edit others' profile without being mod or admin
					if ($CI->tank_auth->get_user_id() !== $input['user_id'] && !$CI->tank_auth->is_allowed())
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
		if ($this->tank_auth->is_admin())
		{
			$arr['group_id'] = array(
				'type' => 'radio',
				'database' => TRUE,
				'label' => __('Group'),
				'help' => __('Choose the permission level of the member'),
				'radio_values' => array(
					'1' => __('Administrator'),
					'3' => __('Moderator'),
					'2' => __('Member'),
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


	function save($data)
	{
		// data must be already sanitized through the form array
		$this->db->where('user_id', $data['user_id'])->update('profiles', $data);
	}

}