<?php if (!defined('DOCROOT')) exit('No direct script access allowed'); ?>
	
<?php \Plugins::run_hook('fu.themes.default_after_op_open', array($radix)); ?>

<?= Form::open(array('enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $radix->shortname . '/submit')) ?>
<?= Form::hidden(\Config::get('security.csrf_token_key'), \Security::fetch_token()); ?>
<?= Form::hidden('reply_numero', isset($thread_id)?$thread_id:0, array('id' => 'reply_numero')) ?>
<?= isset($backend_vars['last_limit']) ? Form::hidden('reply_last_limit', $backend_vars['last_limit'])  : '' ?>

<table id="reply">
	<tbody>
		<tr>
			<td><?= __('Name') ?></td>
			<td><?php
				echo \Form::input(array(
					'name' => 'name',
					'id' => 'reply_name_yep',
					'style' => 'display:none'
				));

				echo \Form::input(array(
					'name' => 'reply_bokunonome',
					'id' => 'reply_bokunonome',
					'value' => $user_name
				));
			?></td>
		</tr>
		<tr>
			<td><?= __('E-mail') ?></td>
			<td><?php
				echo \Form::input(array(
					'name' => 'email',
					'id' => 'reply_email_yep',
					'style' => 'display:none'
				));

				echo \Form::input(array(
					'name' => 'reply_elitterae',
					'id' => 'reply_elitterae',
					'value' => $user_email
				));
			?></td>
		</tr>
		<tr>
			<td><?= __('Subject') ?></td>
			<td>
				<?php
				echo \Form::input(array(
					'name' => 'reply_talkingde',
					'id' => 'reply_talkingde',
				));
				?>

				<?php
				$submit_array = array(
					'data-function' => 'comment',
					'name' => 'reply_gattai',
					'value' => __('Submit'),
					'class' => 'btn',
				);
				
				echo Form::submit($submit_array);
				?>

				[ <label><?php echo \Form::checkbox(array('name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1)) ?> Spoiler Image?</label> ]
			</td>
		</tr>
		<tr>
			<td><?= __('Comment') ?></td>
			<td><?php
				echo \Form::textarea(array(
					'name' => 'reply',
					'id' => 'reply_comment_yep',
					'style' => 'display:none'
				));
				
				echo Form::textarea(array(
					'name' => 'reply_chennodiscursus',
					'id' => 'reply_chennodiscursus',
					'placeholder' => (!$radix->archive && isset($thread_dead) && $thread_dead) ? __('This thread has been archived. Any replies made will be marked as ghost posts and will only affect the ghost index.') : '',
				));
			?></td>
		</tr>
		<?php /*
		<tr>
			<td><?= __('Verification') ?></td>
			<td> <div>

				</div></td>
		</tr>
		 */ ?>
		<?php if (!isset($disable_image_upload) || !$disable_image_upload) : ?>
		<tr>
			<td><?= __('File') ?></td>
			<td><?php echo \Form::file(array('name' => 'file_image', 'id' => 'file_image')) ?></td>
		</tr>
		<tr>
			<td><?= __('Progress') ?></td>
			<td><div class="progress progress-info progress-striped active" style="width: 300px; margin-bottom: 2px"><div class="bar" style="width: 0%"></div></div></td>
		</tr>
		<?php endif; ?>
		<tr>
			<td><?= __('Password') ?></td>
			<td><?=  \Form::password(array(
					'name' => 'reply_nymphassword',
					'id' => 'reply_nymphassword',
					'value' => $user_pass,
					'required' => 'required'
				));
				?> <span style="font-size: smaller;">(Password used for file deletion)</span>
			</td>
		</tr>
		
		<?php
			$postas = array('N' => __('User'));

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
	
		<?php if (Radix::get_selected()->posting_rules) : ?>
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
			<span style="color:red"><?= $reply_errors ?></span>
			<?php endif; ?>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>
<?= \Form::close() ?>