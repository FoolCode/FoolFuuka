<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if (!get_selected_board()->archive && isset($is_page)) : ?>
<div style="postition:relative"></div>
<div align="center" class="postarea">
	<?php echo form_open_multipart(get_selected_board()->shortname.'/sending', array('name' => 'post')) ?>
		<?php echo form_hidden('resto', 0) ?>
		<table cellpadding="1" cellspacing="1">
			<tbody>
				<tr>
					<td></td>
					<td class="postblock" align="left">
						<b>Name</b>
					</td>
					<td>
						<?php echo form_input(array('class' => 'inputtext', 'name' => 'name', 'size' => 28)) ?>
						<span id="tdname"></span>
					</td>
				</tr>
				<tr>
					<td></td>
					<td class="postblock" align="left">
						<b>E-mail</b>
					</td>
					<td>
						<?php echo form_input(array('class' => 'inputtext', 'name' => 'email', 'size' => 28)) ?>
						<span id="tdemail"></span>
					</td>
				</tr>
				<tr>
					<td></td>
					<td class="postblock" align="left">
						<b>Subject</b>
					</td>
					<td>
						<?php echo form_input(array('class' => 'inputtext', 'name' => 'sub', 'size' => 28)) ?>
					</td>
				</tr>
				<tr>
					<td vlaign="bottom"></td>
					<td class="postblock" align="left">
						<b>Comment</b>
					</td>
					<td>
						<?php echo form_textarea(array('class' => 'inputtext', 'name' => 'com', 'cols' => 48, 'rows' => 4, 'wrap' => 'soft')) ?>
					</td>
				</tr>
				<tr>
					<td vlaign="bottom"></td>
					<td class="postblock" align="left">
						<b>File</b>
					</td>
					<td>
						<?php echo form_upload(array('name' => 'upfile')) ?>
						<?php echo form_hidden('MAX_FILE_SIZE', 3072) ?>
					</td>
				</tr>
				<?php if($this->tank_auth->is_allowed()) : ?>
				<tr>
					<td></td>
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
					<td></td>
					<td class="postblock" align="left">
						<b>Password</b>
					</td>
					<td>
						<?php echo form_password(array('name' => 'pwd', 'size' => 24, 'value' => $this->fu_reply_password)) ?>
						<?php echo form_submit(array('name' => 'com_submit', 'value' => 'Submit')) ?>
					</td>
				</tr>
			</tbody>
		</table>
	<?php echo form_close() ?>
</div>
<hr>
<?php else: ?>
<div style="postition:relative"></div>
<?php endif; ?>