<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<?php
$selected_radix = isset($p->board)?$p->board:get_selected_radix();

$num =  $p->num . ( $p->subnum ? '_' . $p->subnum : '' );
$quote_mode = 'thread';
?>

<div class="postContainer replyContainer" id="pc<?= $num ?>">
	<div class="sideArrows" id="sa<?= $num ?>">&gt;&gt;</div>
		<div id="<?= $num ?>" class="post reply">
			<div class="postInfo" id="pi<?= $num ?>">
				<input type="checkbox" name="post[]" value="<?= $p->doc_id ?>" />
				<span class="userInfo">
					<span class="subject"><?= $p->title_processed ?></span>
					<span class="nameBlock<?= (($p->capcode == 'M') ? ' capcodeMod':'') . (($p->capcode == 'A') ? ' capcodeAdmin':'') ?>">
						<span class="name"><?= ($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed ?></span>
						<?php if ($p->trip) : ?><span class="postertrip"><?= $p->trip ?></span><?php endif; ?>
						<?php if (in_array($p->capcode, array('M', 'A'))) : ?>
							<strong class="capcode">## <?= (($p->capcode == 'M') ? 'Mod':'') . (($p->capcode == 'A') ? 'Admin':'') ?></strong>
							<?php if ($p->capcode == 'M') : ?><img src="<?= site_url('content/themes/yotsuba/images/') . 'icon-mod.gif' ?>" alt="This user is a Moderator." title="This user is a Moderator." class="identityIcon" style="float: none!important; margin-left: 0px;"><?php endif; ?>
							<?php if ($p->capcode == 'A') : ?><img src="<?= site_url('content/themes/yotsuba/images/') . 'icon-admin.gif' ?>" alt="This user is an Administrator." title="This user is an Administrator." class="identityIcon" style="float: none!important; margin-left: 0px;"><?php endif; ?>
						<?php endif; ?>
					</span>
					<span class="postNum mobile">
						<a href="<?= site_url(array($selected_radix->shortname, 'thread', $p->thread_num)) ?>#p<?= $num ?>" title="Highlight this post">No.</a><a href="<?= site_url(array($selected_radix->shortname, $quote_mode, $p->thread_num)) ?>#q<?= $num ?>" title="Quote this post"><?= str_replace('_', ',', $num) ?></a>
					</span>
				</span>
				<span class="dateTime"><?= gmdate('D M d H:i:s Y', $p->original_timestamp) ?></span>
				<span class="postNum desktop">
					<a href="<?= site_url(array($selected_radix->shortname, 'thread', $p->thread_num)) ?>#p<?= $num ?>" title="Highlight this post">No.</a><a href="<?= site_url(array($selected_radix->shortname, $quote_mode, $p->thread_num)) ?>#q<?= $num ?>" title="Quote this post"><?= str_replace('_', ',', $num) ?></a>
				</span>
			</div>

			<?php if ($p->preview_orig) : ?>
			<div class="file" id="f<?= $num ?>">
				<div class="fileInfo">
						<span class="fileText">
							<?= __('File:') ?>
							<a href="<?= ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank"><?= $p->media ?></a>-(<?= byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media_filename_processed ?>)
						</span>
				</div>
				<a class="fileThumb" href="<?= ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank">
					<img src="<?= $p->thumb_link ?>" alt="<?= byte_format($p->media_size, 0) ?>" data-md5="<?= $p->media_hash ?>"<?php if ($p->preview_w > 0 && $p->preview_h > 0) : ?> style="height: <?= $p->preview_h ?>px; width: <?= $p->preview_w ?>px;"<?php endif; ?>/>
				</a>
			</div>
			<?php endif; ?>
			<blockquote class="postMessage" id="m<?= $num ?>"><?= $p->comment_processed ?></blockquote>
	</div>
</div>