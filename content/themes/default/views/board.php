<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

foreach ($posts as $key => $post) :
	?>

	<article <?php if (isset($post['op'])) : ?>id="<?php echo $post['op']->num ?>" class="thread doc_id_<?php echo $post['op']->doc_id ?>"<?php else: ?> class="thread" <?php endif; ?>>
		<div class="thread_divider"></div>
		<?php
		if (isset($post['op'])) :
			$op = $post['op'];
			?>
			<?php if ($op->media_filename) : ?>
				<div class="thread_image_box">
					<a href="<?php echo $op->remote_image_href ?>" rel="noreferrer" target="_blank" class="thread_image_link"><img src="<?php echo $op->thumbnail_href ?>" width="<?php echo $op->preview_w ?>" height="<?php echo $op->preview_h ?>" data-md5="<?php echo $op->media_hash ?>" class="thread_image" /></a>
					<div class="post_file" style="padding-left: 2px"><?php echo byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ', ' . $op->media ?></div>
					<div class="post_file_controls">
						<a href="<?php echo site_url($this->fu_board . '/image/' . urlencode(substr($op->media_hash, 0, -2))) ?>" class="btnr parent">View Same</a><a target="_blank" href="http://iqdb.org/?url=<?php echo $op->thumbnail_href ?>" class="btnr parent">iqdb</a><a target="_blank" href="http://google.com/searchbyimage?image_url=<?php echo $op->thumbnail_href ?>" class="btnr parent">Google</a>
					</div>
				</div>
			<?php endif; ?>

			<header class="<?php echo ((isset($op->report_status) && !is_null($op->report_status)) ? ' reported' : '') ?>">
				<div class="post_data">
							<h2 class="post_title"><?php echo $op->title_processed ?></h2>
							<span class="post_author"><?php echo (($op->email_processed && $op->email_processed != 'noko') ? '<a href="mailto:' . form_prep($op->email_processed) . '">' . $op->name_processed . '</a>' : $op->name_processed) ?></span>
							<span class="post_trip"><?php echo $op->trip_processed ?></span>
							<?php if ($op->capcode == 'M') : ?>
								<span class="post_level post_level_moderator">## Mod</span>
							<?php endif ?>
							<?php if ($op->capcode == 'G') : ?>
								<span class="post_level post_level_global_moderator">## Global Mod</span>
							<?php endif ?>
							<?php if ($op->capcode == 'A') : ?>
								<span class="post_level post_level_administrator">## Admin</span>
							<?php endif ?>
							<time datetime="<?php echo date(DATE_W3C, $op->timestamp) ?>"><?php echo date('D M d H:i:s Y', $op->timestamp) ?></time>
							<span class="post_number"><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#' . $op->num ?>" data-function="highlight" data-post="<?php echo $op->num ?>">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#q' . $op->num ?>" data-function="quote" data-post="<?php echo $op->num ?>"><?php echo $op->num ?></a></span>
							<span class="post_backlink" data-id="<?php echo $op->num ?>"></span>
							<span class="post_controls"><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) ?>" class="btnr parent">View</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#reply' ?>" class="btnr parent">Reply</a><a href="http://boards.4chan.org/<?php echo $this->fu_board . '/res/' . $op->num ?>" class="btnr parent">Original</a><a href="<?php echo site_url($this->fu_board . '/report/' . $op->doc_id) ?>" class="btnr parent" data-function="report" data-post="<?php echo $op->doc_id ?>" data-post-id="<?php echo $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true">Report</a><a href="<?php echo site_url($this->fu_board . '/delete/' . $op->doc_id) ?>" class="btnr parent" data-function="delete" data-post="<?php echo $op->doc_id ?>" data-post-id="<?php echo $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true">Delete</a></span>
				</div>
			</header>
			<div class="text">
				<?php echo $op->comment_processed ?>
			</div>
			<div class="thread_tools_bottom">
					<?php if ($op->deleted == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.get_setting('fs_theme_dir').'/images/icons/file-delete-icon.png'; ?>" title="This post was deleted from 4chan manually"/></span><?php endif ?>
					<?php echo ((isset($post['omitted']) && $post['omitted'] > 0) ? '<span class="omitted">' . $post['omitted'] . ' posts '.((isset($post['images_omitted']) && $post['images_omitted'] > 0)?'and '.$post['images_omitted'].' images':'').' omitted.</span>' : '') ?>
			</div>
		<?php endif; ?>
		<?php if (isset($post['posts'])) : ?>
			<aside class="posts">

				<?php
				if (isset($posts_per_thread))
				{
					$limit = count($post['posts']) - $posts_per_thread;
					if ($limit < 0)
						$limit = 0;
				}
				else
				{
					$limit = 0;
				}

				for ($i = $limit; $i < count($post['posts']); $i++) :
					$p = $post['posts'][$i];

					if ($p->parent == 0)
						$p->parent = $p->num;
					?>
					<?php echo build_board_comment($p); ?>
				<?php endfor; ?>
			</aside>
			<?php if (isset($thread_id)) : ?>
			<div class="js_hook_realtimethread"></div>
			<?php endif; ?>
		<?php endif; ?>
		<?php echo $template['partials']['post_reply']; ?>
	</article>
<?php endforeach; ?>

<div id="backlink" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>

<script type="text/javascript">
	site_url = '<?php echo site_url() ?>';
	board_shortname = '<?php echo get_selected_board()->shortname ?>';
	<?php if (isset($thread_id)) : ?>
	thread_id = <?php echo $thread_id ?>;
	thread_json = <?php echo json_encode($posts) ?>;
	thread_latest_timestamp = thread_json[thread_id].posts[(thread_json[thread_id].posts.length - 1)].timestamp;
	<?php endif; ?>
</script>
