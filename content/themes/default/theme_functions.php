<?php

/**
 * READER FUNCTIONS
 *
 * This file allows you to add functions and plain procedures that will be
 * loaded every time the public reader loads.
 *
 * If this file doesn't exist, the default theme's reader_functions.php will
 * be loaded.
 *
 * For more information, refer to the support sites linked in your admin panel.
 */


function build_board_comment($p, $modifiers = array()) {
	$CI = & get_instance();
	ob_start();
	?>
	<article class="post doc_id_<?php echo $p->doc_id ?><?php if ($p->subnum > 0) : ?> post_ghost<?php endif; ?><?php echo ((isset($p->report_status) && !is_null($p->report_status)) ? ' reported' : '') ?><?php echo ($p->media_filename?' has_image':'') ?><?php if ($p->media_filename) : ?> clearfix<?php endif; ?>" id="<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">
						<?php if ($p->media_filename) : ?>
							<div class="post_file">
								<span class="post_file_controls">
									<a href="<?php echo site_url($CI->fu_board . '/image/' . urlencode(substr($p->media_hash, 0, -2))) ?>" class="btnr parent">View Same</a><a target="_blank" href="http://iqdb.org/?url=<?php echo $p->thumbnail_href ?>" class="btnr parent">iqdb</a><a target="_blank" href="http://saucenao.com/search.php?url=<?php echo $p->thumbnail_href ?>" class="btnr parent">SauceNAO</a><a target="_blank" href="http://google.com/searchbyimage?image_url=<?php echo $p->thumbnail_href ?>" class="btnr parent">Google</a>
								</span>
								<span class="post_file_filename unshown"><?php echo $p->media ?></span><span class="post_file_filename shown"><?php 
								if(mb_strlen($p->media) > 38)
								{
									$ext_pos = mb_strrpos($p->media, '.');
									echo mb_substr($p->media, 0, 32).' (...)'.mb_substr($p->media, $ext_pos);
								}
								else
								{
									echo $p->media;
								}
								?></span>,
								<span class="post_file_metadata"><?php echo byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h ?></span>
							</div>
							<div class="thread_image_box">
								<a href="<?php echo ($p->image_href)?$p->image_href:$p->remote_image_href ?>" rel="noreferrer" target="_blank" class="thread_image_link"><img src="<?php echo $p->thumbnail_href ?>" width="<?php echo $p->preview_w ?>" height="<?php echo $p->preview_h ?>" data-md5="<?php echo $p->media_hash ?>" class="post_image<?php echo ($p->spoiler)?' is_spoiler_image':'' ?>" /></a>
							</div>
						<?php endif; ?>
						<header>
							<div class="post_data">
								<?php if($p->parent == 0) : ?><span class="post_is_op">Opening post</span><?php endif; ?>
								<h2 class="post_title"><?php echo $p->title_processed ?></h2>
								<span class="post_author"><?php echo (($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed) ?></span> <span class="post_trip"><?php echo $p->trip_processed ?></span>
								<?php if ($p->capcode == 'M') : ?>
									<span class="post_level post_level_moderator">## Mod</span>
								<?php endif ?>
								<?php if ($p->capcode == 'G') : ?>
									<span class="post_level post_level_global_moderator">## Global Mod</span>
								<?php endif ?>
								<?php if ($p->capcode == 'A') : ?>
									<span class="post_level post_level_administrator">## Admin</span>
								<?php endif ?>
								<time datetime="<?php echo date(DATE_W3C, $p->timestamp) ?>"><?php echo date('D M d H:i:s Y', $p->timestamp) ?></time>
								<span class="post_number"><a href="<?php echo site_url($CI->fu_board . '/thread/' . $p->parent) . '#' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>" data-function="highlight" data-post="<?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>">No.</a><a href="<?php echo site_url($CI->fu_board . '/thread/' . $p->parent) . '#q' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>" data-function="quote" data-post="<?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>"><?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a></span>
								<span class="post_controls">
									<?php if(isset($modifiers['post_show_view_button'])) : ?><a class="btnr" href="<?php echo site_url(array($CI->fu_board, 'thread', $p->parent)) . '#' . $p->num . (($p->subnum)?'_'.$p->subnum:'') ?>">View</a><?php endif; ?><a href="<?php echo site_url($CI->fu_board . '/report/' . $p->doc_id) ?>" class="btnr parent" data-function="report" data-post="<?php echo $p->doc_id ?>" data-post-id="<?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true">Report</a><?php if($p->subnum > 0 || $CI->tank_auth->is_allowed()) : ?><a href="<?php echo site_url($CI->fu_board . '/delete/' . $p->doc_id) ?>" class="btnr parent" data-function="delete" data-post="<?php echo $p->doc_id ?>" data-post-id="<?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true">Delete</a><?php endif; ?>
								</span>
								<?php if ($p->deleted == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.(($CI->fu_theme) ? $CI->fu_theme : 'default').'/images/icons/file-delete-icon.png'; ?>" title="This post was deleted from 4chan manually."/></span><?php endif ?>
								<?php if ($p->spoiler == 1) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.(($CI->fu_theme) ? $CI->fu_theme : 'default').'/images/icons/spoiler-icon.png'; ?>" title="This post contains a spoiler image."/></span><?php endif ?>
								<?php if ($p->subnum > 0) : ?><span class="post_type"><img src="<?php echo site_url().'content/themes/'.(($CI->fu_theme) ? $CI->fu_theme : 'default').'/images/icons/communicate-icon.png'; ?>" title="This is a ghost post, not coming from 4chan."/></span><?php endif ?>
							</div>
						</header>
						<div class="backlink_list"><?php echo _('Quoted by:') ?> <span class="post_backlink" data-post="<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>"></span></div>
						<div class="text">
							<?php echo $p->comment_processed ?>
						</div>
					</article><br/>
<?php
 $string = ob_get_contents();
 ob_end_clean();
 return $string;
}
