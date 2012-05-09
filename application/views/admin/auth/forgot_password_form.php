<div class="well">
	<?php
	$login = array(
		'name' => 'login',
		'id' => 'login',
		'value' => set_value('login'),
		'maxlength' => 80,
		'size' => 30,
		'placeholder' => __('Required')
	);
	if ($this->config->item('use_username', 'tank_auth'))
	{
		$login_label = __('Email or username');
	}
	else
	{
		$login_label = 'Email';
	}
	?>
	<?php echo form_open(); ?>
	<label><?php echo form_label($login_label,
		$login['id']);
	?></label>
		<?php echo form_input($login); ?>
	<span class="help-inline" style="color: red;">
		<?php echo form_error($login['name']); ?>
		<?php echo isset($errors[$login['name']])
				? $errors[$login['name']] : '';
		?>
	</span>

	<br/>
	<?php
	echo form_submit(array(
		'name' => 'reset',
		'value' => __('Get a new password'),
		'class' => 'btn btn-primary')
	);
	?>
	
	<input type="button" onClick="window.location.href='<?php echo site_url('/admin/auth/login/') ?>'" class="btn" value="<?php echo form_prep(__("Back to login")) ?>" />

<?php echo form_close(); ?>
</div>