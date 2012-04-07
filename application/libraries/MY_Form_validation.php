<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class MY_Form_validation extends CI_Form_validation
{

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Checks the form for and returns either a compiled array of values or
	 * the error
	 * 
	 * $form array 
	 * $alternate array name/value pairs to use instead of the POST array 
	 */
	public function form_validate($form, $alternate = NULL)
	{
		$CI = & get_instance();
		
		// allow overriding the POST array
		$temp_post = $CI->input->post();
		if (is_array($alternate))
		{
			$_POST = $alternate;
			$this->_reset_post_array();
		}

		
		// this gets a bit complex because we want to show all errors at the same
		// time, which means we have to run both CI validation and custom, then
		// merge the result.

		foreach ($form as $name => $item)
		{
			if(isset($item['sub']))
			{
				// flatten the form
				$form = array_merge($form, $item['sub']);
			}
			
			if (isset($item['validation']))
			{
				// set the rules and add [] to the name if array
				$this->set_rules($name . ((isset($item['array']) && $item['array'])?'[]':''), $item['label'], $item['validation']);
			}
		}

		// we need to run both validation and closures
		$this->run();
		$ci_validation_errors = $this->_error_array;

		$validation_func = array();
		// we run this after form_validation in case form_validation edited the POST data
		foreach ($form as $name => $item)
		{
			// the "required" MUST be handled with the standard form_validation
			// or we'll never get in here
			if (isset($item['validation_func']) && $CI->input->post($name))
			{
				// contains TRUE for success and in array with ['error'] in case
				$validation_func[$name] = $item['validation_func']($CI->input->post(), $form);

				// critical errors don't allow the continuation of the validation.
				// this allows less checks for functions that come after the critical ones.
				// criticals are usually the IDs in the hidden fields.
				if (isset($validation_func[$name]['critical']) &&
					$validation_func[$name]['critical'] == TRUE)
				{
					break;
				}

				if (isset($validation_func[$name]['push']) &&
					is_array($validation_func[$name]['push'] == TRUE))
				{
					// overwrite the $_POST array and reload it
					foreach ($validation_func[$name]['push'] as $n => $i)
					{
						$_POST[$n] = $i;
					}

					$this->_reset_post_array();
				}
			}
		}


		// filter results, since the closures return ['success'] = TRUE on success
		$validation_func_errors = array();
		$validation_func_warnings = array();
		foreach ($validation_func as $item)
		{
			// we want only the errors
			if (isset($item['success']))
			{
				continue;
			}

			if (isset($item['warning']))
			{
				// we want only the human readable error
				$validation_func_warnings[] = $item['warning'];
			}

			if (isset($item['error']))
			{
				// we want only the human readable error
				$validation_func_errors[] = $item['error'];
			}
		}

		if (count($ci_validation_errors) > 0 || count($validation_func_errors) > 0)
		{
			$errors = array_merge($ci_validation_errors, $validation_func_errors);
			// restore post
			if (is_array($alternate))
			{
				$_POST = $temp_post;
				$this->_reset_post_array();
			}
			return array('error' => implode(' ', $errors));
		}
		else
		{
			// get rid of all the uninteresting inputs and simplify
			$result = array();

			foreach ($form as $name => $item)
			{
				// not interested in data that is not related to database
				if ((!isset($item['database']) || $item['database'] !== TRUE) &&
					(!isset($item['preferences']) || $item['preferences'] !== TRUE))
				{
					continue;
				}

				if ($item['type'] == 'checkbox')
				{
					if ($CI->input->post($name) == 1)
					{
						$result[$name] = 1;
					}
					else
					{
						$result[$name] = 0;
					}
				}
				else
				{
					if ($CI->input->post($name) !== FALSE)
					{
						$result[$name] = $CI->input->post($name);
					}
				}
			}
			
			// restore post
			if (is_array($alternate))
			{
				$_POST = $temp_post;
				$this->_reset_post_array();
			}

			if (count($validation_func_warnings) > 0)
			{
				return array('success' => $result, 'warning' => implode(' ',
						$validation_func_warnings));
			}

			// returning a form with the new values
			return array('success' => $result);
		}
	}

}