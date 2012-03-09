<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php foreach ($posts as $key => $post) : ?>
<article<?php echo (isset($post['op'])) ? ' id="' . $post['op']->num . '" class="thread doc_id_' . $post['op']->doc_id . '"' : ' class="thread"' ?>>
	<div class="thread_divider"></div>
	<?php if (isset($post['op'])) : $op = $post['op']; ?>
	<?php if ($op->preview) : ?>
		<div class="thread_image_box">
			<a href="<?php echo ($op->image_href) ? $op->image_href : $op->remote_image_href ?>" target="_blank" rel="noreferrer" class="thread_image_link">
				<img src="<?php echo $op->thumbnail_href ?>" <?php echo ($op->preview_w > 0 && $op->preview_h > 0) ? 'width="' . $op->preview_w . '" height="' . $op->preview_h . '" ' : '' ?>class="thread_image<?php echo ($op->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?php echo $op->media_hash ?>" />
			</a>

			<div class="post_file" style="padding-left: 2px">
				<?php echo byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ', ' . $op->media ?>
			</div>
			<div class="post_file_controls">
				<?php if (get_selected_radix()->thumbnails || $this->tank_auth->is_allowed()) : ?>
				<a href="<?php echo site_url(get_selected_radix()->shortname . '/image/' . $op->safe_media_hash) ?>" class="btnr parent">View Same</a>
				<a href="http://google.com/searchbyimage?image_url=<?php echo $op->thumbnail_href ?>" target="_blank" class="btnr parent">Google</a>
				<a href="http://iqdb.org/?url=<?php echo $op->thumbnail_href ?>" target="_blank" class="btnr parent">iqdb</a>
				<a href="http://saucenao.com/search.php?url=<?php echo $op->thumbnail_href ?>" target="_blank" class="btnr parent">SauceNAO</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<header<?php echo (isset($op->report_status) && !is_null($op->report_status)) ? ' class="reported"' : '' ?>>
		<div class="post_data">
			<?php echo ($op->title_processed) ? '<h2 class="post_title">' . $op->title_processed . '</h2>' : '' ?>

			<span class="post_author"><?php echo ($op->email_processed && $op->email_processed != 'noko') ? '<a href="mailto:' . form_prep($op->email_processed) . '">' . $op->name_processed . '</a>' : $op->name_processed ?></span>
			<?php echo ($op->trip_processed) ? '<span class="post_trip">'. $op->trip_processed . '</span>' : '' ?>
			<?php if ($op->capcode != 'N') : ?>
				<?php if ($op->capcode == 'M') : ?>
					<span class="post_level post_level_moderator">## Mod</span>
				<?php endif ?>
				<?php if ($op->capcode == 'G') : ?>
					<span class="post_level post_level_global_moderator">## Global Mod</span>
				<?php endif ?>
				<?php if ($op->capcode == 'A') : ?>
					<span class="post_level post_level_administrator">## Admin</span>
				<?php endif ?>
			<?php endif; ?>

			<span class="time_wrap">
				<time datetime="<?php echo date(DATE_W3C, $op->timestamp) ?>" class="show_time"><?php echo date('D M d H:i:s Y', $op->timestamp) ?></time>
				<time datetime="<?php echo date(DATE_W3C, $op->timestamp-18000) ?>" class="hidden_time" title="4chan DateTime"><?php echo date('D M d H:i:s Y', $op->timestamp-18000) ?></time>
			</span>

			<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) . '#'  . $op->num ?>" data-post="<?php echo $op->num ?>" data-function="highlight">No.</a><a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) . '#q' . $op->num ?>" data-post="<?php echo $op->num ?>" data-function="quote"><?php echo $op->num ?></a>

			<span class="post_controls">
				<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) ?>" class="btnr parent">View</a><a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) . '#reply' ?>" class="btnr parent">Reply</a><?php echo (isset($post['omitted']) && $post['omitted'] > 50) ? '<a href="' . site_url(get_selected_radix()->shortname . '/last50/' . $op->num) . '" class="btnr parent">Last 50</a>' : '' ?><?php echo (get_selected_radix()->archive) ? '<a href="http://boards.4chan.org/' . get_selected_radix()->shortname . '/res/' . $op->num . '" class="btnr parent">Original</a>' : '' ?><a href="<?php echo site_url(get_selected_radix()->shortname . '/report/' . $op->doc_id) ?>" class="btnr parent" data-post="<?php echo $op->doc_id ?>" data-post-id="<?php echo $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="report">Report</a><?php if($this->tank_auth->is_allowed() || !get_selected_radix()->archive) : ?><a href="<?php echo site_url(get_selected_radix()->shortname . '/delete/' . $op->doc_id) ?>" class="btnr parent" data-post="<?php echo $op->doc_id ?>" data-post-id="<?php echo $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="delete">Delete</a><?php endif; ?>
			</span>

			<?php if ($op->deleted == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.(($this->fu_theme) ? $this->fu_theme : 'default').'/images/icons/file-delete-icon.png'; ?>" width="16" height="16" title="This post was deleted from 4chan manually."/></span><?php endif ?>
			<?php if ($op->spoiler == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.(($this->fu_theme) ? $this->fu_theme : 'default').'/images/icons/spoiler-icon.png'; ?>" width="16" height="16" title="This post contains a spoiler image."/></span><?php endif ?>
		</div>

		<div class="backlink_list"<?php echo (isset($op->backlinks)) ? ' style="display:block"' : '' ?>>
			<?php echo _('Quoted by:') ?> <span class="post_backlink" data-post="<?php echo $op->num ?>"><?php echo (isset($op->backlinks)) ? implode(' ', $op->backlinks) : '' ?></span>
		</div>
	</header>

	<div class="text">
		<?php echo $op->comment_processed ?>
	</div>
	<div class="thread_tools_bottom">
		<?php echo (isset($post['omitted']) && $post['omitted'] > 0) ? '<span class="omitted">' . $post['omitted'] . ' posts ' . ((isset($post['images_omitted']) && $post['images_omitted'] > 0) ? 'and ' . $post['images_omitted'] . ' images' : '') . ' omitted.</span>' : '' ?>
	</div>
	<?php endif; ?>

	<aside class="posts">
	<?php
	if (isset($post['posts']))
	{
		$post_counter = 0;
		foreach ($post['posts'] as $p)
		{
			if ($p->preview)
				$post_counter++;

			if ($post_counter == 150)
				$modifiers['lazyload'] = TRUE;

			if ($p->parent == 0)
				$p->parent  = $p->num;

			if (file_exists('content/themes/' . $this->fu_theme . '/views/board_comment.php'))
				include('content/themes/' . $this->fu_theme . '/views/board_comment.php');
			else
				include('content/themes/' . $this->config->item('theme_extends') . '/views/board_comment.php');
		}
	}
	?>
	</aside>

	<?php if (isset($thread_id)) : ?>
		<div class="js_hook_realtimethread"></div>
		<?php echo $template['partials']['post_reply']; ?>
		<div id="backlink" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>
	<?php endif; ?>
</article>
<?php endforeach; ?>

<script type="text/javascript">
	board_shortname = '<?php echo get_selected_radix()->shortname ?>';
	site_url = '<?php echo site_url() ?>';

	<?php if (isset($thread_id)) : ?>
	thread_id = <?php echo $thread_id ?>;
		<?php if (!$is_last50) : ?>
		thread_op_json = <?php echo json_encode($posts[$thread_id]['op']) ?>;
		<?php endif; ?>
		<?php
		$latest_doc_id = (isset($posts[$thread_id]['op'])) ? $posts[$thread_id]['op']->doc_id : 0;
		if (isset($posts[$thread_id]['posts']))
		{
			foreach ($posts[$thread_id]['posts'] as $post)
			{
				if ($latest_doc_id < $post->doc_id)
					$latest_doc_id = $post->doc_id;
			}
		}
		?>
		latest_doc_id = <?php echo $latest_doc_id ?>;
	<?php endif; ?>
</script>
