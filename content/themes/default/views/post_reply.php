<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if (isset($thread_id)) : ?>
<section class="post reply" id="reply">
	<header>
		<span>Reply to Thread [<a href="#reply" rel="popover" data-original-title="Replying" data-content="Don't worry, your post will not be uploaded to the original board.">?</a>]</span>
	</header>
	<div>
		<?php echo form_open('', array('class' => 'form-stacked')) ?>
		<fieldset>
			<div class="clearfix">
				<label for="reply_name">Name</label>
				<div class="input">
					<?php echo form_input(array(
						'name' => 'reply_name',
						'id' => 'reply_name'
					)); ?>
				</div>
			</div>
			<div class="clearfix">
				<label for="reply_email">E-mail</label>
				<div class="input">
					<?php echo form_input(array(
						'name' => 'reply_email',
						'id' => 'reply_email'
					)); ?>
				</div>
			</div>
			<div class="clearfix">
				<label for="reply_subject">Subject</label>
				<div class="input">
					<?php echo form_input(array(
						'name' => 'reply_subject',
						'id' => 'reply_subject'
					)); ?>
				</div>
			</div>
			<div class="clearfix">
				<label for="reply_comment">Comment</label>
				<div class="input">
					<?php echo form_textarea(array(
						'name' => 'reply_comment',
						'id' => 'reply_comment'
					)); ?>
				</div>
			</div>
			<div class="clearfix">
				<label for="reply_password">Password</label>
				<div class="input">
					<?php echo form_input(array(
						'name' => 'reply_password',
						'id' => 'reply_password'
					)); ?>
				</div>
			</div>
			<div class="actions">
			<?php
				echo form_hidden('id', $thread_id);
				echo form_submit(array(
					'name' => 'reply_action',
					'value' => 'Submit',
					'class' => 'btn primary'
				));
				echo '&nbsp;';
				echo form_submit(array(
					'name' => 'reply_action',
					'value' => 'Delete',
					'class' => 'btn secondary'
				));
			?>
			</div>
			<?php //print_r($post_data) ?>
		</fieldset>
		<?php echo form_close() ?>
	</div>
</section>
<?php endif; ?>
