<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>


<table>
	<tbody>
		<tr>
			<td class="doubledash">&gt;&gt;</td>
			<td class="<?php echo ($p->subnum > 0) ? 'subreply' : 'reply' ?>" id="p<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">
				<label>
					<input type="checkbox" name="delete[]" value="<?php echo $p->doc_id ?>"/>
					<span class="filetitle"><?php echo $p->title_processed ?></span>
					<span class="postername"><?php echo (($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed) ?></span>
					<span class="postertrip"><?php echo $p->trip_processed ?></span>
					<?php echo date('D M d H:i:s Y', $p->timestamp + 18000) ?>
				</label>
				<?php if ($thread_id == NULL) : ?>
					<a class="js" href="<?php echo site_url(array($this->fu_board, 'thread', $p->parent)) . '#p' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.<?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
				<?php else : ?>
					<a class="js" href="<?php echo site_url(array($this->fu_board, 'thread', $p->parent)) . '#p' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.</a><a class="js" href="javascript:insert('>><?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>\n')"><?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
				<?php endif; ?>

				<?php if ($p->deleted == 1) : ?><img class="inline" src="<?php echo site_url() . 'content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/images/icons/file-delete-icon.png'; ?>" alt="[DELETED]" title="This post was deleted from 4chan manually."/><?php endif ?>
				<?php if ($p->spoiler == 1) : ?><img class="inline" src="<?php echo site_url() . 'content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/images/icons/spoiler-icon.png'; ?>" alt="[SPOILER]" title="This post contains a spoiler image."/><?php endif ?>
				<?php if ($p->subnum > 0) : ?><img class="inline" src="<?php echo site_url() . 'content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/images/icons/communicate-icon.png'; ?>" alt="[INTERNAL]" title="This is a ghost post, not coming from 4chan."/><?php endif ?>

				<?php if (isset($modifiers['post_show_view_button'])) : ?>[<a class="btnr" href="<?php echo site_url(array($this->fu_board, 'thread', $p->parent)) . '#p' . $p->num . (($p->subnum) ? '_' . $p->subnum : '') ?>">View</a>]<?php endif; ?>

				<br/>
				<?php if ($p->media_filename) : ?>
					<span>
						File: <?php echo byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media; ?>
						<?php echo '<!-- ' . substr($p->media_hash, 0, -2) . '-->' ?>
					</span>
					[<a href="<?php echo site_url($this->fu_board . '/image/' . urlencode(substr($p->media_hash, 0, -2))) ?>">View Same</a>] [<a href="http://iqdb.org/?url=<?php echo $p->thumbnail_href ?>">iqdb</a>] [<a href="http://google.com/searchbyimage?image_url=<?php echo $p->thumbnail_href ?>">Google</a>] [<a href="http://saucenao.com/search.php?url=<?php echo $p->thumbnail_href ?>">SauceNAO</a>]
					<br>
					<a href="<?php echo ($p->image_href) ? $p->image_href : $p->remote_image_href ?>" rel="noreferrer">
						<img class="thumb" src="<?php echo $p->thumbnail_href ?>" alt="<?php echo $p->num ?>" width="<?php echo $p->preview_w ?>" height="<?php echo $p->preview_h ?>" />
					</a>
				<?php endif; ?>
				<blockquote>
					<p><?php echo $p->comment_processed ?></p>
				</blockquote>
			</td>
		</tr>
	</tbody>
</table>