<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if ($enabled_post_reply) : ?>
<section id="reply" class="thread_post">
	<header>
		<span>Reply to Thread [<a href="#reply" data-rel="popover" data-original-title="Replying" data-content="Don't worry, your post will not be uploaded to the original board.">?</a>]</span>
	</header>
	<?php echo form_open_multipart(get_selected_radix()->shortname.'/submit') ?>
	<fieldset>
		<div style="float:left; padding-left:30px">
			<div class="clearfix">
				<?php if(isset($reply_errors)) : ?>
				<span style="color:red"><?php echo $reply_errors ?></span>
				<?php endif; ?>
				<div id="reply_ajax_notices"></div>
			</div>
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
			<?php if(!get_selected_radix()->archive) : ?>
			<div class="clearfix">
				<label for="reply_file">File</label>
				<div class="input">
					<?php echo form_upload(array(
						'name' => 'file_image',
						'id' => 'file_image',
						'required' => 'required'
					)); ?>
				</div>
			</div>
			<div class="clearfix">
				<label for="reply_spoiler">Spoiler</label>
				<span class="checkbox-wrap">
					<?php echo form_checkbox(array(
						'name' => 'reply_spoiler',
						'id' => 'reply_spoiler',
						'value' => 1,
						'style' => 'height:13px; width:13px;'
					));	?>
				</span>
			</div>
			<?php endif; ?>
			<div class="clearfix">
				<label for="reply_nymphassword">Password</label>
				<div class="input">
					<?php echo form_password(array(
						'name' => 'reply_nymphassword',
						'id' => 'reply_nymphassword',
						'value' => $this->fu_reply_password,
						'required' => 'required'
					)); ?>
				</div>
			</div>
			<?php if($this->tank_auth->is_allowed()) : ?>
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
			<div class="actions">
				<?php
				echo form_hidden('reply_numero', 0);
				echo form_hidden('MAX_FILE_SIZE', 3072);
				echo form_submit(array(
					'name' => 'reply_gattai',
					'value' => 'Submit',
					'class' => 'btn primary',
					'data-function' => 'comment',
					'data-post' => $thread_id
				)); ?>
			</div>
		</div>
	</fieldset>
	<?php echo form_close() ?>
</section>
<?php endif; ?>
