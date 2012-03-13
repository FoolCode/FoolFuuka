<div class="incontent login">
	<?php
	$password = array(
		'name' => 'password',
		'id' => 'password',
		'size' => 30,
	);
	?>
	<?php echo form_open($this->uri->uri_string()); ?>
	<div class="formgroup">
		<div><?php echo form_label('Password', $password['id']); ?></div>
		<div><?php echo form_password($password); ?></div>
		<div style="color: red;"><?php echo form_error($password['name']); ?><?php echo isset($errors[$password['name']]) ? $errors[$password['name']] : ''; ?></div>
	</div>	
	<div class="formgroup">
		<div>
			<?php echo form_submit('cancel', 'Delete account'); ?>
		</div>
	</div>
	<?php echo form_close(); ?>
	<a href="<? echo site_url('/account/profile/') ?>" class="button yellow"><?php echo _("Back to profile") ?></a>
</div>