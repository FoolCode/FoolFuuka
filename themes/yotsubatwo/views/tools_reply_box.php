<?php if (!defined('DOCROOT')) exit('No direct script access allowed'); ?>

<?= Form::open(array('enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $radix->shortname . '/submit')) ?>
<?= Form::hidden('reply_numero', isset($thread_id)?$thread_id:0) ?>
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
					'name' => 'reply_gattai',
					'value' => __('Submit'),
					'class' => 'btn'
				);

				if (isset($thread_id) && $thread_id > 0)
				{
					$submit_array['data-function'] = 'comment';
					$submit_array['data-post'] = $thread_id;
				}
				echo \Form::submit($submit_array);
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
				echo \Form::textarea(array(
					'name' => 'reply_chennodiscursus',
					'id' => 'reply_chennodiscursus',
					'placeholder' => (!Radix::get_selected()->archive && isset($thread_dead) && $thread_dead) ? __('This thread has been archived. Any replies made will be marked as ghost posts and will only affect the ghost index.') : '',
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
			<td><?php echo \Form::upload(array('name' => 'file_image', 'id' => 'file_image')) ?></td>
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
		<?php if (\Auth::has_access('comment.as_mod')) : ?>
		<tr>
			<td><?= __('Post as') ?></td>
			<td>
				<?php
					$postas = array('user' => __('User'), 'mod' => __('Moderator'));
					if (\Auth::has_access('comment.as_admin'))
					{
						$postas['admin'] = __('Administrator');
					}
					echo \Form::dropdown('reply_postas', $postas, 'User', 'id="reply_postas"');
				?>
			</td>
		</tr>
		<?php endif; ?>
		<?php if (Radix::get_selected()->posting_rules) : ?>
		<tr class="rules">
			<td></td>
			<td>
				<?php
					$this->load->library('Markdown_Parser');
					echo Markdown(Radix::get_selected()->posting_rules);
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