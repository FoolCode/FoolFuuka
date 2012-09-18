<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<div id="reply" class="thread_form_wrap clearfix">
<section class="thread_form clearfix">
<?= Form::open(array('enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $radix->shortname . '/submit')) ?>
<?= Form::hidden(\Config::get('security.csrf_token_key'), \Security::fetch_token()); ?>
<?= Form::hidden('reply_numero', isset($thread_id)?$thread_id:0, array('id' => 'reply_numero')) ?>
<?= isset($backend_vars['last_limit']) ? Form::hidden('reply_last_limit', $backend_vars['last_limit'])  : '' ?>
<fieldset>

	<div class="input-prepend">
		<label class="add-on" for="reply_talkingde"><?= __('Subject') ?></label><?php
		echo Form::input(array(
			'name' => 'reply_talkingde',
			'id' => 'reply_talkingde',
			'class' => 'span6'
		));
		?>
	</div>

	<label class="comment_label" for="reply_chennodiscursus"><?= __('Comment') ?></label>

	<div class="pull-left">

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


		<?php if (!isset($disable_image_upload) || !$disable_image_upload) : ?>
		<div class="input-prepend">
			<label class="add-on" for="file_image"><?= __('File') ?></label><input type="file" name="file_image" id="file_image" />
			<?= Form::hidden('MAX_FILE_SIZE', $radix->max_image_size_kilobytes) ?>
		</div>
		<?php endif; ?>

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
		<?php
			$postas = array('N' => __('User'));

			if (\Auth::has_access('comment.mod_capcode')) $postas['M'] = __('Moderator');
			if (\Auth::has_access('comment.admin_capcode')) $postas['A'] = __('Administrator');
			if (\Auth::has_access('comment.dev_capcode')) $postas['D'] = __('Developer');
			if (count($postas) > 1) :
		?>
		<div class="input-prepend">
			<label class="add-on" for="reply_postas"><?= __('Post As') ?></label>
				<?= Form::select('reply_postas', 'User', $postas, array('id' => 'reply_postas')); ?>
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
	</div>


	<div class="rules pull-left">
		<div class="btn-group" style="margin-bottom:5px">
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
		<div class="progress progress-info progress-striped active" style="height:8px"><div class="bar" style="width: 0%"></div></div>
		<?php
			if ($radix->posting_rules)
			{
				echo \Markdown::parse($radix->posting_rules);
			}
		?>
	</div>

	<div id="reply_ajax_notices"></div>
	<?php if (isset($reply_errors)) : ?>
	<span style="color:red"><?= $reply_errors ?></span>
	<?php endif; ?>
</fieldset>
<?= Form::close() ?>
</section>
</div>