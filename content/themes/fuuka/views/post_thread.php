<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if (isset($is_page) && !get_selected_radix()->archive) : ?>
<?php echo form_open_multipart(get_selected_radix()->shortname .'/sending', array('id' => 'postform')); ?>
<table style="margin-left: auto; margin-right: auto">
	<tbody>
		<tr>
			<td class="subreply">
				<div class="theader">
					Create New Thread
				</div>
				<?php if(isset($reply_errors)) : ?>
				<span style="color:red"><?php echo $reply_errors ?></span>
				<?php endif; ?>
				<table>
					<tbody>
						<tr>
							<td class="postblock">Name</td>
							<td><?php echo form_input(array('name' => 'NAMAE', 'size' => 63, 'value' => ((isset($this->fu_reply_name))?$this->fu_reply_name:''))); ?></td>
						</tr>
						<tr>
							<td class="postblock">E-mail</td>
							<td><?php echo form_input(array('name' => 'MERU', 'size' => 63, 'value' => ((isset($this->fu_reply_email))?$this->fu_reply_email:''))); ?></td>
						</tr>
						<tr>
							<td class="postblock">Subject</td>
							<td><?php echo form_input(array('name' => 'subject', 'size' => 63)); ?></td>
						</tr>
						<tr>
							<td class="postblock">Comment</td>
							<td><?php echo form_textarea(array('name' => 'KOMENTO', 'cols' => 48, 'rows' => 4)); ?></td>
						</tr>
						<tr>
							<td class="postblock">File</td>
							<td><?php echo form_upload(array('name' => 'file_image', 'id' => 'file_image')); ?></td>
						</tr>
						<tr>
							<td class="postblock">Spoiler</td>
							<td><?php echo form_checkbox(array('name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1)); ?></td>
						</tr>
						<tr>
							<td class="postblock">Password <a class="tooltip" href="#">[?] <span>Password used for file deletion.</span></a></td>
							<td><?php echo form_password(array('name' => 'delpass', 'size' => 24, 'value' => $this->fu_reply_password)); ?></td>
						</tr>
						<?php if($this->tank_auth->is_allowed()) : ?>
						<tr>
							<td class="postblock">Post As</td>
							<td>
								<?php
								$postas = array('user' => 'User', 'mod' => 'Moderator');
								if($this->tank_auth->is_admin()) { $postas['admin'] = 'Administrator'; }
								echo form_dropdown('reply_postas', $postas,'id="reply_postas"');
								?>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td class="postblock">Action</td>
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
<?php echo form_close() ?>

<hr/>
<?php endif; ?>