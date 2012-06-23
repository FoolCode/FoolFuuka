<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if ((isset($enabled_tools_reply_box) && $enabled_tools_reply_box && !get_selected_radix()->archive) || (isset($thread_id))) :
?>

<?= form_open_multipart(get_selected_radix()->shortname . '/submit') ?>
<?= form_hidden('reply_numero', isset($thread_id)?$thread_id:0) ?>
<?php if (!isset($disable_image_upload) || !$disable_image_upload) : ?>
<?= form_hidden('MAX_FILE_SIZE', get_selected_radix()->max_image_size_kilobytes * 1024) ?>
<?php endif; ?>

<table id="reply">
	<tbody>
		<tr>
			<td><?= __('Name') ?></td>
			<td><?php
				echo form_input(array(
					'name' => 'name',
					'id' => 'reply_name_yep',
					'style' => 'display:none'
				));

				echo form_input(array(
					'name' => 'reply_bokunonome',
					'id' => 'reply_bokunonome',
					'value' => $this->fu->reply_name
				));
			?></td>
		</tr>
		<tr>
			<td><?= __('E-mail') ?></td>
			<td><?php
				echo form_input(array(
					'name' => 'email',
					'id' => 'reply_email_yep',
					'style' => 'display:none'
				));

				echo form_input(array(
					'name' => 'reply_elitterae',
					'id' => 'reply_elitterae',
					'value' => $this->fu->reply_email
				));
			?></td>
		</tr>
		<tr>
			<td><?= __('Subject') ?></td>
			<td>
				<?php
				echo form_input(array(
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
				echo form_submit($submit_array);
				?>

				[ <label><?php echo form_checkbox(array('name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1)) ?> Spoiler Image?</label> ]
			</td>
		</tr>
		<tr>
			<td><?= __('Comment') ?></td>
			<td><?php
				echo form_textarea(array(
					'name' => 'reply',
					'id' => 'reply_comment_yep',
					'style' => 'display:none'
				));
				echo form_textarea(array(
					'name' => 'reply_chennodiscursus',
					'id' => 'reply_chennodiscursus',
					'placeholder' => (!get_selected_radix()->archive && isset($thread_dead) && $thread_dead) ? __('This thread has been archived. Any replies made will be marked as ghost posts and will only affect the ghost index.') : '',
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
			<td><?php echo form_upload(array('name' => 'file_image', 'id' => 'file_image')) ?></td>
		</tr>
		<?php endif; ?>
		<tr>
			<td><?= __('Password') ?></td>
			<td><?=  form_password(array(
					'name' => 'reply_nymphassword',
					'id' => 'reply_nymphassword',
					'value' => $this->fu->reply_password,
					'required' => 'required'
				));
				?> <span style="font-size: smaller;">(Password used for file deletion)</span>
			</td>
		</tr>
		<?php if ($this->auth->is_mod_admin()) : ?>
		<tr>
			<td><?= __('Post as') ?></td>
			<td>
				<?php
					$postas = array('user' => __('User'), 'mod' => __('Moderator'));
					if ($this->auth->is_admin())
					{
						$postas['admin'] = __('Administrator');
					}
					echo form_dropdown('reply_postas', $postas, 'User', 'id="reply_postas"');
				?>
			</td>
		</tr>
		<?php endif; ?>
		<?php if (get_selected_radix()->posting_rules) : ?>
		<tr class="rules">
			<td></td>
			<td>
				<?php
					$this->load->library('Markdown_Parser');
					echo Markdown(get_selected_radix()->posting_rules);
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
<?= form_close() ?>
<?php endif; ?>