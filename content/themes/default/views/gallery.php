<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div id="thread_o_matic" class="clearfix">
<?php
$separator = 0;
foreach ($threads as $k => $p) :
	$separator++;
?>
	<article id="<?= $p->num ?>" class="thread doc_id_<?= $p->doc_id ?>">
		<header>
			<div class="post_data">
				<h2 class="post_title"><?= $p->title_processed ?></h2>
				<span class="post_author"><?= (($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed) ?></span>
				<span class="post_trip"><?= $p->trip_processed ?></span>
				<span class="poster_hash"><?= ($p->poster_hash_processed) ? 'ID:' . $p->poster_hash_processed : '' ?></span>
				<?php if ($p->capcode == 'M') : ?>
					<span class="post_level post_level_moderator">## <?= __('Mod') ?></span>
				<?php endif ?>
				<?php if ($p->capcode == 'A') : ?>
					<span class="post_level post_level_administrator">## <?= __('Admin') ?></span>
				<?php endif ?><br/>
				<time datetime="<?= gmdate(DATE_W3C, $p->timestamp) ?>"><?= gmdate('D M d H:i:s Y', $p->timestamp) ?></time>
				<span class="post_number"><a href="<?= site_url(get_selected_radix()->shortname . '/thread/' . $p->num) . '#' . $p->num ?>" data-function="highlight" data-post="<?= $p->num ?>">No.</a><a href="<?= site_url(get_selected_radix()->shortname . '/thread/' . $p->num) . '#q' . $p->num ?>" data-function="quote" data-post="<?= $p->num ?>"><?= $p->num ?></a></span>
				<span class="post_controls"><a href="<?= site_url(get_selected_radix()->shortname . '/thread/' . $p->num) ?>" class="btnr parent"><?= __('View') ?></a><a href="<?= site_url(get_selected_radix()->shortname . '/thread/' . $p->num) . '#reply' ?>" class="btnr parent"><?= __('Reply') ?></a><?= (isset($p->count_all) && $p->count_all > 50) ? '<a href="' . site_url(get_selected_radix()->shortname . '/last50/' . $p->num) . '" class="btnr parent">' . __('Last 50') . '</a>' : '' ?><?php if (get_selected_radix()->archive == 1) : ?><a href="http://boards.4chan.org/<?= get_selected_radix()->shortname . '/res/' . $p->num ?>" class="btnr parent"><?= __('Original') ?></a><?php endif; ?><a href="<?= site_url(get_selected_radix()->shortname . '/report/' . $p->doc_id) ?>" class="btnr parent" data-function="report" data-post="<?= $p->doc_id ?>" data-post-id="<?= $p->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true"><?= __('Report') ?></a><?php if ($this->auth->is_mod_admin()) : ?><a href="<?= site_url(get_selected_radix()->shortname . '/delete/' . $p->doc_id) ?>" class="btnr parent" data-function="delete" data-post="<?= $p->doc_id ?>" data-post-id="<?= $p->num ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true"><?= __('Delete') ?></a><?php endif; ?></span>
			</div>
		</header>
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
			<a href="<?= site_url(get_selected_radix()->shortname . '/thread/' . $p->num) ?>" data-backlink="<?= $p->num ?>" rel="noreferrer" target="_blank" class="thread_image_link"<?= ($p->media_link)?' data-expand="true"':'' ?>>
				<?php if(!$this->auth->is_mod_admin() && !get_selected_radix()->transparent_spoiler && $p->spoiler) :?>
				<div class="spoiler_box"><span class="spoiler_box_text"><?= __('Spoiler') ?><span class="spoiler_box_text_help"><?= __('Click to view') ?></span></div>
				<?php else : ?>
				<img src="<?= $p->thumb_link ?>" width="<?= $p->preview_w ?>" height="<?= $p->preview_h ?>" data-width="<?= $p->media_w ?>" data-height="<?= $p->media_h ?>" data-md5="<?= $p->media_hash ?>" class="thread_image<?= ($p->spoiler)?' is_spoiler_image':'' ?>" />
				<?php endif; ?>
			</a>
			<?php endif; ?>
			<div class="post_file" style="padding-left: 2px"><?= byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media_filename ?></div>
			
			<?php if ($p->media_status == 'banned') : ?>
				<div class="post_file_controls">
					<a href="<?= ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" class="btnr" target="_blank">Full</a><a href="<?= site_url(get_selected_radix()->shortname . '/search/image/' . urlencode(substr($p->media_hash, 0, -2))) ?>" class="btnr parent"><?= __('View Same') ?></a><a target="_blank" href="http://iqdb.org/?url=<?= $p->thumb_link ?>" class="btnr parent">iqdb</a><a target="_blank" href="http://saucenao.com/search.php?url=<?= $p->thumb_link ?>" class="btnr parent">SauceNAO</a><a target="_blank" href="http://google.com/searchbyimage?image_url=<?= $p->thumb_link ?>" class="btnr parent">Google</a>
				</div>
			<?php endif; ?>
		</div>
		<div class="thread_tools_bottom">
			<?php if (isset($p->nreplies)) : ?>
				<?= __('Replies') ?> : <?= $p->nreplies ?> | <?= __('Images') ?>: <?= $p->nimages ?>
			<?php endif; ?>
			<?php if ($p->deleted == 1) : ?><span class="post_type"><img src="<?= site_url() . $this->theme->fallback_asset('images/icons/file-delete-icon.png'); ?>" title="<?= form_prep(__('This post was deleted from 4chan manually')) ?>"/></span><?php endif ?>
			<?php if ($p->spoiler == 1) : ?><span class="post_type"><img src="<?= site_url() . $this->theme->fallback_asset('images/icons/spoiler-icon.png'); ?>" title="<?= form_prep(__('This post contains a spoiler image')) ?>"/></span><?php endif ?>
		</div>
	</article>
<?php
if ($separator%4 == 0)
	echo '<div class="clearfix"></div>';
endforeach;
?>
</div>
<article class="thread">
	<div id="backlink" class="thread_o_matic" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>
</article>