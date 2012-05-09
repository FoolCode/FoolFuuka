<div class="well">
	<?php
	$password = array(
		'name' => 'password',
		'id' => 'password',
		'size' => 30,
		'placeholder' => __('Required')
	);
	?>
	<?php echo form_open(); ?>
	
	<label><?php echo form_label('Password', $password['id']); ?></label>
	<?php echo form_password($password); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($password['name']); ?><?php echo isset($errors[$password['name']]) ? $errors[$password['name']] : ''; ?></span>	
	
	<br/>
	
	<?php
	echo form_submit(array(
		'name' => 'cancel',
		'value' => __('Delete account'),
		'class' => 'btn btn-primary')
	);
	?>
	
	<?php echo form_close(); ?>
</div>