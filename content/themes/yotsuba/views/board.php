<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (isset($thread_id))
	echo $template['partials']['post_thread'];
?>

<iframe src="" style="height: 0px; width: 0px; visibility: hidden; border-top-style: none; border-right-style: none; border-bottom-style: none; border-left-style: none; border-width: initial; border-color: initial; border-image: initial;">
	<html>
		<head></head>
		<body></body>
	</html>
</iframe>

<?php
echo form_open(get_selected_radix()->shortname .'/sending', array('name' => 'delform'));

foreach ($posts as $key => $post) : ?>
	<?php if(isset($post['op'])) :
		$op = $post['op'];
	?>
		<?php if ($op->preview) : ?>
		<span class="filesize">
			File : <a target="_blank" href="<?php echo ($op->media_link) ? $op->media_link : $op->remote_media_link ?>"><?php echo ($op->media_filename) ? $op->media_filename : $op->media ?></a><?php echo '-(' . byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ')' ?>
		</span>
		<br>
		<a target="_blank" href="<?php echo ($op->media_link) ? $op->media_link : $op->remote_media_link ?>">
			<img border="0" align="left" <?php if ($op->preview_w > 0 && $op->preview_h > 0) : ?>width="<?php echo $op->preview_w ?>" hspace="20" height="<?php echo $op->preview_h ?>" <?php else : ?>hspace="20" <?php endif; ?> alt="<?php echo byte_format($op->media_size, 0) ?>" md5="<?php echo $op->media_hash ?>" src="<?php echo $op->thumb_link ?>"/>
		</a>
		<?php endif; ?>

		<a name="0"></a>
		<input type="checkbox" name="delete[]" value="<?php echo $op->doc_id ?>"/>
		<span class="filetitle"><?php echo $op->title_processed ?></span>
		<span class="postername"><?php echo (($op->email_processed && $op->email_processed != 'noko') ? '<a href="mailto:' . form_prep($op->email_processed) . '">' . $op->name_processed . '</a>' : $op->name_processed) ?></span>
		<span class="postertrip"><?php echo $op->trip_processed ?></span>
		<?php if ($op->capcode == 'M') : ?>
			<span class="post_level_moderator">## Mod</span>
		<?php endif ?>
		<?php if ($op->capcode == 'G') : ?>
			<span class="post_level_global_moderator">## Global Mod</span>
		<?php endif ?>
		<?php if ($op->capcode == 'A') : ?>
			<span class="post_level_administrator">## Admin</span>
		<?php endif ?>
		<span class="posttime"><?php echo date('m/d/y(D)H:i', $op->original_timestamp) ?></span>
		<span id="nothread<?php echo $op->num ?>">
			<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) . '#' . $op->num ?>" class="quotejs">No.</a><a href="<?php echo (!isset($thread_id)) ? site_url(get_selected_radix()->shortname . '/thread/' . $op->num) . '#q' . $op->num : 'javascript:quote(\'' . $op->num . '\')'?>" class="quotejs"><?php echo $op->num ?></a><?php if (!isset($thread_id)) : ?> &nbsp; [<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) ?>">Reply</a>]<?php endif; ?>
		</span>
		<blockquote>
			<?php echo $op->comment_processed ?>
		</blockquote>
		<?php echo ((isset($post['omitted']) && $post['omitted'] > 0) ? '<span class="omittedposts">' . $post['omitted'] . ' posts '.((isset($post['images_omitted']) && $post['images_omitted'] > 0)?' and '.$post['images_omitted'].' image replies ':'') . 'omitted. Click Reply to view.</span>' : '') ?>
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

		foreach ($post['posts'] as $p)
		{

			if ($p->thread_num == 0)
				$p->thread_num = $p->num;

			if(!isset($thread_id))
				$thread_id = NULL;

			if(file_exists('content/themes/' . $this->fu_theme . '/views/board_comment.php'))
				include('content/themes/' . $this->fu_theme . '/views/board_comment.php');
			else
				include('content/themes/' . $this->config->item('theme_extends') . '/views/board_comment.php');
		}
	}
	?>
	<br clear="left">
	<hr>
<?php endforeach; ?>

<div style="text-align: center; text-side:12px;">
	<a href="http://pocky.jlist.com/click/3953/111" target="_blank" onmouseover="window.status='Hentai dating-sim games in English - click to see'; return true;" onmouseout="window.status=''; return true;" title="Hentai dating-sim games in English - click to see">
	<img src="http://pocky.jlist.com/media/3953/111" width="728" height="90" alt="Hentai dating-sim games in English - click to see" border="0">
	</a>
</div>
<hr>

<?php if (isset($thread_id)) : ?>
	<span style="left: 5px; position: absolute;">
		[<a href="<?php echo site_url(get_selected_radix()->shortname) ?>">Return</a>] [<a href="#top">Top</a>]
	</span>
<br>
<?php endif; ?>
<table align="right">
	<tbody>
		<tr>
			<td nowrap="" align="center" class="deletebuttons">
				Delete Post Password <input class="inputtext" type="password" name="pwd" size="8" maxlength="8" value="">
				<input type="submit" name="com_delete" value="Delete"/>
				<input type="submit" name="com_report" value="Report"/>
			</td>
		</tr>
		<tr>
			<td align="right">
				Style [<a href="<?php echo site_url(array('functions', 'theme', 'default')) ?>" onclick="changeTheme('default'); return false;">Default</a> | <a href="<?php echo site_url(array('functions', 'theme', 'fuuka')) ?>" onclick="changeTheme('fuuka'); return false;">Fuuka</a> | <a href="<?php echo site_url(array('functions', 'theme', 'yotsuba')) ?>" onclick="changeTheme('yotsuba'); return false;">Yotsuba</a>]
			</td>
		</tr>
	</tbody>
</table>
<?php if (!isset($thread_id)) : ?>
<table class="pages" align="left" border="1">
	<tbody>
	<tr>
		<?php if ($pagination['current_page'] == 1) : ?>
		<td>Previous</td>
		<?php else : ?>
		<td><input type="submit" value="Previous" onclick="location.href='<?php echo $pagination['base_url'] . ($pagination['current_page'] - 1); ?>';return false;"></td>
		<?php endif; ?>
		<td>
			<?php
			for ($index = 1; $index <= (($pagination['total'] > 15) ? 15 : $pagination['total']); $index++)
			{
				if ($pagination['current_page'] == $index)
					echo '[<b>' . $index  . '</b>] ';
				else
					echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>] ';
			}
			?>
		</td>
		<?php if (15 == $pagination['current_page']) : ?>
		<td>Next</td>
		<?php else : ?>
		<td><input type="submit" value="Next" onclick="location.href='<?php echo $pagination['base_url'] . ($pagination['current_page'] + 1); ?>';return false;"></td>
		<?php endif; ?>
	</tr>
	</tbody>
</table>
<?php endif; ?>

<?php echo form_close() ?>
