<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!isset($thread_id) && isset($is_page) && get_selected_radix() && !get_selected_radix()->archive) : ?>
<?= form_open_multipart(get_selected_radix()->shortname .'/sending', array('id' => 'postform')) ?>
<table style="margin-left: auto; margin-right: auto">
	<tbody>
		<tr>
			<td class="subreply">
				<div class="theader">
					<?= __('Create New Thread') ?>
				</div>
				<?php if (isset($reply_errors)) : ?>
					<span style="color:red"><?= $reply_errors ?></span>
				<?php endif; ?>
				<table>
					<tbody>
						<tr>
							<td class="postblock"><?= __('Name') ?></td>
							<td><?php echo form_input(array('name' => 'NAMAE', 'size' => 63, 'value' => $this->fu->reply_name)) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('E-mail') ?></td>
							<td><?php echo form_input(array('name' => 'MERU', 'size' => 63, 'value' => $this->fu->reply_email)) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Subject') ?></td>
							<td><?php echo form_input(array('name' => 'subject', 'size' => 63)) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Comment') ?></td>
							<td><?php echo form_textarea(array('name' => 'KOMENTO', 'cols' => 48, 'rows' => 4)) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('File') ?></td>
							<td><?php echo form_upload(array('name' => 'file_image', 'id' => 'file_image')) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Spoiler') ?></td>
							<td><?php echo form_checkbox(array('name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1)) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Password') ?> <a class="tooltip" href="#">[?] <span><?= __('This is used for file and post deletion.') ?></span></a></td>
							<td><?php echo form_password(array('name' => 'delpass', 'size' => 24, 'value' => $this->fu->reply_password)) ?></td>
						</tr>
						<?php if ($this->auth->is_mod_admin()) : ?>
						<tr>
							<td class="postblock"><?= __('Post As') ?></td>
							<td><?php
								$postas = array('user' => __('User'), 'mod' => __('Moderator'));
								if ($this->auth->is_admin())
								{
									$postas['admin'] = __('Administrator');
								}
								echo form_dropdown('reply_postas', $postas,'id="reply_postas"');
							?></td>
						</tr>
						<?php endif; ?>
						<tr>
							<td class="postblock"><?= __('Action') ?></td>
							<td>
								<?php
									echo form_hidden('parent', 0);
									echo form_hidden('MAX_FILE_SIZE', 3072);
									echo form_submit(array(
										'name' => 'reply_action',
										'value' => 'Submit'
									));
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
<?= form_close() ?>

<hr/>
<?php endif; ?>


<?php if (isset($thread_id)) : ?>
<table>
	<tbody>
		<tr>
			<td class="doubledash">&gt;&gt;</td>
			<td class="subreply">
				<div class="theader">
					<?= __('Reply to Thread') ?> <a class="tooltip-red" href="#">[?] <span><?= __("Don't expect anything heroic. This post will not be posted to any other board.") ?></span></a>
				</div>
				<?php if (isset($reply_errors)) : ?>
				<span style="color:red"><?= $reply_errors ?></span>
				<?php endif; ?>
				<span><?= (!get_selected_radix()->archive && isset($thread_dead) && $thread_dead) ? __('This thread has been archived. Any replies made will be marked as ghost posts and will only affect the ghost index.') : '' ?></span>
				<table>
					<tbody>
						<tr>
							<td class="postblock"><?= __('Name') ?></td>
							<td><?php echo form_input(array('name' => 'NAMAE', 'size' => 63, 'value' => $this->fu->reply_name)); ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('E-mail') ?></td>
							<td><?php echo form_input(array('name' => 'MERU', 'size' => 63, 'value' => $this->fu->reply_email)); ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Subject') ?></td>
							<td><?php echo form_input(array('name' => 'subject', 'size' => 63)); ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Comment') ?></td>
							<td><?php echo form_textarea(array('name' => 'KOMENTO', 'cols' => 48, 'rows' => 4)); ?></td>
						</tr>
						<?php if (!get_selected_radix()->archive) : ?>
						<tr>
							<td class="postblock"><?= __('File') ?></td>
							<td><?php echo form_upload(array('name' => 'file_image', 'id' => 'file_image')); ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Spoiler') ?></td>
							<td><?php echo form_checkbox(array('name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1)); ?></td>
						</tr>
						<?php endif; ?>
						<tr>
							<td class="postblock"><?= __('Password') ?> <a class="tooltip" href="#">[?] <span><?= __('This is used for file and post deletion.') ?></span></a></td>
							<td><?php echo form_password(array('name' => 'delpass', 'size' => 24, 'value' => $this->fu->reply_password)); ?></td>
						</tr>
						<?php if ($this->auth->is_mod_admin()) : ?>
						<tr>
							<td class="postblock"><?= __('Post As') ?></td>
							<td>
								<?php
								$postas = array('user' => __('User'), 'mod' => __('Moderator'));
								if ($this->auth->is_admin()) {
									$postas['admin'] = __('Administrator');
								}
								echo form_dropdown('reply_postas', $postas,'id="reply_postas"');
								?>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td class="postblock"><?= __('Action') ?></td>
							<td>
								<?php
									echo form_hidden('parent', $thread_id);
									echo form_hidden('MAX_FILE_SIZE', 3072);
									echo form_submit(array(
										'name' => 'reply_action',
										'value' => 'Submit'
									));
									echo form_submit(array(
										'name' => 'reply_delete',
										'value' => 'Delete Selected Posts'
									));
									echo form_submit(array(
										'name' => 'reply_report',
										'value' => 'Report Selected Posts'
									));
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
<?php endif; ?>
