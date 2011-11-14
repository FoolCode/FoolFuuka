<?php
if (!defined('BASEPATH'))
exit('No direct script access allowed');

foreach ($posts as $key => $post) : ?>
	<?php if(isset($post['op'])) :
		$op = $post['op'];
	?>
		<?php if ($op->media_filename) : ?>
		<span class="filesize">
			File : <?php echo $op->media_filename . '-(' . byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ')' ?>
		</span>
		<br>
		<a href="" target="_blank">
			<img src="<?php echo $op->thumbnail_href ?>" border="0" align="left" width="<?php echo $op->preview_w ?>" height="<?php echo $op->preview_h ?>" hspace="20" alt="<?php echo byte_format($op->media_size, 0) ?>" md5="<?php echo $op->media_hash ?>"/>
		</a>
		<?php endif; ?>
		
		<a name="0"></a>
		<span class="filetitle"></span>
		<span class="postername"><?php echo $op->name ?></span>
		<span class="postertrip"><?php echo $op->trip ?></span>
		<span class="posttime"><?php echo date('D M d H:i:s Y', $op->timestamp) ?></span>
		<span id="nothread<?php echo $op->num ?>">
			<a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num . '#' . $op->num) ?>" class="quotejs">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#q' . $op->num ?>" class="quotejs"><?php echo $op->num ?></a>
			[<a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) ?>">Reply</a>]
		</span>
		<blockquote><?php echo $op->comment_processed ?></blockquote>
		<span class="omittedposts"><?php echo ((isset($post['omitted']) && $post['omitted'] > 0)?$post['omitted'] . ' posts omitted. Click Reply to view.':'') ?></span>
	<?php endif; ?>

	<?php 
	$post_count = 0;
	if(isset($post['posts']))
	foreach (array_reverse($post['posts']) as $p) : 

		if(isset($posts_per_thread) && $posts_per_thread == $post_count)
		{
			break;
		}
		$post_count++;
	?>
	<table>
		<tbody>
			<tr>
				<td nowrap class="doubledash">&gt;&gt;</td>
				<td id="<?php echo $p->num ?>" class="reply">
					<span class="replytitle"></span>
					<span class="commentpostername"><?php echo $p->name ?></span>
					<?php echo date('D M d H:i:s Y', $p->timestamp) ?>
					<?php if ($p->subnum > 0) : ?>
					<span id="norep<?php echo $p->num ?>">
						<a href="<?php echo site_url($this->fu_board . '/thread/' . $p->parent . '#' . $p->num . '_' . $p->subnum) ?>" class="quotejs">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $p->parent) . '#q' . $p->num . '_' . $p->subnum ?>" class="quotejs"><?php echo $op->num ?></a>
					</span>
					<?php else : ?>
					<span id="norep<?php echo $p->num ?>">
						<a href="<?php echo site_url($this->fu_board . '/thread/' . $p->parent . '#' . $p->num) ?>" class="quotejs">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $p->parent) . '#q' . $p->num ?>" class="quotejs"><?php echo $op->num ?></a>
					</span>
					<?php endif; ?>
					<?php if ($p->media_filename) : ?>
						<br>
						File : <?php echo $p->media_filename . '-(' . byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ')' ?>
						<br>
						<a href="" target="_blank">
							<img src="<?php echo $p->thumbnail_href ?>" border="0" align="left" width="<?php echo $p->preview_w ?>" height="<?php echo $p->preview_h ?>" hspace="20" alt="<?php echo byte_format($p->media_size, 0) ?>" md5="<?php echo $p->media_hash ?>"/>
						</a>
					<?php endif; ?>
					<blockquote><?php echo $p->comment_processed ?></blockquote>
				</td>
			</tr>
		</tbody>
	</table>
	<?php endforeach; ?>
	<br clear="left">
	<hr>
<?php endforeach; ?>