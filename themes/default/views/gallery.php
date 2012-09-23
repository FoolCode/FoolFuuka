<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<div id="thread_o_matic" class="clearfix">
<?php
$separator = 0;
foreach ($board->get_comments() as $k => $p) :
	$separator++;
?>
	<article id="<?= $p->num ?>" class="thread doc_id_<?= $p->doc_id ?>">
		<header>
			<div class="post_data">
				<h2 class="post_title"><?= $p->get_title_processed() ?></h2>
				<span class="post_author"><?= (($p->email && $p->email !== 'noko') ? '<a href="mailto:' . rawurlencode($p->email) . '">' . $p->get_name_processed() . '</a>' : $p->get_name_processed()) ?></span>
				<span class="post_trip"><?= $p->get_trip_processed() ?></span>
				<span class="poster_hash"><?= ($p->get_poster_hash_processed()) ? 'ID:' . $p->get_poster_hash_processed() : '' ?></span>
				<?php if ($p->capcode == 'M') : ?>
					<span class="post_level post_level_moderator">## <?= __('Mod') ?></span>
				<?php endif ?>
				<?php if ($p->capcode == 'A') : ?>
					<span class="post_level post_level_administrator">## <?= __('Admin') ?></span>
				<?php endif ?>
				<?php if ($p->capcode == 'D') : ?>
					<span class="post_level post_level_developer">## <?= __('Developer') ?></span>
				<?php endif ?><br/>
				<time datetime="<?= gmdate(DATE_W3C, $p->timestamp) ?>"><?= gmdate('D M d H:i:s Y', $p->timestamp) ?></time>
				<span class="post_number"><a href="<?= Uri::create($radix->shortname . '/thread/' . $p->num) . '#' . $p->num ?>" data-function="highlight" data-post="<?= $p->num ?>">No.</a><a href="<?= Uri::create($radix->shortname . '/thread/' . $p->num) . '#q' . $p->num ?>" data-function="quote" data-post="<?= $p->num ?>"><?= $p->num ?></a></span>
				<?php if ($p->poster_country !== null) : ?><span class="post_type"><span title="<?= e($p->poster_country_name) ?>" class="flag flag-<?= strtolower($p->poster_country) ?>"></span></span><?php endif; ?>
				<span class="post_controls"><a href="<?= Uri::create($radix->shortname . '/thread/' . $p->num) ?>" class="btnr parent"><?= __('View') ?></a><a href="<?= Uri::create($radix->shortname . '/thread/' . $p->num) . '#reply' ?>" class="btnr parent"><?= __('Reply') ?></a><?= (isset($p->count_all) && $p->count_all > 50) ? '<a href="' . Uri::create($radix->shortname . '/last50/' . $p->num) . '" class="btnr parent">' . __('Last 50') . '</a>' : '' ?><?php if ($radix->archive == 1) : ?><a href="http://boards.4chan.org/<?= $radix->shortname . '/res/' . $p->num ?>" class="btnr parent"><?= __('Original') ?></a><?php endif; ?><a href="<?= Uri::create($radix->shortname . '/report/' . $p->doc_id) ?>" class="btnr parent" data-function="report" data-post="<?= $p->doc_id ?>" data-post-id="<?= $p->num ?>" data-board="<?= htmlspecialchars($p->board->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true"><?= __('Report') ?></a><?php if (Auth::has_access('maccess.mod')) : ?><a href="<?= Uri::create($radix->shortname . '/delete/' . $p->doc_id) ?>" class="btnr parent" data-function="delete" data-post="<?= $p->doc_id ?>" data-post-id="<?= $p->num ?>" data-board="<?= htmlspecialchars($p->board->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true"><?= __('Delete') ?></a><?php endif; ?></span>
			</div>
		</header>
		<?php if ($p->media !== null) : ?>
		<div class="thread_image_box" title="<?= $p->get_comment_processed() ? htmlspecialchars('<strong>'.($p->get_comment_processed()).'</strong>') : '' ?>">
			<?php if ($p->media->get_media_status() === 'banned') : ?>
				<img src="<?= Uri::base() . $this->fallback_asset('images/banned-image.png') ?>" width="150" height="150" />
			<?php elseif ($p->media->get_media_status() !== 'normal') : ?>
				<a href="<?= ($p->media->get_media_link()) ? $p->media->get_media_link() : $p->media->get_remote_media_link() ?>" target="_blank" rel="noreferrer" class="thread_image_link">
					<img src="<?= Uri::base() . $this->fallback_asset('images/missing-image.jpg') ?>" width="150" height="150" />
				</a>
			<?php else: ?>
				<a href="<?= Uri::create($radix->shortname . '/thread/' . $p->num) ?>" rel="noreferrer" target="_blank" class="thread_image_link"<?= ($p->media->get_media_link())?' data-expand="true"':'' ?>>
					<?php if(!Auth::has_access('maccess.mod') && !$radix->transparent_spoiler && $p->spoiler) :?>
					<div class="spoiler_box"><span class="spoiler_box_text"><?= __('Spoiler') ?><span class="spoiler_box_text_help"><?= __('Click to view') ?></span></div>
					<?php else : ?>
					<img src="<?= $p->media->get_thumb_link() ?>" width="<?= $p->media->preview_w ?>" height="<?= $p->media->preview_h ?>" data-width="<?= $p->media->media_w ?>" data-height="<?= $p->media->media_h ?>" data-md5="<?= $p->media->media_hash ?>" class="thread_image<?= ($p->media->spoiler)?' is_spoiler_image':'' ?>" />
					<?php endif; ?>
				</a>
			<?php endif; ?>
			<?php if ($p->media->get_media_status() !== 'banned'  || \Auth::has_access('media.see_banned')) : ?>
			<div class="post_file" style="padding-left: 2px"><?= \Num::format_bytes($p->media->media_size, 0) . ', ' . $p->media->media_w . 'x' . $p->media->media_h . ', ' . $p->media->media_filename ?></div>
				<div class="post_file_controls">
					<a href="<?= ($p->media->get_media_link()) ? $p->media->get_media_link() : $p->media->get_remote_media_link() ?>" class="btnr" target="_blank">Full</a><?php if ($p->media->total > 1) : ?><a href="<?= Uri::create($radix->shortname . '/search/image/' . urlencode(substr($p->media->media_hash, 0, -2))) ?>" class="btnr parent"><?= __('View Same') ?></a><?php endif; ?><a target="_blank" href="http://iqdb.org/?url=<?= $p->media->get_thumb_link() ?>" class="btnr parent">iqdb</a><a target="_blank" href="http://saucenao.com/search.php?url=<?= $p->media->get_thumb_link() ?>" class="btnr parent">SauceNAO</a><a target="_blank" href="http://google.com/searchbyimage?image_url=<?= $p->media->get_thumb_link() ?>" class="btnr parent">Google</a>
				</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<div class="thread_tools_bottom">
			<?php if (isset($p->nreplies)) : ?>
				<?= __('Replies') ?> : <?= $p->nreplies ?> | <?= __('Images') ?>: <?= $p->nimages ?>
			<?php endif; ?>
			<?php if ($p->deleted == 1) : ?><span class="post_type"><img src="<?= Uri::base() . $this->fallback_asset('images/icons/file-delete-icon.png'); ?>" title="<?= htmlspecialchars(__('This post was deleted from 4chan manually')) ?>"/></span><?php endif ?>
			<?php if (isset($p->media) && $p->media->spoiler == 1) : ?><span class="post_type"><img src="<?= Uri::base() . $this->fallback_asset('images/icons/spoiler-icon.png'); ?>" title="<?= htmlspecialchars(__('This post contains a spoiler image')) ?>"/></span><?php endif ?>
		</div>
	</article>
<?php
if ($separator % 4 == 0)
	echo '<div class="clearfix"></div>';
endforeach;
?>
</div>
<article class="thread">
	<div id="backlink" class="thread_o_matic" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>
</article>