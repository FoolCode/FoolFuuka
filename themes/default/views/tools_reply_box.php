<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<?php if (\ReCaptcha::available()) : ?>
	<script>
		var RecaptchaOptions = {
		   theme : 'custom',
		   custom_theme_widget: 'recaptcha_widget'
		};
	</script>
<?php endif; ?>

<div id="reply" class="thread_form_wrap clearfix">
<section class="thread_form clearfix">
<?= Form::open(array('enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $radix->shortname . '/submit')) ?>
<?= Form::hidden(\Config::get('security.csrf_token_key'), \Security::fetch_token()); ?>
<?= Form::hidden('reply_numero', isset($thread_id)?$thread_id:0, array('id' => 'reply_numero')) ?>
<?= isset($backend_vars['last_limit']) ? Form::hidden('reply_last_limit', $backend_vars['last_limit'])  : '' ?>
<fieldset>

	<div class="progress progress-info progress-striped active" style="height:8px; margin-top:0px; margin-bottom: 3px; background: #fff; width: 660px; opacity: 0;"><div class="bar" style="width: 0%"></div></div>

	<?php /*<label class="comment_label" for="reply_chennodiscursus"><?= __('Comment') ?></label>*/ ?>
	<div class="pull-left">

		<div class="input-prepend">
			<label class="add-on" for="reply_talkingde"><?= __('Subject') ?></label><?php
			echo Form::input(array(
				'name' => 'reply_talkingde',
				'id' => 'reply_talkingde',
			));
			?>
		</div>
		
		<div class="input-prepend">
			<label class="add-on" for="reply_bokunonome"><?= __('Name') ?></label><?php
			echo Form::input(array(
				'name' => 'name',
				'id' => 'reply_name_yep',
				'style' => 'display:none'
			));

			echo Form::input(array(
				'name' => 'reply_bokunonome',
				'id' => 'reply_bokunonome',
				'value' => $user_name
			));
			?>
		</div>

		<div class="input-prepend">
			<label class="add-on" for="reply_elitterae"><?= __('E-mail') ?></label><?php
			echo Form::input(array(
				'name' => 'email',
				'id' => 'reply_email_yep',
				'style' => 'display:none'
			));

			echo Form::input(array(
				'name' => 'reply_elitterae',
				'id' => 'reply_elitterae',
				'value' => $user_email
			));
			?>
		</div>
		
		

		<div class="input-prepend">
			<label class="add-on" for="reply_nymphassword"><?= __('Password') ?></label><?php
			echo Form::password(array(
				'name' => 'reply_nymphassword',
				'id' => 'reply_nymphassword',
				'value' => $user_pass,
				'required' => 'required'
			));
			?>
		</div>
		
		<?php if (!isset($disable_image_upload) || !$disable_image_upload) : ?>
		<div class="input-prepend">
			<label class="add-on" for="file_image"><?= __('File') ?></label><input type="file" name="file_image" id="file_image" />
		</div>
		<?php endif; ?>
		<?php
			$postas = array('N' => __('User'));

			if (\Auth::has_access('comment.mod_capcode')) $postas['M'] = __('Moderator');
			if (\Auth::has_access('comment.admin_capcode')) $postas['A'] = __('Administrator');
			if (\Auth::has_access('comment.dev_capcode')) $postas['D'] = __('Developer');
			if (count($postas) > 1) :
		?>
		<div class="input-prepend">
			<label class="add-on" for="reply_postas"><?= __('Post As') ?></label>
				<?= \Form::select('reply_postas', 'User', $postas, array('id' => 'reply_postas')); ?>
		</div>
		<?php endif; ?>

	</div>


	<div class="input-append pull-left">
		<?php
		echo Form::textarea(array(
			'name' => 'reply',
			'id' => 'reply_comment_yep',
			'style' => 'display:none'
		));
		echo Form::textarea(array(
			'name' => 'reply_chennodiscursus',
			'id' => 'reply_chennodiscursus',
			'placeholder' => (!$radix->archive && isset($thread_dead) && $thread_dead) ? __('This thread has been archived. Any replies made will be marked as ghost posts and will only affect the ghost index.') : '',
			'rows' => 3,
			'style' => 'height:132px; width:320px;'
		));
		?>
		
		<div class="btn-group">
			<?php
			$submit_array = array(
				'data-function' => 'comment',
				'name' => 'reply_gattai',
				'value' => __('Submit'),
				'class' => 'btn btn-primary',
			);
			
			echo Form::submit($submit_array);

			$submit_array = array(
				'data-function' => 'comment',
				'name' => 'reply_gattai_spoilered',
				'value' => __('Submit Spoilered'),
				'class' => 'btn',
			);

			echo Form::submit($submit_array);

			echo Form::reset(array('class' => 'btn', 'name' => 'reset', 'value' => __('Reset')));
			?>
		</div>
		
	</div>


	<div class="rules pull-left">
		<div class="rules_box">
		<?php
			if ($radix->posting_rules)
			{
				echo \Markdown::parse($radix->posting_rules);
			}
		?>
		</div>
		
		<?php if (\ReCaptcha::available()) : ?>
		
		<div class="recaptcha_widget" style="display:none">
			<div><p><?= e(__('You might be a bot! Enter a reCAPTCHA to continue.')) ?></p></div>
			<div id="recaptcha_image" style="background: #fff; border: 1px solid #ccc; padding: 3px 6px; margin: 4px 0;"></div>
			<div class="input-prepend">
				<label class="add-on" for="recaptcha_response_field"><?= e(__('Solution')) ?></label>
				<input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />
			</div>
			<div class="btn-group">
				<a class="btn btn-mini" href="javascript:Recaptcha.reload()">Get another CAPTCHA</a>
				<a class="recaptcha_only_if_image btn btn-mini" href="javascript:Recaptcha.switch_type('audio')">Get an audio CAPTCHA</a>
				<a class="recaptcha_only_if_audio btn btn-mini" href="javascript:Recaptcha.switch_type('image')">Get an image CAPTCHA</a>
				<a class="btn btn-mini" href="javascript:Recaptcha.showhelp()">Help</a>
			</div>
		</div>

		<script type="text/javascript" src="https://www.google.com/recaptcha/api/challenge?k=<?= \Config::get('recaptcha.public_key') ?>"></script>
		<noscript>
			<iframe src="https://www.google.com/recaptcha/api/noscript?k=<?= \Config::get('recaptcha.public_key') ?>" height="300" width="500" frameborder="0"></iframe><br>
			<textarea name="recaptcha_challenge_field" rows="3" cols="40">
			</textarea>
			<input type="hidden" name="recaptcha_response_field"  value="manual_challenge">
		</noscript>
		<?php endif; ?>

	</div>

	<div id="reply_ajax_notices"></div>
	<?php if (isset($reply_errors)) : ?>
	<span style="color:red"><?= $reply_errors ?></span>
	<?php endif; ?>
</fieldset>
<?= Form::close() ?>
</section>
</div>