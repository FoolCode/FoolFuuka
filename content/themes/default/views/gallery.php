<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div id="thread_o_matic" class="clearfix">
<?php
$separator = 0;
foreach ($threads as $k => $p) :
	count++;
?>
	<article id="<?php echo $p->num ?>" class="thread doc_id_<?php echo $p->doc_id ?>">
		<header>
			<div class="post_data">
				<h2 class="post_title"><?php echo $p->title_processed ?></h2>
				<span class="post_author"><?php echo (($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed) ?></span>
				<span class="post_trip"><?php echo $p->trip_processed ?></span>
				<?php if ($p->capcode == 'M') : ?>
					<span class="post_level post_level_moderator">## <?php echo __('Mod') ?></span>
				<?php endif ?>
				<?php if ($p->capcode == 'G') : ?>
					<span class="post_level post_level_global_moderator">## <?php echo __('Global Mod') ?></span>
				<?php endif ?>
				<?php if ($p->capcode == 'A') : ?>
					<span class="post_level post_level_administrator">## <?php echo __('Admin') ?></span>
				<?php endif ?><br/>
				<time datetime="<?php echo date(DATE_W3C, $p->timestamp) ?>"><?php echo date('D M d H:i:s Y', $p->timestamp) ?></time>
				<span class="post_number"><a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $p->num) . '#' . $p->num ?>" data-function="highlight" data-post="<?php echo $p->num ?>">No.</a><a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $p->num) . '#q' . $p->num ?>" data-function="quote" data-post="<?php echo $p->num ?>"><?php echo $p->num ?></a></span>
				<span class="post_controls"><a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $p->num) ?>" class="btnr parent"><?php echo __('View') ?></a><a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $p->num) . '#reply' ?>" class="btnr parent"><?php echo __('Reply') ?></a><?php echo (isset($p->count_all) && $p->count_all > 50) ? '<a href="' . site_url(get_selected_radix()->shortname . '/last50/' . $p->num) . '" class="btnr parent">' . __('Last 50') . '</a>' : '' ?><?php if (get_selected_radix()->archive == 1) : ?><a href="http://boards.4chan.org/<?php echo get_selected_radix()->shortname . '/res/' . $p->num ?>" class="btnr parent"><?php echo __('Original') ?></a><?php endif; ?><a href="<?php echo site_url(get_selected_radix()->shortname . '/report/' . $p->doc_id) ?>" class="btnr parent" data-function="report" data-post="<?php echo $p->doc_id ?>" data-post-id="<?php echo $p->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true"><?php echo __('Report') ?></a><?php if($this->tank_auth->is_allowed()) : ?><a href="<?php echo site_url(get_selected_radix()->shortname . '/delete/' . $p->doc_id) ?>" class="btnr parent" data-function="delete" data-post="<?php echo $p->doc_id ?>" data-post-id="<?php echo $p->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true"><?php echo __('Delete') ?></a><?php endif; ?></span>
			</div>
		</header>
		<div class="thread_image_box">
			<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $p->num) ?>" data-backlink="<?php echo $p->num ?>" rel="noreferrer" target="_blank" class="thread_image_link"<?php echo ($p->media_link)?' data-expand="true"':'' ?>><img src="<?php echo $p->thumb_link ?>" <?php if ($p->preview_w > 0 && $p->preview_h > 0) : ?>width="<?php echo $p->preview_w ?>" height="<?php echo $p->preview_h ?>"<?php endif; ?> data-width="<?php echo $p->media_w ?>" data-height="<?php echo $p->media_h ?>" data-md5="<?php echo $p->media_hash ?>" class="thread_image<?php echo ($p->spoiler)?' is_spoiler_image':'' ?>" /></a>
			<div class="post_file" style="padding-left: 2px"><?php echo byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media_filename ?></div>
			<div class="post_file_controls">
				<a href="<?php echo ($p->media_link)?$p->media_link:$p->remote_media_link ?>" class="btnr" target="_blank">Full</a><a href="<?php echo site_url(get_selected_radix()->shortname . '/search/image/' . urlencode(substr($p->media_hash, 0, -2))) ?>" class="btnr parent"><?php echo __('View Same') ?></a><a target="_blank" href="http://iqdb.org/?url=<?php echo $p->thumb_link ?>" class="btnr parent">iqdb</a><a target="_blank" href="http://saucenao.com/search.php?url=<?php echo $p->thumb_link ?>" class="btnr parent">SauceNAO</a><a target="_blank" href="http://google.com/searchbyimage?image_url=<?php echo $p->thumb_link ?>" class="btnr parent">Google</a>
			</div>
		</div>
		<div class="thread_tools_bottom">
			<?php if(isset($p->nreplies)) : ?>
			Replies: <?php echo $p->nreplies ?> | Images: <?php echo $p->nimages ?>
			<?php endif; ?>
			<?php if ($p->deleted == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.get_setting('fs_theme_dir').'/images/icons/file-delete-icon.png'; ?>" title="<?php echo form_prep(__('This post was deleted from 4chan manually')) ?>"/></span><?php endif ?>
			<?php if ($p->spoiler == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.get_setting('fs_theme_dir').'/images/icons/spoiler-icon.png'; ?>" title="<?php echo form_prep(__('This post contains a spoiler image')) ?>"/></span><?php endif ?>
		</div>
		<div id="backlink" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>
	</article>
	<?php
	if($count%4 == 0) echo '<div class="clearfix"></div>';
	endforeach; ?>
</div>
