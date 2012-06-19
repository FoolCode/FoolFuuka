<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

$selected_radix = isset($p->board)?$p->board:get_selected_radix();

$num =  $p->num . ( $p->subnum ? '_' . $p->subnum : '' );
$quote_mode = (isset($is_last50) && $is_last50) ? 'last50' : 'thread';
?>

<article class="post doc_id_<?= $p->doc_id ?>
	<?php if ($p->subnum > 0) : ?> post_ghost<?php endif; ?>
	<?php if ($p->thread_num == $p->num) : ?> post_is_op<?php endif; ?>
	<?php if (isset($p->report_status) && !is_null($p->report_status)) : ?> reported<?php endif; ?>
	<?php if ($p->media) : ?> has_image clearfix<?php endif; ?>" id="<?= $num ?>">

	<?php if ($p->preview_orig) : ?>
	<div class="post_file">

		<span class="post_file_controls">
		<?php if ($p->media_status != 'banned') : ?>
			<?php if (!$selected_radix->hide_thumbnails || $this->auth->is_mod_admin()) : ?>
			<a href="<?= site_url('@radix/' . $selected_radix->shortname . '/search/image/' . $p->safe_media_hash) ?>" class="btnr parent"><?= __('View Same') ?></a><a
				href="http://google.com/searchbyimage?image_url=<?= $p->thumb_link ?>" target="_blank" class="btnr parent">Google</a><a
				href="http://iqdb.org/?url=<?= $p->thumb_link ?>" target="_blank" class="btnr parent">iqdb</a><a
				href="http://saucenao.com/search.php?url=<?= $p->thumb_link ?>" target="_blank" class="btnr parent">SauceNAO</a>
			<?php endif; ?>
		<?php endif ?>
		</span>

		<?php if (mb_strlen($p->media_filename_processed) > 38) : ?>
			<span class="post_file_filename" rel="tooltip" title="<?= form_prep($p->media_filename) ?>">
				<?= mb_substr($p->media_filename_processed, 0, 32) . ' (...)' . mb_substr($p->media_filename_processed, mb_strrpos($p->media_filename_processed, '.')) . ', ' ?>
			</span>
		<?php else: ?>
			<?= $p->media_filename_processed . ', ' ?>
		<?php endif; ?>

		<span class="post_file_metadata">
			<?= byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h ?>
		</span>
	</div>

	<div class="thread_image_box">
		<?php if ($p->media_status != 'available') :?>
			<?php if ($p->media_status == 'banned') : ?>
				<img src="<?= site_url() . $this->theme->fallback_asset('images/banned-image.png') ?>" width="150" height="150" />
			<?php else : ?>
				<a href="<?= ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank" rel="noreferrer" class="thread_image_link">
					<img src="<?= site_url() . $this->theme->fallback_asset('images/missing-image.jpg') ?>" width="150" height="150" />
				</a>
			<?php endif; ?>
		<?php else: ?>
		<a href="<?= ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank" rel="noreferrer" class="thread_image_link">
			<?php if(!$this->auth->is_mod_admin() && !$selected_radix->transparent_spoiler && $p->spoiler) :?>
			<div class="spoiler_box"><span class="spoiler_box_text"><?= __('Spoiler') ?><span class="spoiler_box_text_help"><?= __('Click to view') ?></span></div>
			<?php elseif (isset($modifiers['lazyload']) && $modifiers['lazyload'] == TRUE) : ?>
			<img src="<?= site_url('content/themes/default/images/transparent_pixel.png') ?>" data-original="<?= $p->thumb_link ?>" width="<?= $p->preview_w ?>" height="<?= $p->preview_h ?>" class="lazyload post_image<?= ($p->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?= $p->media_hash ?>" />
			<noscript>
				<a href="<?= ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank" rel="noreferrer" class="thread_image_link">
					<img src="<?= $p->thumb_link ?>" style="margin-left: -<?= $p->preview_w ?>px" <?= ($p->preview_w > 0 && $p->preview_h > 0) ? 'width="' . $p->preview_w . '" height="' . $p->preview_h . '" ' : '' ?>class="lazyload post_image<?= ($p->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?= $p->media_hash ?>" />
				</a>
			</noscript>
			<?php else : ?>
			<img src="<?= $p->thumb_link ?>" <?= ($p->preview_w > 0 && $p->preview_h > 0) ? 'width="' . $p->preview_w . '" height="' . $p->preview_h . '" ' : '' ?>class="lazyload post_image<?= ($p->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?= $p->media_hash ?>" />
			<?php endif; ?>
		</a>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<header>
		<div class="post_data">
			<?php if (isset($modifiers['post_show_board_name']) &&  $modifiers['post_show_board_name']): ?>
			<span class="post_show_board">/<?= $selected_radix->shortname ?>/</span>
			<?php endif; ?>

			<h2 class="post_title"><?= $p->title_processed ?></h2>
			<span class="post_author"><?= ($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed ?></span>
			<span class="post_trip"><?= $p->trip_processed ?></span>
			<span class="poster_hash"><?= ($p->poster_hash_processed) ? 'ID:' . $p->poster_hash_processed : '' ?></span>
			<?php if ($p->capcode != 'N') : ?>
				<?php if ($p->capcode == 'M') : ?>
					<span class="post_level post_level_moderator">## <?= __('Mod') ?></span>
					<?php endif ?>
				<?php if ($p->capcode == 'A') : ?>
					<span class="post_level post_level_administrator">## <?= __('Admin') ?></span>
				<?php endif ?>
			<?php endif; ?>

			<span class="time_wrap">
				<time datetime="<?= gmdate(DATE_W3C, $p->timestamp) ?>" <?php if ($selected_radix->archive) : ?> title="<?= __('4chan Time') . ': ' . gmdate('D M d H:i:s Y', $p->original_timestamp) ?>"<?php endif; ?>><?= gmdate('D M d H:i:s Y', $p->timestamp) ?></time>
			</span>

			<a href="<?= site_url(array('@radix',  $selected_radix->shortname, 'thread', $p->thread_num)) . '#'  . $num ?>" data-post="<?= $num ?>" data-function="highlight">No.</a><a href="<?= site_url(array('@radix',  $selected_radix->shortname, $quote_mode, $p->thread_num)) . '#q' . $num ?>" data-post="<?= str_replace('_', ',', $num) ?>" data-function="quote"><?= str_replace('_', ',', $num) ?></a>

			<?php if ($p->subnum > 0)   : ?><span class="post_type"><i class="icon-comment-alt" title="<?= form_prep(__('This post was made in the archive.')) ?>"></i></span><?php endif ?>
			<?php if ($p->spoiler == 1) : ?><span class="post_type"><i class="icon-eye-close" title="<?= form_prep(__('This post contains a spoiler image.')) ?>"></i></span><?php endif ?>
			<?php if ($p->deleted == 1) : ?><span class="post_type"><i class="icon-trash" title="<?= form_prep(__('This post was deleted from 4chan manually.')) ?>"></i></span><?php endif ?>

			<span class="post_controls">
				<?php if (isset($modifiers['post_show_view_button'])) : ?><a href="<?= site_url('@radix/' . $selected_radix->shortname . '/thread/' . $p->thread_num) . '#' . $num ?>" class="btnr parent"><?= __('View') ?></a><?php endif; ?><a href="<?= site_url('@radix/' . $selected_radix->shortname . '/report/' . $p->doc_id) ?>" class="btnr parent" data-post="<?= $p->doc_id ?>" data-post-id="<?= $num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="report"><?= __('Report') ?></a><?php if ($p->subnum > 0 || $this->auth->is_mod_admin() || !$selected_radix->archive) : ?><a href="<?= site_url('@radix/' . $selected_radix->shortname . '/delete/' . $p->doc_id) ?>" class="btnr parent" data-post="<?= $p->doc_id ?>" data-post-id="<?= $num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="delete"><?= __('Delete') ?></a><?php endif; ?>
			</span>
		</div>
	</header>

	<div class="backlink_list"<?= (isset($p->backlinks)) ? ' style="display:block"' : '' ?>>
		<?= __('Quoted By:') ?> <span class="post_backlink" data-post="<?= $p->num ?>"><?= (isset($p->backlinks)) ? implode(' ', $p->backlinks) : '' ?></span>
	</div>

	<div class="text<?php if (preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $p->comment_processed)) echo ' shift-jis'; ?>">
		<?= $p->comment_processed ?>
	</div>

	<?php if ($this->auth->is_mod_admin()) : ?>
	<div class="btn-group" style="clear:both; padding:5px 0 0 0;">
		<button class="btn btn-mini" data-function="activateModeration"><?= __('Mod') ?><?php if ($p->poster_ip) echo ' ' .inet_dtop($p->poster_ip) ?></button>
	</div>
	<div class="btn-group post_mod_controls" style="clear:both; padding:5px 0 0 5px;">
		<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-board-url="<?= site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?= $p->doc_id ?>" data-action="remove_post"><?= __('Delete Post') ?></button>
		<?php if ($p->preview_orig) : ?>
			<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-board-url="<?= site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?= $p->doc_id ?>" data-action="remove_image"><?= __('Delete Image') ?></button>
			<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-board-url="<?= site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?= $p->doc_id ?>" data-action="ban_md5"><?= __('Ban Image') ?></button>
		<?php endif; ?>
		<?php if ($p->poster_ip) : ?>
			<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-board-url="<?= site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?= $p->doc_id ?>" data-action="ban_user"><?= __('Ban IP:') . ' ' . inet_dtop($p->poster_ip) ?></button>
			<button class="btn btn-mini" data-function="searchUser" data-board="<?= $selected_radix->shortname ?>" data-board-url="<?= site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?= $p->doc_id ?>" data-poster-ip="<?= inet_dtop($p->poster_ip) ?>"><?= __('Search IP') ?></button>
			<?php if (get_setting('fs_sphinx_global')) : ?>
			<button class="btn btn-mini" data-function="searchUserGlobal" data-board="<?= $selected_radix->shortname ?>" data-board-url="<?= site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?= $p->doc_id ?>" data-poster-ip="<?= inet_dtop($p->poster_ip) ?>"><?= __('Search IP Globally') ?></button>
			<?php endif; ?>
		<?php endif; ?>
		<?php if (isset($p->report_status) && !is_null($p->report_status)) : ?>
			<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-board-url="<?= site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?= $p->doc_id ?>" data-action="remove_report"><?= __('Delete Report') ?></button>
		<?php endif; ?>
	</div>

	<?php if (isset($p->report_status) && !is_null($p->report_status)) : ?>
	<div class="report_reason"><?= '<strong>' . __('Reported Reason:') . '</strong> ' . $p->report_reason_processed ?>
		<br/>
		<div class="ip_reporter"><?= inet_dtop($p->report_ip_reporter) ?></div>
	</div>
	<?php endif; ?>
	<?php endif; ?>
</article>
<br/>
