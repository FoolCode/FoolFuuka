<div class="well"><?php
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
	<label><?php
	echo form_label(_('Email Address'), $email['id']);
	?></label>
	<?php echo form_input($email); ?>
	<span class="help-inline" style="color: red;"><?php echo form_error($email['name']); ?><?php
	echo isset($errors[$email['name']]) ? $errors[$email['name']] : '';
	?></span>

	<br/>
	<?php
	echo form_submit(array(
		'name' => 'send',
		'value' => _('Send'),
		'class' => 'btn btn-primary')
	);
	?>

	<input type="button" onClick="window.location.href='<?php echo site_url('/admin/auth/login/') ?>'" class="btn" value="<?php echo form_prep(_("Back to login")) ?>" />


	<?php echo form_close(); ?>
</div>