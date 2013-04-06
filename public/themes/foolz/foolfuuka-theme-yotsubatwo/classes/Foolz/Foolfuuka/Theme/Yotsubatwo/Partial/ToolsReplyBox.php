<?php

namespace Foolz\Foolfuuka\Theme\Yotsubatwo\Partial;

class ToolsReplyBox extends \Foolz\Theme\View
{
	public function toString()
	{
		$backend_vars = $this->getBuilderParamManager()->getParam('backend_vars');
		$radix = $this->getBuilderParamManager()->getParam('radix');
		$user_name = $this->getBuilderParamManager()->getParam('user_name');
		$user_pass = $this->getBuilderParamManager()->getParam('user_pass');
		$user_email = $this->getBuilderParamManager()->getParam('user_email');
		$thread_id = $this->getBuilderParamManager()->getParam('thread_id', 0);
		$reply_errors = $this->getBuilderParamManager()->getParam('reply_errors', false);

		\Foolz\Plugin\Hook::forge('foolfuuka.themes.default_after_op_open')->setParam('board', $radix)->execute(); ?>

		<?php if (\ReCaptcha::available()) : ?>
			<script>
				var RecaptchaOptions = {
					theme : 'custom',
					custom_theme_widget: 'recaptcha_widget'
				};
			</script>
			<?php endif; ?>

		<?= \Form::open(['enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $radix->shortname . '/submit']) ?>
		<?= \Form::hidden(\Config::get('security.csrf_token_key'), \Security::fetch_token()); ?>
		<?= \Form::hidden('reply_numero', isset($thread_id)?$thread_id:0, array('id' => 'reply_numero')) ?>
		<?= isset($backend_vars['last_limit']) ? \Form::hidden('reply_last_limit', $backend_vars['last_limit'])  : '' ?>

		<table id="reply">
			<tbody>
			<tr>
				<td><?= __('Name') ?></td>
				<td><?php
					echo \Form::input([
						'name' => 'name',
						'id' => 'reply_name_yep',
						'style' => 'display:none'
					]);

					echo \Form::input([
						'name' => 'reply_bokunonome',
						'id' => 'reply_bokunonome',
						'value' => $user_name
					]);
					?></td>
			</tr>
			<tr>
				<td><?= __('E-mail') ?></td>
				<td><?php
					echo \Form::input([
						'name' => 'email',
						'id' => 'reply_email_yep',
						'style' => 'display:none'
					]);

					echo \Form::input([
						'name' => 'reply_elitterae',
						'id' => 'reply_elitterae',
						'value' => $user_email
					]);
					?></td>
			</tr>
			<tr>
				<td><?= __('Subject') ?></td>
				<td>
					<?php
					echo \Form::input([
						'name' => 'reply_talkingde',
						'id' => 'reply_talkingde',
					]);
					?>

					<?php
					$submit_array = [
						'data-function' => 'comment',
						'name' => 'reply_gattai',
						'value' => __('Submit'),
						'class' => 'btn',
					];

					echo \Form::submit($submit_array);
					?>

					<?php if ( ! $this->getBuilderParamManager()->getParam('disable_image_upload', false)) : ?>
					[ <label><?php echo \Form::checkbox(['name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1]) ?> Spoiler Image?</label> ]
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><?= __('Comment') ?></td>
				<td><?php
					echo \Form::textarea([
						'name' => 'reply',
						'id' => 'reply_comment_yep',
						'style' => 'display:none'
					]);

					echo \Form::textarea([
						'name' => 'reply_chennodiscursus',
						'id' => 'reply_chennodiscursus',
						'placeholder' => (!$radix->archive && isset($thread_dead) && $thread_dead) ? __('This thread has entered ghost mode. Your reply will be marked as a ghost post and will only affect the ghost index.') : '',
					]);
					?></td>
			</tr>
				<?php if (\ReCaptcha::available()) : ?>
			<tr class="recaptcha_widget" style="display:none">
				<td><?= __('Verification') ?></td>
				<td><div><p><?= e(__('You might be a bot! Enter a reCAPTCHA to continue.')) ?></p></div>
					<div id="recaptcha_image" style="background: #fff; border: 1px solid #ccc; padding: 3px 6px; margin: 4px 0;"></div>
					<input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />
					<div class="btn-group">
						<a class="btn btn-mini" href="javascript:Recaptcha.reload()">Get another CAPTCHA</a>
						<a class="recaptcha_only_if_image btn btn-mini" href="javascript:Recaptcha.switch_type('audio')">Get an audio CAPTCHA</a>
						<a class="recaptcha_only_if_audio btn btn-mini" href="javascript:Recaptcha.switch_type('image')">Get an image CAPTCHA</a>
						<a class="btn btn-mini" href="javascript:Recaptcha.showhelp()">Help</a>
					</div>

					<script type="text/javascript" src="//www.google.com/recaptcha/api/challenge?k=<?= \Config::get('recaptcha.public_key') ?>"></script>
					<noscript>
						<iframe src="//www.google.com/recaptcha/api/noscript?k=<?= \Config::get('recaptcha.public_key') ?>" height="300" width="500" frameborder="0"></iframe><br>
						<textarea name="recaptcha_challenge_field" rows="3" cols="40">
						</textarea>
						<input type="hidden" name="recaptcha_response_field"  value="manual_challenge">
					</noscript></td>
			</tr>
				<?php endif; ?>
				<?php if ( ! $this->getBuilderParamManager()->getParam('disable_image_upload', false)) : ?>
			<tr>
				<td><?= __('File') ?></td>
				<td><?php echo \Form::file(['name' => 'file_image', 'id' => 'file_image']) ?></td>
			</tr>
			<tr>
				<td><?= __('Progress') ?></td>
				<td><div class="progress progress-info progress-striped active" style="width: 300px; margin-bottom: 2px"><div class="bar" style="width: 0%"></div></div></td>
			</tr>
				<?php endif; ?>
			<tr>
				<td><?= __('Password') ?></td>
				<td><?=  \Form::password([
					'name' => 'reply_nymphassword',
					'id' => 'reply_nymphassword',
					'value' => $user_pass,
					'required' => 'required'
				]);
					?> <span style="font-size: smaller;">(Password used for file deletion)</span>
				</td>
			</tr>

				<?php
				$postas = ['N' => __('User')];

				if (\Auth::has_access('comment.mod_capcode')) $postas['M'] = __('Moderator');
				if (\Auth::has_access('comment.admin_capcode')) $postas['A'] = __('Administrator');
				if (\Auth::has_access('comment.dev_capcode')) $postas['D'] = __('Developer');
				if (count($postas) > 1) :
					?>
				<tr>
					<td><?= __('Post as') ?></td>
					<td><?= \Form::select('reply_postas', 'User', $postas, array('id' => 'reply_postas')); ?></td>
				</tr>
					<?php endif; ?>

				<?php if ($radix->posting_rules) : ?>
			<tr class="rules">
				<td></td>
				<td>
					<?php
					echo \Markdown::parse($radix->posting_rules);
					?>
				</td>
			</tr>
			<tr class="rules">
				<td colspan="2">
					<div id="reply_ajax_notices"></div>
					<?php if (isset($reply_errors)) : ?>
					<span style="color: red"><?= $reply_errors ?></span>
					<?php endif; ?>
				</td>
			</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?= \Form::close() ?>
	<?php
	}
}