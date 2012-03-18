<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if ((isset($enabled_post_thread) && $enabled_post_thread && !get_selected_radix()->archive) ||
	(isset($thread_id))) : ?>
	<section class="thread_form">
		<?php
		echo form_open_multipart(get_selected_radix()->shortname . '/submit');
		echo form_hidden('reply_numero', isset($thread_id)?$thread_id:0);
		?>
		<fieldset>

			<div class="input-prepend">
				<label class="add-on" for="reply_talkingde"><?php echo _('Subject') ?></label><?php
	echo form_input(array(
		'name' => 'reply_talkingde',
		'id' => 'reply_talkingde',
		'class' => 'span6'
	));
		?>
			</div>

			<label class="comment_label" for="reply_chennodiscursus"><?php echo _('Comment') ?></label>

			<div class="pull-left">

				<div class="input-prepend">
					<label class="add-on" for="reply_bokunonome"><?php echo _('Name') ?></label><?php
			echo form_input(array(
				'name' => 'name',
				'id' => 'reply_name_yep',
				'style' => 'display:none'
			));

			echo form_input(array(
				'name' => 'reply_bokunonome',
				'id' => 'reply_bokunonome',
				'value' => ((isset($this->fu_reply_name)) ? $this->fu_reply_name : '')
			));
		?>
				</div>

				<div class="input-prepend">
					<label class="add-on" for="reply_elitterae"><?php echo _('E-mail') ?></label><?php
				echo form_input(array(
					'name' => 'email',
					'id' => 'reply_email_yep',
					'style' => 'display:none'
				));
				echo form_input(array(
					'name' => 'reply_elitterae',
					'id' => 'reply_elitterae',
					'value' => ((isset($this->fu_reply_email)) ? $this->fu_reply_email : '')
				));
		?>
				</div>

				
				<?php if(!get_selected_radix()->archive) : ?>
				<div class="input-prepend">
					<label class="add-on" for="reply_file"><?php echo _('File') ?></label><?php
				echo form_upload(array(
					'name' => 'file_image',
					'id' => 'file_image',
					'required' => 'required'
				));
				echo form_hidden('MAX_FILE_SIZE', 3072);
		?>
				<?php endif; ?>
				</div>
				<div class="input-prepend">
					<label class="add-on" for="reply_nymphassword"><?php echo _('Password') ?></label><?php
				echo form_password(array(
					'name' => 'reply_nymphassword',
					'id' => 'reply_nymphassword',
					'value' => $this->fu_reply_password,
					'required' => 'required'
				));
		?>
				</div>
				<?php if ($this->tank_auth->is_allowed()) : ?>
					<div class="input-prepend">
						<label class="add-on" for="reply_postas"><?php echo _('Post as') ?></label><?php
			$postas = array('user' => _('User'), 'mod' => _('Moderator'));
			if ($this->tank_auth->is_admin())
			{
				$postas['admin'] = _('Administrator');
			}
			echo form_dropdown('reply_postas', $postas, 'User', 'id="reply_postas"');
					?>
					</div>
				<?php endif; ?>

			</div>





			<div class="input-append pull-left">
				<?php
				echo form_textarea(array(
					'name' => 'reply',
					'id' => 'reply_comment_yep',
					'style' => 'display:none'
				));
				?><?php
			echo form_textarea(array(
				'name' => 'reply_chennodiscursus',
				'id' => 'reply_chennodiscursus',
				'rows' => 3,
				'style' => 'height:132px; width:320px;'
			));
				?>
			</div>

			<?php if(!isset($thread_id) || $thread_id == 0) : ?>
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
			</div>
			<?php endif; ?>


			<div class="btn-group" style="clear:both">

				<?php
				echo form_reset(array('class' => 'btn', 'name' => 'reset', 'value' => _('Reset')));
				
				$submit_array = array(
					'name' => 'reply_gattai_spoilered',
					'value' => _('Submit spoilered'),
					'class' => 'btn',
				);

				if (isset($thread_id) && $thread_id > 0)
				{
					$submit_array['data-function'] = 'comment';
					$submit_array['data-post'] = $thread_id;
				}
				echo form_submit($submit_array);
				
				$submit_array = array(
					'name' => 'reply_gattai',
					'value' => _('Submit'),
					'class' => 'btn btn-primary',
				);

				if (isset($thread_id) && $thread_id > 0)
				{
					$submit_array['data-function'] = 'comment';
					$submit_array['data-post'] = $thread_id;
				}
				echo form_submit($submit_array);
				
				?>
				
			</div>

			<div id="reply_ajax_notices"></div>
			<?php if(isset($reply_errors)) : ?>
				<span style="color:red"><?php echo $reply_errors ?></span>
			<?php endif; ?>

		</fieldset>
		<?php echo form_close() ?>
	</section>
<?php endif; ?>