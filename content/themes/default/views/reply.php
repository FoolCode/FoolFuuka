<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (isset($thread_id))
{
	echo '<div class="reply">';
	echo form_open();
		echo '<fieldset>';
			echo '<div class="clearfix">';
				echo '<label for="reply_name">Name</label>';
				echo '<div class="input">';
					echo form_input(array(
						'name' => 'reply_name',
						'id' => 'reply_name'
					));
				echo '</div>';
			echo '</div>';
			echo '<div class="clearfix">';
				echo '<label for="reply_email">E-mail</label>';
				echo '<div class="input">';
					echo form_input(array(
						'name' => 'reply_email',
						'id' => 'reply_email'
					));
				echo '</div>';
			echo '</div>';
			echo '<div class="clearfix">';
				echo '<label for="reply_subject">Subject</label>';
				echo '<div class="input">';
					echo form_input(array(
						'name' => 'reply_subject',
						'id' => 'reply_subject'
					));
				echo '</div>';
			echo '</div>';
			echo '<div class="clearfix">';
				echo '<label for="reply_comment">Comment</label>';
				echo '<div class="input">';
					echo form_textarea(array(
						'name' => 'reply_comment',
						'id' => 'reply_comment'
					));
				echo '</div>';
			echo '</div>';
			echo '<div class="clearfix">';
				echo '<label for="reply_password">Password</label>';
				echo '<div class="input">';
					echo form_input(array(
						'name' => 'reply_password',
						'id' => 'reply_password'
					));
				echo '</div>';
			echo '</div>';
			echo '<div class="actions">';
			echo form_hidden('reply_id', $thread_id);
			echo form_submit(array(
				'value' => 'Submit',
				'class' => 'btn primary',
			));
			echo '</div>';
		echo '</fieldset>';
	echo form_close();
	echo '</div>';

	print_r($post_data);
}