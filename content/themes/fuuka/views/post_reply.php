<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if (isset($thread_id)) : ?>
<table>
	<tbody>
		<tr>
			<td class="doubledash">&gt;&gt;</td>
			<td class="subreply">
				<div class="theader">
					Reply to Thread <a class="tooltip-red" href="#">[?] <span>Don't expect anything heroic. Your post will not be uploaded to the original board.</span></a>
				</div>
				<table>
					<tbody>
						<tr>
							<td class="postblock">Name</td>
							<td><?php echo form_input(array('name' => 'reply_bokunonome', 'id' => 'reply_name', 'value' => ((isset($this->fu_reply_name))?$this->fu_reply_name:''))); ?></td>
						</tr>
						<tr>
							<td class="postblock">E-mail</td>
							<td><?php echo form_input(array('name' => 'reply_elitterae', 'id' => 'reply_email', 'value' => ((isset($this->fu_reply_email))?$this->fu_reply_email:''))); ?></td>
						</tr>
						<tr>
							<td class="postblock">Subject</td>
							<td><?php echo form_input(array('name' => 'reply_talkingde', 'id' => 'reply_subject')); ?></td>
						</tr>
						<tr>
							<td class="postblock">Comment</td>
							<td><?php echo form_textarea(array('name' => 'reply_chennodiscursus', 'id' => 'reply_comment')); ?></td>
						</tr>
						<tr>
							<td class="postblock">Password</td>
							<td><?php echo form_password(array('name' => 'reply_nymphassword', 'id' => 'reply_password', 'value' => $this->fu_reply_password)); ?></td>
						</tr>
						<tr>
							<td class="postblock">Action</td>
							<td>
								<?php
									echo form_hidden('reply_numero', $thread_id);
									echo form_submit(array(
										'name' => 'reply_action',
										'value' => 'Submit'
									));
									echo form_submit(array(
										'name' => 'reply_delete',
										'value' => 'Delete Selected Posts'
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
