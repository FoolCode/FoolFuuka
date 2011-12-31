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
	<a name="<?php echo ($p->subnum > 0) ? $p->num . '_' . $p->subnum : $p->num ?>"></a>
	<table>
		<tbody>
			<tr>
				<td nowrap class="doubledash">&gt;&gt;</td>
				<td id="<?php echo $p->num ?>" class="reply">
					<span class="replytitle"></span>
					<span class="commentpostername"><?php echo $p->name ?></span>
					<?php echo date('M/d/y(D)H:i', $p->timestamp) ?>

					<?php if ($p->subnum > 0) : ?>
					<span id="norep<?php echo $p->num . '_' . $p->subnum ?>">
						<a href="<?php echo site_url($CI->fu_board . '/thread/' . $p->parent) . '#' . $p->num . '_' . $p->subnum ?>" class="quotejs">No.</a><a href="<?php echo ($thread_id == NULL) ? site_url($CI->fu_board . '/thread/' . $p->parent) . '#' . $p->num . '_' . $p->subnum : 'javascript:quote(\'' . $p->num . ',' . $p->subnum . '\')'?>" class="quotejs"><?php echo $p->num . ',' . $p->subnum ?></a>
					</span>
					<?php else : ?>
					<span id="norep<?php echo $p->num ?>">
						<a href="<?php echo site_url($CI->fu_board . '/thread/' . $p->parent) . '#' . $p->num ?>" class="quotejs">No.</a><a href="<?php echo ($thread_id == NULL) ? site_url($CI->fu_board . '/thread/' . $p->parent) . '#' . $p->num : 'javascript:quote(\'' . $p->num . '\')' ?>" class="quotejs"><?php echo $p->num ?></a>
					</span>
					<?php endif; ?>

					<?php if ($p->media_filename) : ?>
					<br>
					<span class="filesize">
						File <a href="<?php echo ($p->image_href) ? $p->image_href : $p->remote_image_href ?>" rel="noreferrer" target="_blank"><?php echo $p->media_filename ?></a><?php echo '-(' . byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ')' ?>
					</span>
					<br>
					<a href="<?php echo ($p->image_href) ? $p->image_href : $p->remote_image_href ?>" rel="noreferrer" target="_blank">
						<img src="<?php echo $p->thumbnail_href ?>" border="0" align="left" width="<?php echo $p->preview_w ?>" height="<?php echo $p->preview_h ?>" hspace="20" alt="<?php echo byte_format($p->media_size, 0) ?>" md5="<?php echo $p->media_hash ?>"/>
					</a>
					<?php endif; ?>

					<blockquote>
						<?php echo $p->comment_processed ?>
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
