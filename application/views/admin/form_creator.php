<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');


/*
 * How to use: just as any view.
 * 
 * $form = $this->load->view('admin/form_creator', $data, TRUE);
 * 
 * The $data must have the $data['form'] index. The keys in the array are the
 * name="" field.
 *
 * 	$form = array(
 *			'open' => array(
 *				'type' => 'open',
 *				'hidden' => array(
 *					'id' => NULL
 *				)
 *			)
 * 			'name' => array(
 * 				'type' => 'input',
 * 				'label' => _('Name'),
 * 				'help' => _('Insert the name of your mom.'),
 * 				'placeholder' => _('Required'),
 * 				'class' => 'span3',
 * 				'validation' => 'required'
 * 			),
 * 			'separator-1' => array( // keep the key different
 * 				'type' => 'separator' // prints a separator
 * 			),
 * 			'a-checkbox' => array(
 * 				'type' => 'checkbox',
 * 				'checked' => TRUE
 * 				'value' => 1 // defaults to 1 if not inserted
 * 				'help' => _('A checkbox example')
 * 			),
 * ...
 * 		);
 * 
 * Values outside of $not_input will be sent to the form_ function (where applicable).
 * 
 * 
 * 
 * $object if set will automatically populate the fields
 * $object must be an object, so you need $board->archive, with the key being
 * the same name as the name=""
 * 
 * 
 * 
 */

?>

<div class="well">

<?php

foreach ($form as $name => $item) :

	// separate up the array so we can put the rest in the form function
	$not_input = array('help', 'label', 'validation', 'preferences');
	$helpers = array();
	foreach ($not_input as $not)
	{
		if (isset($item[$not]))
		{
			$helpers[$not] = $item[$not];
			unset($item[$not]);
		}
	}
	
	$item['name'] = $name;
	
	// use the input by the user
	if($this->input->post($item['name']))
	{
		$item['value'] = $this->input->post($item['name']);
	}
		
	switch ($item['type']):

		case 'separator':
			?>
			<br/><br/>
			<?php
			break;

		case 'separator-short':
			?>
			<br/>
			<?php
			break;
		

		case 'open':
			// a special case for the hidden, let's try populating it
			if(isset($item['hidden']))
			{
				foreach($item['hidden'] as $key => $hidden)
				{
					if(is_null($hidden))
					{
						if($this->input->post($key))
						{
							$item['hidden'][$key] = $this->input->post($key);
						}
						else if(isset($object->$key))
						{
							$item['hidden'][$key] = $object->$key;
						}
					}
					
				}
			}
			
			echo form_open(
				isset($item['href']) ? $item['href'] : '',
				isset($item['attributes']) ? $item['attributes'] : '',
				isset($item['hidden']) ? $item['hidden'] : array()
			);
			break;


		case 'close':
			echo form_close(); // I know there's a variable there but it's useless
			break;


		case 'submit':
		case 'reset':
			echo call_user_func('form_' . $item['type'], $item);
			break;


		case 'radio':
		case 'checkbox':
			if (!isset($item['value']))
			{
				$item['value'] = 1;
			}
			?>
			<label class="checkbox">
				<?php
				echo form_checkbox($name, $item['value'], isset($object->$name)?$object->$name:FALSE)
				?>
				<?php echo $helpers['help'] ?>
			</label>
			<?php
			break;


		// These are the standard CodeIgniter functions that accept array 
		// http://codeigniter.com/user_guide/helpers/form_helper.html
		case 'input':
		case 'password':
		case 'upload':
		case 'textarea':
		case 'dropdown':
		case 'multiselect':
		case 'button':
			
			if(!isset($item['value']))
			{
				$item['value'] = isset($object->$name)?$object->$name:'';
			}
			
			?>
			<label><?php echo $helpers['label'] ?></label>
			<?php
			echo call_user_func('form_' . $item['type'], $item);
			?>
			<span class="help-inline">
				<?php echo isset($helpers['help']) ? $helpers['help']
						: NULL ?>
			</span>

			<?php
			break;

		default:
			break;

	endswitch;
	?>



<?php endforeach; ?>

</div>