<?php
if ( ! defined('DOCROOT'))
	exit('No direct script access allowed');

if ( ! isset($thread_id) && ! $radix->archive) : ?>
<?= Form::open(['enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $radix->shortname . '/submit', 'id' => 'postform']) ?>
<?= Form::hidden(\Config::get('security.csrf_token_key'), \Security::fetch_token()); ?>
<?= isset($backend_vars['last_limit']) ? Form::hidden('reply_last_limit', $backend_vars['last_limit'])  : '' ?>
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
							<td><?php echo \Form::input(['name' => 'NAMAE', 'size' => 63, 'value' => $user_name]) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('E-mail') ?></td>
							<td><?php echo \Form::input(['name' => 'MERU', 'size' => 63, 'value' => $user_email]) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Subject') ?></td>
							<td><?php echo \Form::input(['name' => 'subject', 'size' => 63]) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Comment') ?></td>
							<td><?php echo \Form::textarea(['name' => 'KOMENTO', 'cols' => 48, 'rows' => 4]) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('File') ?></td>
							<td><?php echo \Form::file(['name' => 'file_image', 'id' => 'file_image']) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Spoiler') ?></td>
							<td><?php echo \Form::checkbox(['name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1]) ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Password') ?> <a class="tooltip" href="#">[?] <span><?= __('This is used for file and post deletion.') ?></span></a></td>
							<td><?php echo \Form::password(['name' => 'delpass', 'size' => 24, 'value' => $user_pass]) ?></td>
						</tr>
						<?php if (\ReCaptcha::available()) : ?>
						<tr id="recaptcha_widget">
							<td class="postblock"><?= __('Verification') ?><br/>(<?= __('Optional') ?>)</td>
							<td>
								<script type="text/javascript" src="//www.google.com/recaptcha/api/challenge?k=<?= \Config::get('recaptcha.public_key') ?>"></script>
								<noscript>
									<iframe src="//www.google.com/recaptcha/api/noscript?k=<?= \Config::get('recaptcha.public_key') ?>" height="300" width="500" frameborder="0"></iframe><br/>
									<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
									<input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
								</noscript>
							</td>
						</tr>
						<?php endif; ?>
						<?php
							$postas = ['N' => __('User')];

							if (\Auth::has_access('comment.mod_capcode')) $postas['M'] = __('Moderator');
							if (\Auth::has_access('comment.admin_capcode')) $postas['A'] = __('Moderator');
							if (\Auth::has_access('comment.dev_capcode')) $postas['D'] = __('Developer');
							if (count($postas) > 1) :
						?>
						<tr>
							<td class="postblock"><?= __('Post As') ?></td>
							<td>
								<?= \Form::select('reply_postas', 'User', $postas, ['id' => 'reply_postas']); ?>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td class="postblock"><?= __('Action') ?></td>
							<td>
								<?php
									echo \Form::hidden('parent', 0);
									echo \Form::hidden('MAX_FILE_SIZE', 3072);
									echo \Form::submit([
										'name' => 'reply_action',
										'value' => 'Submit'
									]);
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
<?= \Form::close() ?>

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
				<span><?= ( ! Radix::getSelected()->archive && isset($thread_dead) && $thread_dead) ? __('This thread has been archived. Any replies made will be marked as ghost posts and will only affect the ghost index.') : '' ?></span>
				<table>
					<tbody>
						<tr>
							<td class="postblock"><?= __('Name') ?></td>
							<td><?php echo \Form::input(['name' => 'NAMAE', 'size' => 63, 'value' => $user_name]); ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('E-mail') ?></td>
							<td><?php echo \Form::input(['name' => 'MERU', 'size' => 63, 'value' => $user_email]); ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Subject') ?></td>
							<td><?php echo \Form::input(['name' => 'subject', 'size' => 63]); ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Comment') ?></td>
							<td><?php echo \Form::textarea(['name' => 'KOMENTO', 'cols' => 48, 'rows' => 4]); ?></td>
						</tr>
						<?php if ( ! Radix::getSelected()->archive) : ?>
						<tr>
							<td class="postblock"><?= __('File') ?></td>
							<td><?php echo \Form::file(['name' => 'file_image', 'id' => 'file_image']); ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('Spoiler') ?></td>
							<td><?php echo \Form::checkbox(['name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1]); ?></td>
						</tr>
						<?php endif; ?>
						<tr>
							<td class="postblock"><?= __('Password') ?> <a class="tooltip" href="#">[?] <span><?= __('This is used for file and post deletion.') ?></span></a></td>
							<td><?php echo \Form::password(['name' => 'delpass', 'size' => 24, 'value' => $user_pass]); ?></td>
						</tr>
						<?php if (\ReCaptcha::available()) : ?>
						<tr id="recaptcha_widget">
							<td class="postblock"><?= __('Verification') ?><br/>(<?= __('Optional') ?>)</td>
							<td>
								<script type="text/javascript" src="//www.google.com/recaptcha/api/challenge?k=<?= \Config::get('recaptcha.public_key') ?>"></script>
								<noscript>
									<iframe src="//www.google.com/recaptcha/api/noscript?k=<?= \Config::get('recaptcha.public_key') ?>" height="300" width="500" frameborder="0"></iframe><br/>
									<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
									<input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
								</noscript>
							</td>
						</tr>
						<?php endif; ?>
						<?php
							$postas = ['N' => __('User')];

							if (\Auth::has_access('comment.mod_capcode')) $postas['M'] = __('Moderator');
							if (\Auth::has_access('comment.admin_capcode')) $postas['A'] = __('Moderator');
							if (\Auth::has_access('comment.dev_capcode')) $postas['D'] = __('Developer');
							if (count($postas) > 1) :
						?>
						<tr>
							<td class="postblock"><?= __('Post As') ?></td>
							<td>
								<?= \Form::select('reply_postas', 'User', $postas, ['id' => 'reply_postas']); ?>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td class="postblock"><?= __('Action') ?></td>
							<td>
								<?php
									echo \Form::hidden('parent', $thread_id);
									echo \Form::hidden('MAX_FILE_SIZE', 3072);
									echo \Form::submit([
										'name' => 'reply_action',
										'value' => 'Submit'
									]);
									echo \Form::submit([
										'name' => 'reply_delete',
										'value' => 'Delete Selected Posts'
									]);
									echo \Form::submit([
										'name' => 'reply_report',
										'value' => 'Report Selected Posts'
									]);
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