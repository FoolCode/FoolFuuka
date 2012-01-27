<a name="<?php echo ($p->subnum > 0) ? $p->num . '_' . $p->subnum : $p->num ?>"></a>
<table>
	<tbody>
		<tr>
			<td nowrap class="doubledash">&gt;&gt;</td>
			<td id="<?php echo ($p->subnum > 0) ? $p->num . '_' . $p->subnum : $p->num ?>" class="<?php echo ($p->subnum > 0) ? 'subreply' : 'reply' ?>">
				<input type="checkbox" name="delete[]" value="<?php echo $p->doc_id ?>"/>
				<span class="replytitle"><?php echo $p->title_processed ?></span>
				<span class="commentpostername"><?php echo (($p->email_processed && $p->email_processed != 'noko') ? '<a href="mailto:' . form_prep($p->email_processed) . '">' . $p->name_processed . '</a>' : $p->name_processed) ?></span>
				<?php if ($p->trip_processed) : ?>
					<span class="postertrip"><?php echo $p->trip_processed ?></span>
				<?php endif; ?>
				<?php if ($p->capcode == 'M') : ?>
					<span class="post_level_moderator">## Mod</span>
				<?php endif ?>
				<?php if ($p->capcode == 'G') : ?>
					<span class="post_level_global_moderator">## Global Mod</span>
				<?php endif ?>
				<?php if ($p->capcode == 'A') : ?>
					<span class="post_level_administrator">## Admin</span>
				<?php endif ?>
				<?php echo date('m/d/y(D)H:i', $p->timestamp + 18000) ?>

				<?php if ($p->subnum > 0) : ?>
					<span id="norep<?php echo $p->num . '_' . $p->subnum ?>">
						<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $p->parent) . '#' . $p->num . '_' . $p->subnum ?>" class="quotejs">No.</a><a href="<?php echo ($thread_id == NULL) ? site_url(get_selected_radix()->shortname . '/thread/' . $p->parent) . '#q' . $p->num . '_' . $p->subnum : 'javascript:quote(\'' . $p->num . ',' . $p->subnum . '\')' ?>" class="quotejs"><?php echo $p->num . ',' . $p->subnum ?></a>
					</span>
				<?php else : ?>
					<span id="norep<?php echo $p->num ?>">
						<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $p->parent) . '#' . $p->num ?>" class="quotejs">No.</a><a href="<?php echo ($thread_id == NULL) ? site_url(get_selected_radix()->shortname . '/thread/' . $p->parent) . '#q' . $p->num : 'javascript:quote(\'' . $p->num . '\')' ?>" class="quotejs"><?php echo $p->num ?></a>
					</span>
				<?php endif; ?>

				<?php if ($p->media) : ?>
					<br>
					<span class="filesize">
						File <a href="<?php echo ($p->image_href) ? $p->image_href : $p->remote_image_href ?>" target="_blank"><?php echo ($p->media_filename) ? $p->media_filename : $p->media ?></a><?php echo '-(' . byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ')' ?>
					</span>
					<br>
					<a href="<?php echo ($p->image_href) ? $p->image_href : $p->remote_image_href ?>" target="_blank">
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