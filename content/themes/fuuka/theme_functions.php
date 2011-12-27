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


function build_board_comment($p, $modifiers = array(), $thread_id = NULL) {
	$CI = & get_instance();
	ob_start();
?>
	<table>
		<tbody>
			<tr>
				<td class="doubledash">&gt;&gt;</td>
				<td class="reply" id="p<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">
					<label>
						<?php if ($thread_id != NULL) : ?>
						<input type="checkbox" name="delete[]" value="<?php echo $p->doc_id ?>"/>
						<?php endif; ?>
						<span class="postername"><?php echo $p->name ?></span>
						<?php echo date('D M d H:i:s Y', $p->timestamp) ?>
					</label>
					<?php if($thread_id == NULL) : ?>
					<a class="js" href="<?php echo site_url($CI->fu_board . '/thread/' . $p->parent) . '#p' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.<?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
					<?php else : ?>
					<a class="js" href="<?php echo site_url($CI->fu_board . '/thread/' . $p->parent) . '#p' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">No.</a><a class="js" href="javascript:insert('>><?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>\n')"><?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?></a>
					<?php endif; ?>

					<?php if ($p->deleted == 1) : ?><img class="inline" src="<?php echo site_url().'content/themes/'.(($CI->fu_theme) ? $CI->fu_theme : 'default').'/images/icons/file-delete-icon.png'; ?>" alt="[DELETED]" title="This post was deleted from 4chan manually."/><?php endif ?>
					<?php if ($p->spoiler == 1) : ?><img class="inline" src="<?php echo site_url().'content/themes/'.(($CI->fu_theme) ? $CI->fu_theme : 'default').'/images/icons/spoiler-icon.png'; ?>" alt="[SPOILER]" title="This post contains a spoiler image."/><?php endif ?>
					<?php if ($p->subnum > 0) : ?><img class="inline" src="<?php echo site_url().'content/themes/'.(($CI->fu_theme) ? $CI->fu_theme : 'default').'/images/icons/communicate-icon.png'; ?>" alt="[INTERNAL]" title="This is a ghost post, not coming from 4chan."/><?php endif ?>

					<br/>
					<?php if ($p->media_filename) : ?>
					<span>
						File: <?php echo byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media_filename; ?>
						<?php echo '<!-- ' . substr($p->media_hash, 0, -2) . '-->' ?>
					</span>
					[<a href="<?php echo site_url($CI->fu_board . '/image/' . urlencode(substr($p->media_hash, 0, -2))) ?>">View Same</a>] [<a href="http://iqdb.org/?url=<?php echo $p->thumbnail_href ?>">iqdb</a>] [<a href="http://google.com/searchbyimage?image_url=<?php echo $p->thumbnail_href ?>">Google</a>] [<a href="http://saucenao.com/search.php?url=<?php echo $p->thumbnail_href ?>">SauceNAO</a>]
					<br>
					<a href="<?php echo ($p->image_href)?$p->image_href:$p->remote_image_href ?>" rel="noreferrer">
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
<?php
	$string = ob_get_contents();
	ob_end_clean();
	return $string;
}
