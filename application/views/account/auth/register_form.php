<div class="incontent login">

	<?php
	if ($use_username)
	{
		$username = array(
			'name' => 'username',
			'id' => 'username',
			'value' => set_value('username'),
			'maxlength' => $this->config->item('username_max_length', 'tank_auth'),
			'size' => 30,
			'placeholder' => _('required')
		);
	}
	$email = array(
		'name' => 'email',
		'id' => 'email',
		'value' => set_value('email'),
		'maxlength' => 80,
		'size' => 30,
		'placeholder' => _('required')
	);
	$password = array(
		'name' => 'password',
		'id' => 'password',
		'value' => set_value('password'),
		'maxlength' => $this->config->item('password_max_length', 'tank_auth'),
		'size' => 30,
		'placeholder' => _('required')
	);
	$confirm_password = array(
		'name' => 'confirm_password',
		'id' => 'confirm_password',
		'value' => set_value('confirm_password'),
		'maxlength' => $this->config->item('password_max_length', 'tank_auth'),
		'size' => 30,
		'placeholder' => _('required')
	);
	$captcha = array(
		'name' => 'captcha',
		'id' => 'captcha',
		'maxlength' => 8,
		'placeholder' => _('required')
	);
	?>
	<?php echo form_open($this->uri->uri_string()); ?>
<?php if ($use_username)
{ ?>
		<div class="formgroup">
			<div><?php echo form_label(_('Username'), $username['id']); ?></div>
			<div><?php echo form_input($username); ?></div>
			<div style="color: red;"><?php echo form_error($username['name']); ?><?php echo isset($errors[$username['name']]) ? $errors[$username['name']] : ''; ?></div>
		</div>
<?php } ?>
	<div class="formgroup">
		<div><?php echo form_label(_('Email Address'), $email['id']); ?></div>
		<div><?php echo form_input($email); ?></div>
		<div style="color: red;"><?php echo form_error($email['name']); ?><?php echo isset($errors[$email['name']]) ? $errors[$email['name']] : ''; ?></div>
	</div>
	<div class="formgroup">
		<div><?php echo form_label(_('Password'), $password['id']); ?></div>
		<div><?php echo form_password($password); ?></div>
		<div style="color: red;"><?php echo form_error($password['name']); ?></div>
	</div>
	<div class="formgroup">
		<div><?php echo form_label(_('Confirm Password'), $confirm_password['id']); ?></div>
		<div><?php echo form_password($confirm_password); ?></div>
		<div style="color: red;"><?php echo form_error($confirm_password['name']); ?></div>
	</div>

<?php if ($captcha_registration)
{
	if ($use_recaptcha)
	{ ?>
			<div class="formgroup">
				<div>
					<div id="recaptcha_image"></div>
				</div>
				<div>
					<a href="javascript:Recaptcha.reload()"><?php echo _('Get another CAPTCHA') ?></a>
					<div class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type('audio')"><?php echo _('Get an audio CAPTCHA') ?></a></div>
					<div class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type('image')"><?php echo _('Get an image CAPTCHA') ?></a></div>
				</div>
			</div>
			<div class="formgroup">
				<div>
					<div class="recaptcha_only_if_image"><?php echo _('Enter the words above') ?></div>
					<div class="recaptcha_only_if_audio"><?php echo _('Enter the numbers you hear') ?></div>
				</div>
				<div><input type="text" id="recaptcha_response_field" name="recaptcha_response_field" /></div>
				<td style="color: red;"><?php echo form_error('recaptcha_response_field'); ?></div>
				<?php echo $recaptcha_html; ?>
		</div>
	<?php }
	else
	{ ?>
		<div class="formgroup">
			<div>
		<?php echo _('Enter the code exactly as it appears') ?>:
			</div>
			<div>
		<?php echo $captcha_html; ?>
			</div>
		</div>
		<div class="formgroup">
			<div><?php echo form_label(_('Confirmation Code'), $captcha['id']); ?></div>
			<div><?php echo form_input($captcha); ?></div>
			<div style="color: red;"><?php echo form_error($captcha['name']); ?></div>
		</div>
	<?php }
} ?>
<div class="formgroup">
	<div></div>
	<div><?php echo form_submit('register', _('Register')); ?></div>		
</div>
<?php echo form_close(); ?>

<a href="<? echo site_url('/account/auth/login/') ?>" class="button yellow"><?php echo _("Back to login") ?></a>

</div>