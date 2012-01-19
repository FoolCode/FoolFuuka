<?php
if (!defined('BASEPATH'))
exit('No direct script access allowed');
if(!isset($modifiers))
$modifiers = array();
?>

<div id="thread_o_matic" class="clearfix">
	<?php
	$count = 0;
	foreach ($threads as $k => $op) :
	$count++;
	?>
	<article id="<?php echo $op->num ?>" class="thread doc_id_<?php echo $op->doc_id ?>" class="thread">
		<header>
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
				<?php endif ?><br/>
				<time datetime="<?php echo date(DATE_W3C, $op->timestamp) ?>"><?php echo date('D M d H:i:s Y', $op->timestamp) ?></time>
				<span class="post_number"><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#' . $op->num ?>" data-function="highlight" data-post="<?php echo $op->num ?>">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#q' . $op->num ?>" data-function="quote" data-post="<?php echo $op->num ?>"><?php echo $op->num ?></a></span>
				<span class="post_controls"><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) ?>" class="btnr parent">View</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#reply' ?>" class="btnr parent">Reply</a><?php echo ($op->count_all > 50) ? '<a href="' . site_url($this->fu_board . '/last50/' . $op->num) . '" class="btnr parent">Last 50</a>' : '' ?><?php if (get_selected_board()->archive == 1) : ?><a href="http://boards.4chan.org/<?php echo $this->fu_board . '/res/' . $op->num ?>" class="btnr parent">Original</a><?php endif; ?><a href="<?php echo site_url($this->fu_board . '/report/' . $op->doc_id) ?>" class="btnr parent" data-function="report" data-post="<?php echo $op->doc_id ?>" data-post-id="<?php echo $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true">Report</a><?php if($this->tank_auth->is_allowed()) : ?><a href="<?php echo site_url($this->fu_board . '/delete/' . $op->doc_id) ?>" class="btnr parent" data-function="delete" data-post="<?php echo $op->doc_id ?>" data-post-id="<?php echo $op->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true">Delete</a><?php endif; ?></span>
			</div>
		</header>
		<div class="thread_image_box">
			<a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) ?>" data-backlink="<?php echo $op->num ?>" rel="noreferrer" target="_blank" class="thread_image_link"<?php echo ($op->image_href)?' data-expand="true"':'' ?>><img src="<?php echo $op->thumbnail_href ?>" <?php if ($op->preview_w > 0 && $op->preview_h > 0) : ?>width="<?php echo $op->preview_w ?>" height="<?php echo $op->preview_h ?>"<?php endif; ?> data-width="<?php echo $op->media_w ?>" data-height="<?php echo $op->media_h ?>" data-md5="<?php echo $op->media_hash ?>" class="thread_image<?php echo ($op->spoiler)?' is_spoiler_image':'' ?>" /></a>
			<div class="post_file" style="padding-left: 2px"><?php echo byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ', ' . $op->media ?></div>
			<div class="post_file_controls">
				<a href="<?php echo ($op->image_href)?$op->image_href:$op->remote_image_href ?>" class="btnr" target="_blank">Full</a><a href="<?php echo site_url($this->fu_board . '/image/' . urlencode(substr($op->media_hash, 0, -2))) ?>" class="btnr parent">View Same</a><a target="_blank" href="http://iqdb.org/?url=<?php echo $op->thumbnail_href ?>" class="btnr parent">iqdb</a><a target="_blank" href="http://saucenao.com/search.php?url=<?php echo $op->thumbnail_href ?>" class="btnr parent">SauceNAO</a><a target="_blank" href="http://google.com/searchbyimage?image_url=<?php echo $op->thumbnail_href ?>" class="btnr parent">Google</a>
			</div>
		</div>
		<div class="thread_tools_bottom">
			Replies: <?php echo $op->count_all ?> | Images: <?php echo $op->count_images+1 ?>
			<?php if ($op->deleted == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.get_setting('fs_theme_dir').'/images/icons/file-delete-icon.png'; ?>" title="This post was deleted from 4chan manually."/></span><?php endif ?>
			<?php if ($op->spoiler == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.get_setting('fs_theme_dir').'/images/icons/spoiler-icon.png'; ?>" title="This post contains a spoiler image."/></span><?php endif ?>
		</div>
		<div id="backlink" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>
	</article>
	<?php
	if($count%4 == 0) echo '<div class="clearfix"></div>';
	endforeach; ?>

	<script type="text/javascript">
	site_url = '<?php echo site_url() ?>';
	board_shortname = '<?php echo get_selected_board()->shortname ?>';
	page_function = "gallery";
	thread_json = <?php echo json_encode($threads) ?>;
</script>

</div>