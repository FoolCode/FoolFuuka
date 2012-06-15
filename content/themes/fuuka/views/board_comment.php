<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

$selected_radix = isset($p->board)?$p->board:get_selected_radix();
?>

<table>
	<tbody>
		<tr>
			<td class="doubledash">&gt;&gt;</td>
			<td class="<?= ($p->subnum > 0) ? 'subreply' : 'reply' ?>" id="<?= $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">
				<label>
					<input type="checkbox" name="delete[]" value="<?= $p->doc_id ?>"/>
					<?php if (isset($modifiers['post_show_board_name']) &&  $modifiers['post_show_board_name']): ?><span class="post_show_board">/<?= $selected_radix->shortname ?>/</span><?php endif; ?>
					<span class="filetitle"><?= $p->title_processed ?></span>
					<span class="postername<?= ($p->capcode == 'M') ? ' mod' : '' ?><?= ($p->capcode == 'A') ? ' admin' : '' ?>"><?= (($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed) ?></span>
					<span class="postertrip<?= ($p->capcode == 'M') ? ' mod' : '' ?><?= ($p->capcode == 'A') ? ' admin' : '' ?>"><?= $p->trip_processed ?></span>
					<span class="poster_hash"><?php if ($p->poster_hash_processed) : ?>ID:<?= $p->poster_hash_processed ?><?php endif; ?></span>
					<?php if ($p->capcode == 'M') : ?>
						<span class="postername mod">## <?= __('Mod') ?></span>
					<?php endif ?>
					<?php if ($p->capcode == 'A') : ?>
						<span class="postername admin">## <?= __('Admin') ?></span>
					<?php endif ?>
					<?= gmdate('D M d H:i:s Y', $p->original_timestamp) ?>
				</label>
				<?php if (!isset($thread_id)) : ?>
					<a class="js" href="<?= site_url(array($selected_radix->shortname, 'thread', $p->thread_num)) . '#' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.<?= $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
				<?php else : ?>
					<a class="js" href="<?= site_url(array($selected_radix->shortname, 'thread', $p->thread_num)) . '#' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.</a><a class="js" href="javascript:replyQuote('>><?= $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>\n')"><?= $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
				<?php endif; ?>

				<?php if ($p->deleted == 1) : ?><img class="inline" src="<?= site_url() . 'content/themes/' . (($this->theme->get_selected_theme()) ? $this->theme->get_selected_theme() : 'default') . '/images/icons/file-delete-icon.png'; ?>" alt="[DELETED]" title="<?= __('This post was deleted before its lifetime has expired.') ?>"/><?php endif ?>
				<?php if ($p->spoiler == 1) : ?><img class="inline" src="<?= site_url() . 'content/themes/' . (($this->theme->get_selected_theme()) ? $this->theme->get_selected_theme() : 'default') . '/images/icons/spoiler-icon.png'; ?>" alt="[SPOILER]" title="<?= __('The image in this post is marked as spoiler.') ?>"/><?php endif ?>
				<?php if ($p->subnum > 0) : ?><img class="inline" src="<?= site_url() . 'content/themes/' . (($this->theme->get_selected_theme()) ? $this->theme->get_selected_theme() : 'default') . '/images/icons/communicate-icon.png'; ?>" alt="[INTERNAL]" title="<?= __('This post is not an archived reply.') ?>"/><?php endif ?>

				<?php if (isset($modifiers['post_show_view_button'])) : ?>[<a class="btnr" href="<?= site_url(array($selected_radix->shortname, 'thread', $p->thread_num)) . '#p' . $p->num . (($p->subnum) ? '_' . $p->subnum : '') ?>">View</a>]<?php endif; ?>

				<br/>
				<?php if ($p->preview_orig) : ?>
					<span>
						<?= __('File:') . ' ' . byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media_filename_processed; ?>
						<?= '<!-- ' . substr($p->media_hash, 0, -2) . '-->' ?>
					</span>
					<?php if ($p->media_status != 'banned') : ?>
						<?php if (!$selected_radix->hide_thumbnails || $this->auth->is_mod_admin()) : ?>
							[<a href="<?= site_url($selected_radix->shortname . '/search/image/' . $p->safe_media_hash) ?>"><?= __('View Same') ?></a>]
							[<a href="http://google.com/searchbyimage?image_url=<?= $p->thumb_link ?>">Google</a>]
							[<a href="http://iqdb.org/?url=<?= $p->thumb_link ?>">iqdb</a>]
							[<a href="http://saucenao.com/search.php?url=<?= $p->thumb_link ?>">SauceNAO</a>]
						<?php endif; ?>
					<?php endif; ?>
					<br />
					<?php if ($p->media_status != 'available') :?>
						<?php if ($p->media_status == 'banned') : ?>
							<img src="<?= site_url() . $this->theme->fallback_asset('images/banned-image.png') ?>" width="150" height="150" class="thumb"/>
						<?php else : ?>
							<a href="<?= ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" rel="noreferrer">
								<img src="<?= site_url() . $this->theme->fallback_asset('images/missing-image.jpg') ?>" width="150" height="150" class="thumb"/>
							</a>
						<?php endif; ?>
					<?php else: ?>
					<a href="<?= ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" rel="noreferrer">
						<img src="<?= $p->thumb_link ?>" alt="<?= $p->num ?>" width="<?= $p->preview_w ?>" height="<?= $p->preview_h ?>" class="thumb" />
					</a>
					<?php endif; ?>
				<?php endif; ?>
				<div class="quoted-by" style="display: <?= (isset($p->backlinks)) ? 'block' : 'none' ?>">
					<?= __('Quoted By:') ?> <?= (isset($p->backlinks)) ? implode(' ', $p->backlinks) : '' ?>
				</div>
				<blockquote><p><?= $p->comment_processed ?></p></blockquote>
			</td>
		</tr>
	</tbody>
</table>
