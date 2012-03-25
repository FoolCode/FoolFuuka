<div class="well">

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
	<?php echo form_open(); ?>
	<?php if ($use_username) : ?>
		<label><?php echo form_label(_('Username'),
		$username['id']); ?></label>
		<?php echo form_input($username); ?>
		<span class="help-inline" style="color: red;"><?php echo form_error($username['name']); ?><?php echo isset($errors[$username['name']])
			? $errors[$username['name']] : ''; ?></span>
	<?php endif; ?>

	<label><?php echo form_label(_('Email Address'),
		$email['id']); ?></label>
	<?php echo form_input($email); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($email['name']); ?><?php echo isset($errors[$email['name']])
			? $errors[$email['name']] : ''; ?></span>

	<label><?php echo form_label(_('Password'),
		$password['id']); ?></label>
	<?php echo form_password($password); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($password['name']); ?></span>


	<label><?php echo form_label(_('Confirm Password'),
		$confirm_password['id']); ?></label>
	<?php echo form_password($confirm_password); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($confirm_password['name']); ?></span>

<?php
if ($captcha_registration)
{
	if ($use_recaptcha)
	{
		?>
			<div id="recaptcha" class="clearfix" style="margin-bottom:5px">
				<div id="recaptcha_image" style="margin-bottom: 3px; border: 1px solid #999;"></div>
				<a href="javascript:Recaptcha.reload()" class="btn btn-mini"><?php echo _('Get another CAPTCHA') ?></a>
				<a href="javascript:Recaptcha.switch_type('audio')" class="recaptcha_only_if_image btn btn-mini"><?php echo _('Get an audio CAPTCHA') ?></a>
				<a href="javascript:Recaptcha.switch_type('image')" class="recaptcha_only_if_audio btn btn-mini"><?php echo _('Get an image CAPTCHA') ?></a>
			</div>

			<label><div class="recaptcha_only_if_image"><?php echo _('Enter the words above') ?></div>
				<div class="recaptcha_only_if_audio"><?php echo _('Enter the numbers you hear') ?></div></label>
			<input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />
			<span class="help-inline" style="color: red;">
			<?php echo form_error('recaptcha_response_field'); ?>
			</span>
			<?php echo $recaptcha_html; ?>

				<?php
			}
			else
			{
				?>
			<br/>
			<?php echo $captcha_html; ?>
			
		<label><?php echo form_label(_('Enter the letters above'), $captcha['id']); ?></label>
		<?php echo form_input($captcha); ?>
		<span class="help-inline" style="color: red;"><?php echo form_error($captcha['name']); ?></span>
		<?php
	}
}
?>
<br/>
<?php echo form_submit(array(
	'name' => 'register', 
	'value' => _('Register'),
	'class' => 'btn btn-primary'
	)); ?>	

<input type="button" onClick="window.location.href='<?php echo site_url('/admin/auth/login/') ?>'" class="btn" value="<?php echo form_prep(_("Back to login")) ?>" />

<?php echo form_close(); ?>
		

</div>