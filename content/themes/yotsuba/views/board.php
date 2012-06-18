<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class="board">

	<?php
	foreach ($posts as $key => $post) :
		if (isset($post['op'])) :
			$op = $post['op'];
			$selected_radix = isset($op->board)?$op->board:get_selected_radix();

			$num =  $op->num . ( $op->subnum ? '_' . $op->subnum : '' );
			$quote_mode = 'thread';
	?>

	<div class="thread" id="t<?= $op->thread_num ?>">
		<div class="postContainer opContainer" id="pc<?= $num ?>">
			<div id="<?= $num ?>" class="post op">
				<div class="postInfoM mobile" id="pim<?= $num ?>">
					<span class="postNum nameBlock<?= (($op->capcode == 'M') ? ' capcodeMod':'') . (($op->capcode == 'A') ? ' capcodeAdmin':'') ?>">
						<span class="subject"><?= $op->title_processed ?></span>
						<span class="name"><?= ($op->email_processed && $op->email_processed != 'noko') ? '<a href="mailto:' . form_prep($op->email_processed) . '">' . $op->name_processed . '</a>' : $op->name_processed ?></span>
						<?php if ($op->trip) : ?><span class="postertrip"><?= $op->trip ?></span><?php endif; ?>
						<?php if (in_array($op->capcode, array('M', 'A'))) : ?>
							<strong class="capcode">## <?= (($op->capcode == 'M') ? 'Mod':'') . (($op->capcode == 'A') ? 'Admin':'') ?></strong>
							<?php if ($op->capcode == 'M') : ?><img src="//static.4chan.org/image/modicon.gif" alt="This user is a Moderator." title="<?= __('This user is a Moderator.') ?>" class="identityIcon" style="float: none!important; margin-left: 0px;"><?php endif; ?>
							<?php if ($op->capcode == 'A') : ?><img src="//static.4chan.org/image/adminicon.gif" alt="This user is an Administrator." title="<?= __('This user is an Administrator.') ?>" class="identityIcon" style="float: none!important; margin-left: 0px;"><?php endif; ?>
						<?php endif; ?>
						<br/>
						<em><a href="" title="Highlight this post">No.</a><a href="res/1418#q1418" title="Quote this post">1418</a></em>
					</span>
					<span class="dateTime">04/27/12(Fri)22:49:38</span>
				</div>

				<?php if ($op->preview_orig) : ?>
				<div class="file" id="f<?= $num ?>">
					<div class="fileInfo">
						<span class="fileText">
							<?= __('File:') ?>
							<a href="<?= ($op->media_link) ? $op->media_link : $op->remote_media_link ?>" target="_blank"><?= $op->media ?></a>-(<?= byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ', ' . $op->media_filename_processed ?>)
						</span>
					</div>
					<a class="fileThumb" href="<?= ($op->media_link) ? $op->media_link : $op->remote_media_link ?>" target="_blank">
						<img src="<?= $op->thumb_link ?>" alt="<?= byte_format($op->media_size, 0) ?>" data-md5="<?= $op->media_hash ?>"<?php if ($op->preview_w > 0 && $op->preview_h > 0) : ?> style="height: <?= $op->preview_h ?>px; width: <?= $op->preview_w ?>px;"<?php endif; ?>/>
					</a>
				</div>
				<?php endif; ?>

				<div class="postInfo" id="pi<?= $num ?>">
					<input type="checkbox" name="post[]" value="<?= $op->doc_id ?>" />
					<span class="subject"><?= $op->title_processed ?></span>
					<span class="nameBlock<?= (($op->capcode == 'M') ? ' capcodeMod':'') . (($op->capcode == 'A') ? ' capcodeAdmin':'') ?>">
						<span class="name"><?= ($op->email_processed && $op->email_processed != 'noko') ? '<a href="mailto:' . form_prep($op->email_processed) . '">' . $op->name_processed . '</a>' : $op->name_processed ?></span>
						<?php if ($op->trip) : ?><span class="postertrip"><?= $op->trip ?></span><?php endif; ?>
						<?php if (in_array($op->capcode, array('M', 'A'))) : ?>
							<strong class="capcode">## <?= (($op->capcode == 'M') ? 'Mod':'') . (($op->capcode == 'A') ? 'Admin':'') ?>"></strong>
							<?php if ($op->capcode == 'M') : ?><img src="<?= site_url('content/themes/yotsuba/images/') . 'icon-mod.gif' ?>" alt="This user is a Moderator." title="This user is a Moderator." class="identityIcon" style="float: none!important; margin-left: 0px;"><?php endif; ?>
							<?php if ($op->capcode == 'A') : ?><img src="<?= site_url('content/themes/yotsuba/images/') . 'icon-admin.gif' ?>" alt="This user is an Administrator." title="This user is an Administrator." class="identityIcon" style="float: none!important; margin-left: 0px;"><?php endif; ?>
						<?php endif; ?>
					</span>
					<span class="dateTime"><?= gmdate('D M d H:i:s Y', $op->original_timestamp) ?></span>
					<span class="postNum">
						<a href="<?= site_url(array($selected_radix->shortname, 'thread', $op->thread_num)) ?>#<?= $num ?>" title="Highlight this post">No.</a><a href="<?= site_url(array($selected_radix->shortname, $quote_mode, $op->thread_num)) ?>#q<?= $num ?>" title="Quote this post"><?= $num ?></a>

						[<a href="<?= site_url(array($selected_radix->shortname, 'thread', $op->thread_num)) ?>" class="replylink">Reply</a>]
					</span>
				</div>
				<blockquote class="postMessage" id="m<?= $num ?>"><?= $op->comment_processed ?></blockquote>
			</div>
			<div class="postLink mobile">
				<span class="info">
					<?php if (isset($post['omitted']) && $post['omitted'] > 0) : ?>
					<strong>9 posts omitted.</strong><br/><em>(9 have images)</em>
					<?php endif; ?>
				</span>
				<a href="<?= site_url(array($selected_radix->shortname, 'thread', $op->thread_num)) ?>" class="quotelink button">View Thread</a>
			</div>
		</div>
		<?php if (isset($post['omitted']) && $post['omitted'] > 0) : ?>
		<span class="summary desktop">
			<?= $post['omitted'] . ' ' . _ngettext('post', 'posts', $post['omitted']) ?>
			<?php if (isset($post['images_omitted']) && $post['images_omitted'] > 0) : ?>
			<?= ' ' . __('and') . ' ' . $post['images_omitted'] . ' ' . _ngettext('image', 'images', $post['images_omitted']) ?>
			<?php endif; ?>
			<?= ' ' . _ngettext('omitted', 'omitted', $post['omitted'] + $post['images_omitted']) ?>.
			<?= __('Click Reply to View.') ?>
		</span>
		<?php endif; ?>

	<?php endif; ?>

	<?php
		if (isset($post['posts']))
		{
			foreach ($post['posts'] as $p)
			{
				if(!isset($thread_id))
					$thread_id = NULL;

				if ($p->thread_num == 0)
					$p->thread_num = $p->num;

				echo $this->theme->build('board_comment', array('p' => $p, 'modifiers' => $modifiers), TRUE, TRUE);
			}
		}
	?>


	</div>
	<hr/>

	<?php endforeach; ?>


</div>