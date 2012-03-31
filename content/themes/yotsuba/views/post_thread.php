<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if (isset($thread_id)) : ?>
<table width="100%">
	<tbody>
	<tr>
		<th bgcolor="#e04000"><font color="#FFFFFF">Posting Mode: Reply</font></th>
	</tr>
	</tbody>
</table>
<span style="left: 5px; position: absolute;">[<a href="../../" accesskey="a">Return</a>]</span>
<?php endif; ?>
<div style="postition:relative"></div>

<?php if (!isset($thread_id) && isset($is_page) && !get_selected_radix()->archive) : ?>
<div align="center" class="postarea">
	<?php echo form_open_multipart(get_selected_radix()->shortname.'/sending', array('name' => 'post')) ?>
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
						[<label><?php echo form_checkbox(array('name' => 'spoiler', 'id' => 'spoiler', 'value' => 1)); ?> Spoiler Image?</label>]
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
						<?php echo form_upload(array('name' => 'file_image')) ?>
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

<hr/>
<?php endif; ?>

<?php if (isset($thread_id)) : ?>
<div align="center" class="postarea">
	<?php echo form_open_multipart(get_selected_radix()->shortname.'/sending', array('name' => 'post')) ?>
	<?php echo form_hidden('resto', $thread_id) ?>
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
			<?php if(!get_selected_radix()->archive) : ?>
		<tr>
			<td vlaign="bottom"></td>
			<td class="postblock" align="left">
				<b>File</b>
			</td>
			<td>
				<?php echo form_upload(array('name' => 'file_image')) ?>
				<?php echo form_hidden('MAX_FILE_SIZE', 3072) ?>
			</td>
		</tr>
			<?php endif; ?>
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

<hr/>
<?php endif; ?>