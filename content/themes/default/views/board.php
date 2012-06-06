<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

foreach ($posts as $key => $post) :
	if (isset($post['op'])) :
		$op = $post['op'];
		$selected_radix = isset($op->board)?$op->board:get_selected_radix();
?>
<article id="<?= $op->num ?>" class="clearfix thread doc_id_<?= $op->doc_id ?> board_<?= $selected_radix->shortname ?>">
	<?php if ($op->preview_orig) : ?>
		<div class="thread_image_box">
			<a href="<?= ($op->media_link) ? $op->media_link : $op->remote_media_link ?>" target="_blank" rel="noreferrer" class="thread_image_link">
				<img src="<?= $op->thumb_link ?>" <?= ($op->preview_w > 0 && $op->preview_h > 0) ? 'width="' . $op->preview_w . '" height="' . $op->preview_h . '" ' : '' ?>class="thread_image<?= ($op->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?= $op->media_hash ?>" />
			</a>

			<div class="post_file" style="padding-left: 2px;<?php if ($op->preview_w > 149) : ?> max-width:<?= $op->preview_w .'px'; endif; ?>;">
				<?= byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ', ' . $op->media_filename ?>
			</div>

			<div class="post_file_controls">
				<?php if (!$selected_radix->hide_thumbnails || $this->tank_auth->is_allowed()) : ?>
				<a href="<?= site_url($selected_radix->shortname . '/search/image/' . $op->safe_media_hash) ?>" class="btnr parent"><?= __('View Same') ?></a><a
					href="http://google.com/searchbyimage?image_url=<?= $op->thumb_link ?>" target="_blank"
					class="btnr parent">Google</a><a
					href="http://iqdb.org/?url=<?= $op->thumb_link ?>" target="_blank"
					class="btnr parent">iqdb</a><a
					href="http://saucenao.com/search.php?url=<?= $op->thumb_link ?>" target="_blank"
					class="btnr parent">SauceNAO</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<header<?= (isset($op->report_status) && !is_null($op->report_status)) ? ' class="reported"' : '' ?>>
		<div class="post_data">
			<h2 class="post_title"><?= $op->title_processed ?></h2>
			<span class="post_author"><?= ($op->email_processed && $op->email_processed != 'noko') ? '<a href="mailto:' . form_prep($op->email_processed) . '">' . $op->name_processed . '</a>' : $op->name_processed ?></span>
			<span class="post_trip"><?= $op->trip_processed ?></span>
			<span class="poster_hash"><?= ($op->poster_hash_processed) ? 'ID:' . $op->poster_hash_processed : '' ?></span>
			<?php if ($op->capcode != 'N') : ?>
				<?php if ($op->capcode == 'M') : ?>
					<span class="post_level post_level_moderator">## <?= __('Mod') ?></span>
					<?php endif ?>
				<?php if ($op->capcode == 'G') : ?>
					<span class="post_level post_level_global_moderator">## <?= __('Global Mod') ?></span>
					<?php endif ?>
				<?php if ($op->capcode == 'A') : ?>
					<span class="post_level post_level_administrator">## <?= __('Admin') ?></span>
				<?php endif ?>
			<?php endif; ?>

			<span class="time_wrap">
				<time datetime="<?= date(DATE_W3C, $op->timestamp) ?>" class="show_time" <?php if ($selected_radix->archive) : ?> title="<?= __('4chan Time') . ': ' .date('D M d H:i:s Y', $op->original_timestamp) ?>"<?php endif; ?>><?= date('D M d H:i:s Y', $op->timestamp) ?></time>
			</span>

			<a href="<?= site_url($selected_radix->shortname . '/thread/' . $op->num) . '#'  . $op->num ?>" data-post="<?= $op->num ?>" data-function="highlight">No.</a><a href="<?= site_url($selected_radix->shortname . ((isset($is_last50) && $is_last50) ? '/last50/' : '/thread/') . $op->num) . '#q' . $op->num ?>" data-post="<?= $op->num ?>" data-function="quote"><?= $op->num ?></a>

			<span class="post_controls">
				<a href="<?= site_url($selected_radix->shortname . '/thread/' . $op->num) ?>" class="btnr parent"><?= __('View') ?></a><a href="<?= site_url($selected_radix->shortname . '/thread/' . $op->num) . '#reply' ?>" class="btnr parent"><?= __('Reply') ?></a><?= (isset($post['omitted']) && $post['omitted'] > 50) ? '<a href="' . site_url($selected_radix->shortname . '/last50/' . $op->num) . '" class="btnr parent">' . __('Last 50') . '</a>' : '' ?><?= ($selected_radix->archive) ? '<a href="http://boards.4chan.org/' . $selected_radix->shortname . '/res/' . $op->num . '" class="btnr parent">' . __('Original') . '</a>' : '' ?><a href="<?= site_url($selected_radix->shortname . '/report/' . $op->doc_id) ?>" class="btnr parent" data-post="<?= $op->doc_id ?>" data-post-id="<?= $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="report"><?= __('Report') ?></a><?php if ($this->tank_auth->is_allowed() || !$selected_radix->archive) : ?><a href="<?= site_url($selected_radix->shortname . '/delete/' . $op->doc_id) ?>" class="btnr parent" data-post="<?= $op->doc_id ?>" data-post-id="<?= $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="delete"><?= __('Delete') ?></a><?php endif; ?>
			</span>

			<?php if ($op->deleted == 1) : ?><span class="post_type"><img src="<?= site_url().'content/themes/'.(($this->theme->get_selected_theme()) ? $this->theme->get_selected_theme() : 'default').'/images/icons/file-delete-icon.png'; ?>" width="16" height="16" title="<?= form_prep(__('This post was deleted from 4chan manually.')) ?>"/></span><?php endif ?>
			<?php if ($op->spoiler == 1) : ?><span class="post_type"><img src="<?= site_url().'content/themes/'.(($this->theme->get_selected_theme()) ? $this->theme->get_selected_theme() : 'default').'/images/icons/spoiler-icon.png'; ?>" width="16" height="16" title="<?= form_prep(__('This post contains a spoiler image.')) ?>"/></span><?php endif ?>

			<div class="backlink_list"<?= (isset($op->backlinks)) ? ' style="display:block"' : '' ?>>
				<?= __('Quoted By:') ?> <span class="post_backlink" data-post="<?= $op->num ?>"><?= (isset($op->backlinks)) ? implode(' ', $op->backlinks) : '' ?></span>
			</div>

			<?php if ($this->tank_auth->is_allowed()) : ?>
				<div class="btn-group" style="clear:both; padding:5px 0 0 0;">
					<button class="btn btn-mini" data-function="activateModeration"><?= __('Mod') ?><?php if ($op->poster_ip) echo ' ' .inet_dtop($op->poster_ip) ?></button>
				</div>
				<div class="btn-group post_mod_controls" style="clear:both; padding:5px 0 0 0;">
					<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-id="<?= $op->doc_id ?>" data-action="remove_post"><?= __('Delete Post') ?></button>
					<?php if ($op->preview_orig) : ?>
						<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-id="<?= $op->doc_id ?>" data-action="remove_image"><?= __('Delete Image') ?></button>
						<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-id="<?= $op->doc_id ?>" data-action="ban_md5"><?= __('Ban Image') ?></button>
					<?php endif; ?>
					<?php if ($op->poster_ip) : ?>
						<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-id="<?= $op->doc_id ?>" data-action="ban_user"><?= __('Ban IP:') . ' ' . inet_dtop($op->poster_ip) ?></button>
						<button class="btn btn-mini" data-function="searchUser" data-board="<?= $selected_radix->shortname ?>" data-board-url="<?= site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?= $op->doc_id ?>" data-poster-ip="<?= inet_dtop($op->poster_ip) ?>"><?= __('Search IP') ?></button>
						<?php if (get_setting('fs_sphinx_global')) : ?>
						<button class="btn btn-mini" data-function="searchUserGlobal" data-board="<?= $selected_radix->shortname ?>" data-board-url="<?= site_url(array('@radix', $selected_radix->shortname)) ?>" data-id="<?= $op->doc_id ?>" data-poster-ip="<?= inet_dtop($op->poster_ip) ?>"><?= __('Search IP Globally') ?></button>
						<?php endif; ?>
					<?php endif; ?>
					<?php if (isset($op->report_status) && !is_null($op->report_status)) : ?>
						<button class="btn btn-mini" data-function="mod" data-board="<?= $selected_radix->shortname ?>" data-id="<?= $op->doc_id ?>" data-action="remove_report"><?= __('Delete Report') ?></button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</header>

	<div class="text<?php if (preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $op->comment_processed)) echo ' shift-jis'; ?>">
		<?= $op->comment_processed ?>
	</div>
	<div class="thread_tools_bottom">
		<?php if (isset($post['omitted']) && $post['omitted'] > 0) : ?>
		<span class="omitted">
			<?= $post['omitted'] . ' ' . _ngettext('post', 'posts', $post['omitted']) ?>
			<?php if (isset($post['images_omitted']) && $post['images_omitted'] > 0) : ?>
				<?= ' ' . __('and') . ' ' . $post['images_omitted'] . ' ' . _ngettext('image', 'images', $post['images_omitted']) ?>
			<?php endif; ?>
			<?= ' ' . _ngettext('omitted', 'omitted', $post['omitted'] + $post['images_omitted']) ?>
		</span>
		<?php endif; ?>
	</div>

	<?php if (isset($op->report_status) && !is_null($op->report_status)) : ?>
	<div class="report_reason"><?= '<strong>' . __('Reported Reason:') . '</strong> ' . $op->report_reason_processed ?>
		<br/>
		<div class="ip_reporter"><?= inet_dtop($op->report_ip_reporter) ?></div>
	</div>
	<?php endif; ?>
<?php elseif (isset($post['posts'])): ?>
<article class="clearfix thread">
<?php endif; ?>

	<aside class="posts">
		<?php 
		if (isset($post['posts'])) :
			$post_counter = 0;
			foreach ($post['posts'] as $p)
			{
				if ($p->preview_orig)
					$post_counter++;

				if ($post_counter == 150)
					$modifiers['lazyload'] = TRUE;

				if ($p->thread_num == 0)
					$p->thread_num = $p->num;

				echo $this->theme->build('board_comment', array('p' => $p, 'modifiers' => $modifiers), TRUE, TRUE);
			}
		endif; ?>
	</aside>

	<?php if (isset($thread_id)) : ?>
	<div class="js_hook_realtimethread"></div>
	<?= ($enabled_tools_reply_box) ? $template['partials']['tools_reply_box'] : '' ?>
	<?php endif; ?>
<?php if (isset($post['op']) || isset($post['posts'])) : ?>
</article>
<?php endif; ?>
<?php endforeach; ?>
<article class="clearfix thread">
	<div id="backlink" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>
</article>
