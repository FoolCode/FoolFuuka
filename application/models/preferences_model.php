<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * FoOlFrame Preferences Model
 *
 * The Preferences Model deals with the preferences table
 * and uses the form validation extended by FoOlFrame.
 *
 * @package        	FoOlFrame
 * @subpackage    	Models
 * @category    	Models
 * @author        	FoOlRulez
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Preferences_model extends CI_Model
{

	/**
	 * Nothing special here 
	 */
	function __construct()
	{
		parent::__construct();
	}
	
	
	/**
	 * Save in the preferences table the name/value pairs
	 * 
	 * @param array $data name => value
	 */
	function submit($data)
	{
		foreach ($data as $name => $value)
		{
			// in case it's an array of values from name="thename[]"
			if(is_array($value))
			{
				// remove also empty values with array_filter
				// but we want to keep 0s
				$value = serialize(array_filter($value, function($var){
					if($var === 0)
						return TRUE;
					return $var;
				}));
			}
			
			$this->db->where(array('name' => $name));
			// we can update only if it already exists
			if ($this->db->count_all_results('preferences') == 1)
			{
				$this->db->update('preferences', array('value' => $value),
					array('name' => $name));
			}
			else
			{
				$this->db->insert('preferences', array('name' => $name, 'value' => $value));
			}
		}

		// reload those preferences
		load_settings();
	}


	/**
	 * A lazy way to submit the preference panel input, saves some code in controller
	 * 
	 * This function runs the custom validation function that uses the $form array
	 * to first run the original CodeIgniter validation and then the anonymous
	 * functions included in the $form array. It sets a proper notice for the 
	 * admin interface on conclusion.
	 * 
	 * @param array $form 
	 */
	function submit_auto($form)
	{
		if ($this->input->post())
		{
			$this->load->library('form_validation');
			$result = $this->form_validation->form_validate($form);
			if (isset($result['error']))
			{
				set_notice('warning', $result['error']);
			}
			else
			{
				if (isset($result['warning']))
				{
					set_notice('warning', $result['warning']);
				}
				
				set_notice('success', __('Preferences updated.'));
				$this->submit($result['success']);
			}
		}
	}
	
}


/* End of file preferences_model.php */
/* Location: ./application/models/preferences_model.php */