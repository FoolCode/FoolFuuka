<div class="incontent login">
	<?php
	$login = array(
		'name' => 'login',
		'id' => 'login',
		'value' => set_value('login'),
		'maxlength' => 80,
		'size' => 30,
	);
	if ($this->config->item('use_username', 'tank_auth'))
	{
		$login_label = 'Email or login';
	}
	else
	{
		$login_label = 'Email';
	}
	?>
	<?php echo form_open($this->uri->uri_string()); ?>
	<div class="formgroup">
		<div><?php echo form_label($login_label, $login['id']); ?></div>
		<div><?php echo form_input($login); ?></div>
		<div style="color: red;"><?php echo form_error($login['name']); ?><?php echo isset($errors[$login['name']]) ? $errors[$login['name']] : ''; ?></div>
	</div>
	<div class="formgroup">
		<div>
			<?php echo form_submit('reset', 'Get a new password'); ?>
		</div>
	</div>
	<?php echo form_close(); ?>
	<div class="formgroup">
		<div>
			<a href="<? echo site_url('/account/auth/login/') ?>" class="button yellow"><?php echo _("Back to login") ?></a>
		</div>
	</div>