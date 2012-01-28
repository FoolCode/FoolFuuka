<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>


<?php if (isset($is_page) && !get_selected_radix()->archive) : ?>
	<section id="thread_form">
		<?php echo form_open_multipart(get_selected_radix()->shortname.'/sending') ?>
		<fieldset>
			<div style="float:left;">
				<div class="clearfix">
					<label for="reply_bokunonome">Name</label>
					<div class="input">
						<?php echo form_input(array(
							'name' => 'name',
							'id' => 'reply_name_yep',
							'style' => 'display:none'
						)); ?>
						<?php echo form_input(array(
							'name' => 'reply_bokunonome',
							'id' => 'reply_bokunonome',
							'value' => ((isset($this->fu_reply_name))?$this->fu_reply_name:'')
						)); ?>
					</div>
				</div>
				<div class="clearfix">
					<label for="reply_elitterae">E-mail</label>
					<div class="input">
						<?php echo form_input(array(
							'name' => 'email',
							'id' => 'reply_email_yep',
							'style' => 'display:none'
						)); ?>
						<?php echo form_input(array(
							'name' => 'reply_elitterae',
							'id' => 'reply_elitterae',
							'value' => ((isset($this->fu_reply_email))?$this->fu_reply_email:'')
						)); ?>
					</div>
				</div>
				<div class="clearfix">
					<label for="reply_talkingde">Subject</label>
					<div class="input">
						<?php echo form_input(array(
							'name' => 'reply_talkingde',
							'id' => 'reply_talkingde'
						)); ?>
					</div>
				</div>
				<div class="clearfix">
					<label for="reply_file">File</label>
					<div class="input">
						<?php echo form_upload(array(
							'name' => 'file_image',
							'id' => 'file_image'
						)); ?>
					</div>
				</div>
				<div class="clearfix">
					<label for="reply_nymphassword">Password</label>
					<div class="input">
						<?php echo form_password(array(
							'name' => 'reply_nymphassword',
							'id' => 'reply_nymphassword',
							'value' => $this->fu_reply_password
						)); ?>
					</div>
				</div>
				<?php
				// controls for administrators and moderators
				if($this->tank_auth->is_allowed()) : ?>
				<div class="clearfix">
					<label for="reply_postas">Post as</label>
					<div class="input">
						<?php
						$postas = array('user' => 'User', 'mod' => 'Moderator');
						if($this->tank_auth->is_admin())
						{
							$postas['admin'] = 'Administrator';
						}
						echo form_dropdown('reply_postas', $postas, 'User', 'id="reply_postas"'); ?>
					</div>
				</div>
				<?php endif; ?>
			</div>
			<div class="clearfix" style="float:left">
				<label for="reply_chennodiscursus">Comment</label>
				<div class="input">
					<?php echo form_textarea(array(
						'name' => 'reply',
						'id' => 'reply_comment_yep',
						'style' => 'display:none'
					)); ?>
					<?php echo form_textarea(array(
						'name' => 'reply_chennodiscursus',
						'id' => 'reply_chennodiscursus',
						'rows' => 3,
						'style' => 'height:155px; width:320px'
					)); ?>
				</div>
			</div>
			<div class="rules">
				<p>Rules and uses:</p>
				<ul>
					<li>Currently this is a strictly SAFE FOR WORK board.</li>
					<li>Use this board not to create meta threads on 4chan.</li>
					<li>It can be used as backup when 4chan is down.</li>
					<li>You can use this board for your projects. You can function and discuss here, NOT advertise here.</li>
					<li>You are responsible for your posts and images.</li>
					<li>Max image size: 3 MegaBytes</li>
				</ul>


					<div>
					<label for="reply_spoiler add-on">Spoiler
					<?php echo form_checkbox(array(
						'name' => 'reply_spoiler',
						'id' => 'reply_spoiler',
						'value' => 1
					));
					?>
					</label>
					<?php
						echo form_hidden('reply_numero', 0);
						echo form_hidden('MAX_FILE_SIZE', 3072);
						echo form_submit(array(
							'name' => 'reply_gattai',
							'value' => 'Submit',
							'class' => 'btn',
						));
					?>
					</div>
			</div>

		</fieldset>
		<?php echo form_close() ?>
	</section>
<?php endif; ?>