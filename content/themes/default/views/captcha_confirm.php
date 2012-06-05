<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="alert" style="margin:10% auto; width:300px">
	<?php echo '<h4 class="alert-heading">'.__('Enter the captcha to complete the action:') . '</h4> ' ?>
	<?php 
		echo form_open(); 
		echo form_hidden($mode);
		echo (isset($message) && $message)?'<p>' . $message . '</p>':'';
		echo (isset($error) && $error)?'<p style="color:red">' . $error . '</p>':'';
		echo '<p>' . $image . '</p>';
		echo '<p>' . form_input(array('name' => $mode . '_captcha', 'style' => 'width:290px')) . '</p>';
		echo '<p>' . form_submit(array(
			'name' => $mode . 'submit', 
			'value' => __('Submit'),
			'class' => 'btn btn-inverse'
		)) . '</p>';
	?>
</div>
