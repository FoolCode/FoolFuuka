<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!isset($modifiers))
		$modifiers = array();
?>

<?php
if (isset($thread_id))
	echo $template['partials']['post_reply'];
?>

<iframe src="" style="height: 0px; width: 0px; visibility: hidden; border-top-style: none; border-right-style: none; border-bottom-style: none; border-left-style: none; border-width: initial; border-color: initial; border-image: initial;">
	<html>
		<head></head>
		<body></body>
	</html>
</iframe>

<?php echo form_open(get_selected_radix()->shortname .'/sending', array('name' => 'delform')) ?>
<?php
foreach ($posts as $key => $post) : ?>
	<?php if(isset($post['op'])) :
		$op = $post['op'];
	?>
		<?php if ($op->media) : ?>
		<span class="filesize">
			File : <a href="<?php echo ($op->image_href) ? $op->image_href : $op->remote_image_href ?>" target="_blank"><?php echo ($op->media_filename) ? $op->media_filename : $op->media ?></a><?php echo '-(' . byte_format($op->media_size, 0) . ', ' . $op->media_w . 'x' . $op->media_h . ')' ?>
		</span>
		<br>
		<a href="<?php echo ($op->image_href) ? $op->image_href : $op->remote_image_href ?>" target="_blank">
			<img src="<?php echo $op->thumbnail_href ?>" border="0" align="left" <?php if ($op->preview_w > 0 && $op->preview_h > 0) : ?> width="<?php echo $op->preview_w ?>" height="<?php echo $op->preview_h ?>"<?php endif; ?> hspace="20" alt="<?php echo byte_format($op->media_size, 0) ?>" md5="<?php echo $op->media_hash ?>"/>
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
		<span class="posttime"><?php echo date('m/d/y(D)H:i', $op->timestamp + 18000) ?></span>
		<span id="nothread<?php echo $op->num ?>">
			<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) . '#' . $op->num ?>" class="quotejs">No.</a><a href="<?php echo (!isset($thread_id)) ? site_url(get_selected_radix()->shortname . '/thread/' . $op->num) . '#q' . $op->num : 'javascript:quote(\'' . $op->num . '\')'?>" class="quotejs"><?php echo $op->num ?></a> &nbsp; [<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) ?>">Reply</a>]
		</span>
		<blockquote>
			<?php echo $op->comment_processed ?>
		</blockquote>
		<?php echo ((isset($post['omitted']) && $post['omitted'] > 0) ? '<span class="omittedposts">' . $post['omitted'] . ' posts '.((isset($post['images_omitted']) && $post['images_omitted'] > 0)?' and '.$post['images_omitted'].' images replies ':'') . 'omitted. Click Reply to view.</span>' : '') ?>
	<?php endif; ?>

	<?php
	if (isset($post['posts']))
	{

		foreach ($post['posts'] as $p) :

			if ($p->parent == 0)
				$p->parent = $p->num;

			if(!isset($thread_id))
				$thread_id = NULL;

			if(file_exists('content/themes/' . $this->fu_theme . '/views/board_comment.php'))
				include('content/themes/' . $this->fu_theme . '/views/board_comment.php');
			else
				include('content/themes/' . $this->config->item('theme_extends') . '/views/board_comment.php');


		endforeach;

	}
	?>
	<br clear="left">
	<hr>
<?php endforeach; ?>

<table align="right">
	<tbody>
		<tr>
			<td nowrap="" align="center" class="deletebuttons">
				Delete Password <input class="inputtext" type="password" name="pwd" size="8" maxlength="8" value="">
				<input type="submit" name="com_delete" value="Delete"/>
				<input type="submit" name="com_report" value="Report"/>
			</td>
		</tr>
		<tr>
			<td align="right">
				Theme [ <a href="<?php echo site_url(array(get_selected_radix()->shortname, 'theme', 'default')) ?>" onclick="changeTheme('default'); return false;">Default</a> / <a href="<?php echo site_url(array(get_selected_radix()->shortname, 'theme', 'fuuka')) ?>" onclick="changeTheme('fuuka'); return false;">Fuuka</a> / <a href="<?php echo site_url(array(get_selected_radix()->shortname, 'theme', 'yotsuba')) ?>" onclick="changeTheme('yotsuba'); return false;">Yotsuba</a> ]
			</td>
		</tr>
	</tbody>
</table>

<?php echo form_close() ?>