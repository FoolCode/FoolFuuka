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
 * 			'open' => array(
 * 				'type' => 'open',
 * 				'hidden' => array(
 * 					'id' => NULL
 * 				)
 * 			)
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

<div class="well"<?php echo (isset($parent))?' data-form-parent="' . $parent . '"':'';
	echo (isset($hide) && $hide?' style="display:none"':'');
?>>

	<?php
	foreach ($form as $name => $item) :

		// separate up the array so we can put the rest in the form function
		$not_input = array(
			'help', 
			'label', 
			'validation', 
			'validation_func', 
			'preferences', 
			'array',
			'sub'
		);
		$helpers = array();
		foreach ($not_input as $not)
		{
			if (isset($item[$not]))
			{
				$helpers[$not] = $item[$not];
				unset($item[$not]);
			}
		}
				
		if(isset($helpers['array']) && $helpers['array'])
		{
			$item['name'] = $name . '[]';
			
			$item['value_array'] = array();
			
			if ($this->input->post($item['name']))
			{
				$item['value_array'] = $this->input->post($item['name']);
				$item['value_array'] = array_filter($item['value_array']);
			}
			else 
			{
				if(isset($item['value']))
					$item['value_array'] = unserialize($item['value_array']);
			}
			
			
			
			$count = count($item['value_array'])+1;
		}
		else
		{
			$item['name'] = $name;

			if ($this->input->post($item['name']))
			{
				$item['value'] = $this->input->post($item['name']);
			}
			
			$count = 1;
		}
		

		if (isset($item['type'])) :
			for($i = 0; $i < $count; $i++) :
				if(isset($item['value_array']))
				{
					//$item['value'] = $item['value_array'][$i];
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


					case 'paragraph':
						?>
						<p><?php echo $helpers['help'] ?></p>
						<?php
						break;


					case 'open':
						// a special case for the hidden
						if (isset($item['hidden']))
						{
							// better not supporting it, things might get messy
							log_message('error',
								'The form automator doesn\'t support hidden in form_opens.');
							show_error('The form automator doesn\'t support hidden in form_opens.');
						}

						echo form_open(
							isset($item['action']) ? $item['action'] : '',
							isset($item['attributes']) ? $item['attributes'] : '',
							isset($item['hidden']) ? $item['hidden'] : array()
						);
						break;


					case 'close':
						echo form_close(); // I know there's a variable there but it's useless
						break;


					case 'hidden':
						// to keep maximum functionality we want one value per hidden
						if (isset($item['value']) && is_array($item['value']))
						{
							// better not supporting it, things might get messy
							log_message('error',
								'The form automator doesn\'t support arrays of hidden values in form_hidden.');
							show_error('The form automator doesn\'t support arrays of hidden values in form_hidden.');
						}


						// this is outputted only if we actually have a value
						// it will never be inserted by the user so don't take care of repopulation
						if (isset($object->$name))
						{
							$item['value'] = $object->$name;
						}

						if (isset($item['value']))
						{
							echo form_hidden($name, $item['value']);
						}
						break;


					case 'submit':
					case 'reset':
						echo call_user_func('form_' . $item['type'], $item);
						break;


					case 'radio':
						?>
						<div style="margin: 0px 0px 15px;">
							<?php
							echo '<label>'.$helpers['help'].'</label>';
							foreach ($item['radio_values'] as $radio_key => $radio_value)
							{
								if (isset($object->$name) && $object->$name == $radio_key)
								{
									$checked = TRUE;
								}
								else
								{
									$checked = FALSE;
								}
								
								?>
								<label class="radio">
									<?php
									echo form_radio($name, $radio_key, $checked)
									?>
									<?php echo $radio_value ?>
								</label>
								<?php
							}
							?>
						</div>
						<?php
						break;


					case 'checkbox':
						if (!isset($item['value']))
						{
							$item['value'] = 1;
						}

						if (isset($helpers['preferences']) && $helpers['preferences'])
						{
							$checked = get_setting($name);
							if(isset($helpers['array']) && $helpers['array'])
							{
								$checked = unserialize($checked);
								$checked = $checked[$i];
							}
						}
						else
						{
							$checked = isset($object->$name) ? $object->$name : FALSE;
						}
						
						$extra = '';
						if(isset($helpers['sub']))
						{
							$extra = 'data-function="hasSubForm"';
						}
						?>
						<label class="checkbox">
							<?php
							echo form_checkbox($name, $item['value'], $checked, $extra)
							?>
							<?php echo $helpers['help'] ?>
						</label>
						<?php
						
						if(isset($helpers['sub']))
						{
							$data = array('form' => $helpers['sub']);
							if(!$checked)
								$data['hide'] = TRUE;
							$data['parent'] = $name;
							$this->load->view('admin/form_creator', $data);
						}
						
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

						if (!isset($item['value']))
						{
							if (isset($helpers['preferences']) && $helpers['preferences'])
							{
								$item['value'] = get_setting($name);
								if(isset($helpers['array']) && $helpers['array'])
								{
									$item['value'] = unserialize($item['value']);

									if(is_array($item['value']) && isset($item['value'][$i]))
									{
										$item['value'] = $item['value'][$i];
										$count++;
									}
									else
									{
										$item['value'] = '';
									}
								}
							}
							else
							{
								$item['value'] = isset($object->$name) ? $object->$name : '';
							}
						}
						
						?>
						<label><?php echo $helpers['label'] ?></label>
						<?php
						echo call_user_func('form_' . $item['type'], $item);
						?>
						<span class="help-inline">
							<?php
							echo isset($helpers['help']) ? $helpers['help'] : NULL
							?>
						</span>

						<?php
						break;

					default:
						break;

				endswitch;
				unset($item['value']);
			endfor;
		endif;
		?>



	<?php endforeach; ?>

</div>