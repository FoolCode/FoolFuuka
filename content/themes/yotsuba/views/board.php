<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!isset($modifiers))
		$modifiers = array();
?>

<?php echo $template['partials']['post_reply']; ?>

<form name="threads">

<?php
foreach ($posts as $key => $post) : ?>
	<?php if(isset($post['op'])) :
		$op = $post['op'];
	?>
		<?php if ($op->media_filename) : ?>
		<br>
		<span class="filesize">
			File : <a href="<?php echo ($op->image_href) ? $op->image_href : $op->remote_image_href ?>" rel="noreferrer" target="_blank"><?php echo $op->media_filename ?></a><?php echo '-(' . byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ')' ?>
		</span>
		<br>
		<a href="<?php echo ($op->image_href) ? $op->image_href : $op->remote_image_href ?>" rel="noreferrer" target="_blank">
			<img src="<?php echo $op->thumbnail_href ?>" border="0" align="left" width="<?php echo $op->preview_w ?>" height="<?php echo $op->preview_h ?>" hspace="20" alt="<?php echo byte_format($op->media_size, 0) ?>" md5="<?php echo $op->media_hash ?>"/>
		</a>
		<?php endif; ?>

		<a name="0"></a>

		<input type="checkbox" name="delete[]" value="<?php echo $op->doc_id ?>"/>
		<span class="filetitle"></span>
		<span class="postername"><?php echo $op->name ?></span>
		<span class="postertrip"><?php echo $op->trip ?></span>
		<span class="posttime"><?php echo date('m/d/y(D)H:i', $op->timestamp + 18000) ?></span>
		<span id="nothread<?php echo $op->num ?>">
			<a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#' . $op->num ?>" class="quotejs">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) . '#q' . $op->num ?>" class="quotejs"><?php echo $op->num ?></a> &nbsp; [<a href="<?php echo site_url($this->fu_board . '/thread/' . $op->num) ?>">Reply</a>]
		</span>
		<blockquote>
			<?php echo $op->comment_processed ?>
		</blockquote>
		<?php echo ((isset($post['omitted']) && $post['omitted'] > 0) ? '<span class="omitted">' . $post['omitted'] . ' posts '.((isset($post['images_omitted']) && $post['images_omitted'] > 0)?'and '.$post['images_omitted'].' images':'').' omitted. Click Reply to view.</span>' : '') ?>
	<?php endif; ?>

	<?php
	if (isset($post['posts']))
	{
		if (isset($posts_per_thread))
		{
			$limit = count($post['posts']) - $posts_per_thread;
			if ($limit < 0)
				$limit = 0;
		}
		else
		{
			$limit = 0;
		}

		for ($i = $limit; $i < count($post['posts']); $i++)
		{
			$p = $post['posts'][$i];

			if ($p->parent == 0)
				$p->parent = $p->num;

			if (isset($thread_id))
			{
				echo build_board_comment($p, $modifiers, $thread_id);
			}
			else
			{
				echo build_board_comment($p, $modifiers);
			}
		}
	}
	?>
	<br clear="left">
	<hr>
<?php endforeach; ?>
</form>
