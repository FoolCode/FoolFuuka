<?php

/**
 * Forms Extension for DataMapper classes.
 *
 * Updates field values in the object from values posted
 *
 * @license 	MIT License
 * @package		DMZ-ExiteCMS-Extensions
 * @category	DMZ
 * @author  	WanWizard
 * @version 	1.0
 */

// --------------------------------------------------------------------------

/**
 * DMZ_Forms Class
 *
 * @package		DMZ-ExiteCMS-Extensions
 */
class DMZ_Forms {

	/**
	 * update the DMZ object from $_POST for all field values that match, plus optional values requested
	 *
	 * @param	DataMapper $object The DataMapper Object to convert
	 * @param	array $fields Array of fields to include.  If empty, includes all database columns.
	 * @return	object, the Datamapper object
	 */
	function update_from_post($object, $fields = array(), $allfields = TRUE)
	{
		// make sure $fields is an array
		$fields = (array) $fields;

		// if all table columns must be included, add them
		if ( $allfields )
		{
			$fields = array_merge($object->fields, $fields);
		}

		$CI =& get_instance();

		// loop through the fields
		foreach($fields as $f)
		{
			// fetch the post value
			$value = $CI->input->post($f);

			// did we receive a value?
			if ( $value !== FALSE )
			{
				$object->{$f} = $value;
			}
			elseif ( ! isset($object->{$f}) )
			{
				$object->{$f} = NULL;
			}
		}

		// return the Datamapper object
		return $object;
	}

}

/* End of file forms.php */
/* Location: ./exitecms/libaries/datamapper/forms.php */
