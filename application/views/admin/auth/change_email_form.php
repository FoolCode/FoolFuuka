<div class="well">
	<?php
	$password = array(
		'name' => 'password',
		'id' => 'password',
		'size' => 30,
		'placeholder' => _('Required')
	);
	$email = array(
		'name' => 'email',
		'id' => 'email',
		'value' => set_value('email'),
		'maxlength' => 80,
		'size' => 30,
		'placeholder' => _('Required')
	);
	?>
	<?php echo form_open(); ?>
	<label><?php echo form_label(_('Password'), $password['id']); ?></label>
	<?php echo form_password($password); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($password['name']); ?><?php echo isset($errors[$password['name']]) ? $errors[$password['name']] : ''; ?></span>
	
	
	
	<label><?php echo form_label(_('New email address'), $email['id']); ?></label>
	<?php echo form_input($email); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($email['name']); ?><?php echo isset($errors[$email['name']]) ? $errors[$email['name']] : ''; ?></span>
	
	<br/>
	
	<?php
	echo form_submit(array(
		'name' => 'change',
		'value' => _('Send confirmation email'),
		'class' => 'btn btn-primary')
	);
	?>
			
	<?php echo form_close(); ?>
	
</div>