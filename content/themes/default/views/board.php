<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
if(!isset($modifiers))
	$modifiers = array();
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
					<a href="<?php echo ($op->image_href)?$op->image_href:$op->remote_image_href ?>" rel="noreferrer" target="_blank" class="thread_image_link"<?php echo ($op->image_href)?' data-expand="true"':'' ?>><img src="<?php echo $op->thumbnail_href ?>" <?php if ($op->preview_w > 0 && $op->preview_h > 0) : ?>width="<?php echo $op->preview_w ?>" height="<?php echo $op->preview_h ?>"<?php endif; ?> data-width="<?php echo $op->media_w ?>" data-height="<?php echo $op->media_h ?>" data-md5="<?php echo $op->media_hash ?>" class="thread_image<?php echo ($op->spoiler)?' is_spoiler_image':'' ?>" /></a>
					<div class="post_file" style="padding-left: 2px"><?php echo byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ', ' . $op->media ?></div>
					<div class="post_file_controls">
						<a href="<?php echo site_url($this->fu_board . '/image/' . urlencode(substr($op->media_hash, 0, -2))) ?>" class="btnr parent">View Same</a><a target="_blank" href="http://iqdb.org/?url=<?php echo $op->thumbnail_href ?>" class="btnr parent">iqdb</a><a target="_blank" href="http://saucenao.com/search.php?url=<?php echo $op->thumbnail_href ?>" class="btnr parent">SauceNAO</a><a target="_blank" href="http://google.com/searchbyimage?image_url=<?php echo $op->thumbnail_href ?>" class="btnr parent">Google</a>
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
							<span class="post_controls"><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) ?>" class="btnr parent">View</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#reply' ?>" class="btnr parent">Reply</a><a href="http://boards.4chan.org/<?php echo $this->fu_board . '/res/' . $op->num ?>" class="btnr parent">Original</a><a href="<?php echo site_url($this->fu_board . '/report/' . $op->doc_id) ?>" class="btnr parent" data-function="report" data-post="<?php echo $op->doc_id ?>" data-post-id="<?php echo $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true">Report</a><?php if($this->tank_auth->is_allowed()) : ?><a href="<?php echo site_url($this->fu_board . '/delete/' . $op->doc_id) ?>" class="btnr parent" data-function="delete" data-post="<?php echo $op->doc_id ?>" data-post-id="<?php echo $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true">Delete</a><?php endif; ?></span>
							<?php if ($op->deleted == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.(($this->fu_theme) ? $this->fu_theme : 'default').'/images/icons/file-delete-icon.png'; ?>" title="This post was deleted from 4chan manually."/></span><?php endif ?>
							<?php if ($op->spoiler == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.(($this->fu_theme) ? $this->fu_theme : 'default').'/images/icons/spoiler-icon.png'; ?>" title="This post contains a spoiler image."/></span><?php endif ?>
				</div>
				<div class="backlink_list"><?php echo _('Quoted by:') ?> <span class="post_backlink" data-post="<?php echo $op->num ?>"></span></div>
			</header>
			<div class="text">
				<?php echo $op->comment_processed ?>
			</div>
			<div class="thread_tools_bottom">
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
					<?php echo build_board_comment($p, $modifiers); ?>
				<?php endfor; ?>
			</aside>
			<?php if (isset($thread_id)) : ?>
			<div class="js_hook_realtimethread"></div>
			<?php endif; ?>
		<?php else : ?>
			<?php if (isset($thread_id)) : ?>
			<aside class="posts"></aside>
			<div class="js_hook_realtimethread"></div>
			<?php endif; ?>
		<?php endif; ?>
		<?php echo $template['partials']['post_reply']; ?>
	<div id="backlink" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>
	</article>
<?php endforeach; ?>


<script type="text/javascript">
	site_url = '<?php echo site_url() ?>';
	board_shortname = '<?php echo get_selected_board()->shortname ?>';
	<?php if (isset($thread_id)) : ?>
	thread_doc_id = <?php echo $post['op']->doc_id ?>;
	thread_id = <?php echo $thread_id ?>;
	thread_json = <?php echo json_encode($posts) ?>;

	var getLatestID = function(data)
	{
		var max = data[data.length-1].doc_id;
		for ( index = 0; index < data.length; index++ )
		{
			if ( data[index].doc_id > max )
			{
				max = data[index].doc_id;
			}
		}

		return max;
	}

	if (typeof thread_json[thread_id].posts != "undefined")
	{
		thread_latest_doc_id = getLatestID(thread_json[thread_id].posts);
	}
	else
	{
		thread_json[thread_id].posts = [];
		thread_latest_doc_id = 0;
	}
	<?php endif; ?>
</script>
