<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

$selected_radix = isset($p->board)?$p->board:get_selected_radix();
?>

<table>
	<tbody>
		<tr>
			<td class="doubledash">&gt;&gt;</td>
			<td class="<?php echo ($p->subnum > 0) ? 'subreply' : 'reply' ?>" id="p<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">
				<label id="<?php echo $p->num ?>">
					<input type="checkbox" name="delete[]" value="<?php echo $p->doc_id ?>"/>
					<?php if(isset($modifiers['post_show_board_name']) &&  $modifiers['post_show_board_name']): ?><span class="post_show_board">/<?php echo $selected_radix->shortname ?>/</span><?php endif; ?>
					<span class="filetitle"><?php echo $p->title_processed ?></span>
					<span class="postername<?php echo ($p->capcode == 'M' || $p->capcode == 'G') ? ' mod' : '' ?><?php echo ($p->capcode == 'A') ? ' admin' : '' ?>"><?php echo (($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed) ?></span>
					<span class="postertrip<?php echo ($p->capcode == 'M' || $p->capcode == 'G') ? ' mod' : '' ?><?php echo ($p->capcode == 'A') ? ' admin' : '' ?>"><?php echo $p->trip_processed ?></span>
					<?php if ($p->capcode == 'M') : ?>
						<span class="postername mod">## Mod</span>
					<?php endif ?>
					<?php if ($p->capcode == 'G') : ?>
						<span class="postername mod">## Global Mod</span>
					<?php endif ?>
					<?php if ($p->capcode == 'A') : ?>
						<span class="postername admin">## Admin</span>
					<?php endif ?>
					<?php echo date('D M d H:i:s Y', $p->original_timestamp) ?>
				</label>
				<?php if (isset($thread_id) && $thread_id == NULL) : ?>
					<a class="js" href="<?php echo site_url(array($selected_radix->shortname, 'thread', $p->thread_num)) . '#p' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.<?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
				<?php else : ?>
					<a class="js" href="<?php echo site_url(array($selected_radix->shortname, 'thread', $p->thread_num)) . '#p' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.</a><a class="js" href="javascript:insert('>><?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>\n')"><?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
				<?php endif; ?>

				<?php if ($p->deleted == 1) : ?><img class="inline" src="<?php echo site_url() . 'content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/images/icons/file-delete-icon.png'; ?>" alt="[DELETED]" title="This post was deleted before its lifetime has expired."/><?php endif ?>
				<?php if ($p->spoiler == 1) : ?><img class="inline" src="<?php echo site_url() . 'content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/images/icons/spoiler-icon.png'; ?>" alt="[SPOILER]" title="The image in this post is marked as spoiler."/><?php endif ?>
				<?php if ($p->subnum > 0) : ?><img class="inline" src="<?php echo site_url() . 'content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/images/icons/communicate-icon.png'; ?>" alt="[INTERNAL]" title="This post is not an archived reply."/><?php endif ?>

				<?php if (isset($modifiers['post_show_view_button'])) : ?>[<a class="btnr" href="<?php echo site_url(array($selected_radix->shortname, 'thread', $p->thread_num)) . '#p' . $p->num . (($p->subnum) ? '_' . $p->subnum : '') ?>">View</a>]<?php endif; ?>

				<br/>
				<?php if ($p->preview_orig) : ?>
					<span>
						File: <?php echo byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media; ?>
						<?php echo '<!-- ' . substr($p->media_hash, 0, -2) . '-->' ?>
					</span>
					<?php if (!$selected_radix->hide_thumbnails || $this->tank_auth->is_allowed()) : ?>[<a href="<?php echo site_url($selected_radix->shortname . '/image/' . substr(urlsafe_b64encode(urlsafe_b64decode($p->media_hash)), 0, -2)) ?>">View Same</a>] [<a href="http://iqdb.org/?url=<?php echo $p->thumb_link ?>">iqdb</a>] [<a href="http://google.com/searchbyimage?image_url=<?php echo $p->thumb_link ?>">Google</a>] [<a href="http://saucenao.com/search.php?url=<?php echo $p->thumb_link ?>">SauceNAO</a>]<?php endif; ?>
					<br>
					<a href="<?php echo ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" rel="noreferrer">
						<img class="thumb" src="<?php echo $p->thumb_link ?>" alt="<?php echo $p->num ?>" <?php if ($p->preview_w > 0 && $p->preview_h > 0) : ?>width="<?php echo $p->preview_w ?>" height="<?php echo $p->preview_h ?>" <?php endif; ?> />
					</a>
				<?php endif; ?>
				<blockquote>
					<p><?php echo $p->comment_processed ?></p>
				</blockquote>
			</td>
		</tr>
	</tbody>
</table>
