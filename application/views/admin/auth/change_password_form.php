<div class="well">
	<?php
	$old_password = array(
		'name' => 'old_password',
		'id' => 'old_password',
		'value' => set_value('old_password'),
		'size' => 30,
		'placeholder' => __('Required')
	);
	$new_password = array(
		'name' => 'new_password',
		'id' => 'new_password',
		'maxlength' => $this->config->item('password_max_length', 'tank_auth'),
		'size' => 30,
		'placeholder' => __('Required')
	);
	$confirm_new_password = array(
		'name' => 'confirm_new_password',
		'id' => 'confirm_new_password',
		'maxlength' => $this->config->item('password_max_length', 'tank_auth'),
		'size' => 30,
		'placeholder' => __('Required')
	);
	?>
	<?php echo form_open(); ?>
	
	<label><?php echo form_label(__('Old Password'), $old_password['id']); ?></label>
	<?php echo form_password($old_password); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($old_password['name']); ?><?php echo isset($errors[$old_password['name']]) ? $errors[$old_password['name']] : ''; ?></span>
	
	<label><?php echo form_label(__('New Password'), $new_password['id']); ?></label>
	<?php echo form_password($new_password); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($new_password['name']); ?><?php echo isset($errors[$new_password['name']]) ? $errors[$new_password['name']] : ''; ?></span>
	
	<label><?php echo form_label(__('Confirm New Password'), $confirm_new_password['id']); ?></label>
	<?php echo form_password($confirm_new_password); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($confirm_new_password['name']); ?><?php echo isset($errors[$confirm_new_password['name']]) ? $errors[$confirm_new_password['name']] : ''; ?></span>
	
	
	<br/>
	<?php
	echo form_submit(array(
		'name' => 'change',
		'value' => __('Change Password'),
		'class' => 'btn btn-primary')
	);
	?>
	
	<?php echo form_close(); ?>

</div>