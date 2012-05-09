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
	if ($login_by_username && $login_by_email)
	{
		$login_label = __('Email or username');
	}
	else if ($login_by_username)
	{
		$login_label = __('Login');
	}
	else
	{
		$login_label = __('Email');
	}
	$password = array(
		'name' => 'password',
		'id' => 'password',
		'size' => 30,
		'placeholder' => __('Required')
	);
	$remember = array(
		'name' => 'remember',
		'id' => 'remember',
		'value' => 1,
		'checked' => set_value('remember'),
	);
	$captcha = array(
		'name' => 'captcha',
		'id' => 'captcha',
		'maxlength' => 8,
	);
	?>
	<?php echo form_open(); ?>

	<label><?php
	echo form_label($login_label, $login['id']);
	?></label>
	<?php echo form_input($login); ?>
	<span class="help-inline" style="color: red;">
		<?php echo form_error($login['name']); ?><?php
		echo isset($errors[$login['name']]) ? $errors[$login['name']] : '';
		?>
	</span>

	<label><?php echo form_label('Password', $password['id']);
		?></label>
	<?php echo form_password($password); ?>
	<span class="help-inline" style="color: red;">
		<?php echo form_error($password['name']); ?><?php
		echo isset($errors[$password['name']]) ? $errors[$password['name']] : '';
		?>
	</span>


	<?php
	if ($show_captcha)
	{
		if ($use_recaptcha)
		{
			?>
			<div id="recaptcha" class="clearfix" style="margin-bottom:5px">
				<div id="recaptcha_image" style="margin-bottom: 3px; border: 1px solid #999;"></div>
				<a href="javascript:Recaptcha.reload()" class="btn btn-mini"><?php echo __('Get another CAPTCHA') ?></a>
				<a href="javascript:Recaptcha.switch_type('audio')" class="recaptcha_only_if_image btn btn-mini"><?php echo __('Get an audio CAPTCHA') ?></a>
				<a href="javascript:Recaptcha.switch_type('image')" class="recaptcha_only_if_audio btn btn-mini"><?php echo __('Get an image CAPTCHA') ?></a>
			</div>

			<label><div class="recaptcha_only_if_image"><?php echo __('Enter the words above') ?></div>
				<div class="recaptcha_only_if_audio"><?php echo __('Enter the numbers you hear') ?></div></label>
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
			
		<label><?php echo form_label(__('Enter the letters above'), $captcha['id']); ?></label>
		<?php echo form_input($captcha); ?>
		<span class="help-inline" style="color: red;"><?php echo form_error($captcha['name']); ?></span>
		<?php
	}
}
?>

<label class="checkbox">
	<?php echo form_checkbox($remember); ?>
	<?php
	echo form_label(__('Remember me'), $remember['id']);
	?>
</label>		

<?php echo form_submit(array('name' => 'submit', 'value' => __('Login'), 'class' => 'btn btn-primary')); ?>
		
<input type="button" onClick="window.location.href='<?php echo site_url('/admin/auth/forgot_password/') ?>'" class="btn" value="<?php echo form_prep(__("Forgot password")) ?>" />
<?php
if ($this->config->item('allow_registration', 'tank_auth')) :
	?>
	<input type="button" onClick="window.location.href='<?php echo site_url('/admin/auth/register/') ?>'" class="btn" value="<?php echo form_prep(__("Register")) ?>" />
<?php endif; ?>

<?php echo form_close(); ?>


</div>
