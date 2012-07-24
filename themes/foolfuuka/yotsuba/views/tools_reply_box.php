<?php if (!defined('DOCROOT')) exit('No direct script access allowed');

if ((isset($enabled_tools_reply_box) && $enabled_tools_reply_box && !Radix::get_selected()->archive) || (isset($thread_id))) :
?>

<hr/>

<div class="mobilePostFormToggle mobile hidden" id="mpostform" onclick="toggleMobilePostForm();">
	<div>Show the Posting Form</div>
</div>

<div style='position:relative'></div>
<?= form_open_multipart(Radix::get_selected()->shortname . '/submit') ?>
<?= form_hidden('reply_numero', isset($thread_id)?$thread_id:0) ?>
<?php if (!isset($disable_image_upload) || !$disable_image_upload) : ?>
	<?= form_hidden('MAX_FILE_SIZE', Radix::get_selected()->max_image_size_kilobytes * 1024) ?>
<?php endif; ?>

<table class="postForm hideMobile" id="postForm">
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
			<td><?php
				echo form_input(array(
					'name' => 'reply_talkingde',
					'id' => 'reply_talkingde',
				));


			?><?= form_submit(array(
				'name' => 'reply_gattai',
				'value' => __('Submit'),
				'class' => 'btn btn-primary',
			)); ?>[<label><?php echo form_checkbox(array('name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1)) ?><?= __('Spoiler Image?') ?></label>]</td>
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
					'placeholder' => (!Radix::get_selected()->archive && isset($thread_dead) && $thread_dead) ? __('This thread has been archived. Any replies made will be marked as ghost posts and will only affect the ghost index.') : '',
				));
			?></td>
		</tr>
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
				?> <span style="font-size: smaller;">(<?= __('This is used for file and post deletion.') ?>)</span>
			</td>
		</tr>
		<?php if (Auth::has_access('maccess.mod')) : ?>
		<tr>
			<td><?= __('Post As') ?></td>
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
		<?php if (Radix::get_selected()->posting_rules) : ?>
		<tr class="rules">
			<td colspan="2">
				<ul class="rules">
					<?php
						$this->load->library('Markdown_Parser');
						echo Markdown(Radix::get_selected()->posting_rules);
					?>
				</ul>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>
	<?= form_close() ?>

<?php endif; ?>