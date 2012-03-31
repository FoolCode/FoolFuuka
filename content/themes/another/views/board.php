<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php 
if(isset($pagination['current_page']))
$counter = (24*$pagination['current_page'])-24;
else
$counter = 0;

foreach ($posts as $key => $post) : 
if(!isset($post['op']->nreplies))
{
	if(isset($post['posts']))
		$post['op']->nreplies = count($post['posts']);
	else
		$post['op']->nreplies = 0;
}
?>
<article<?php echo (isset($post['op']->num)) ? ' id="' . $post['op']->num . '" 
	class="clearfix thread doc_id_' . $post['op']->doc_id . '"' : ' class="clearfix thread"'; ?>>
	<?php if (isset($post['op'])) : $op = $post['op']; endif; ?>
	<?php if($is_page) : ?>
	<div class="counter"><?php echo ++$counter; ?></div>
	<?php endif; ?>

	<?php if(isset($post['op']->num)) : ?>
	<div class="voting">
		<a class="up" data-function="vote" data-vote="1" data-doc-id="<?php echo $op->doc_id ?>" data-board="<?php echo get_selected_radix()->shortname ?>"></a>
		<div class="vote_number number"><?php echo $this->vote->count(get_selected_radix(), $op->doc_id) ?></div>
		<a class="down" data-function="vote" data-vote="-1" data-doc-id="<?php echo $op->doc_id ?>" data-board="<?php echo get_selected_radix()->shortname ?>"></a>
	</div>
	
	<?php if ($op->preview) : ?>
		<div class="thread_image_box">
			<a href="<?php echo ($op->media_link) ? $op->media_link : $op->remote_media_link ?>" target="_blank" rel="noreferrer" class="thread_image_link">
				<img src="<?php echo $op->thumb_link ?>" <?php echo ($op->preview_w > 0 && $op->preview_h > 0) ? 'width="' . $op->preview_w . '" height="' . $op->preview_h . '" ' : '' ?>class="thread_image<?php echo ($op->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?php echo $op->media_hash ?>" />
			</a>
		</div>
	<?php endif; ?>
	
	
	<header<?php echo (isset($op->report_status) && !is_null($op->report_status)) ? ' class="reported"' : '' ?>>
		<div class="title">
		<a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) ?>">
			<?php 
			if(!$op->title_processed)
			{
				if($op->comment_processed)
				{
					if(strlen($op->comment_processed) > 80)
						echo mb_substr(strip_tags($op->comment_processed), 0, 80) . '[...]';
					else 
						echo strip_tags($op->comment_processed);
				}
				else
				{
					echo 'No title';
				}
			
			}
			else
			{
				echo $op->title_processed;
			}
			?>
		</a>
		</div>
		
		<?php if (($is_thread || $is_last50) && $op->comment_processed) : ?>
		<div class="text">
			<?php echo $op->comment_processed ?>
		</div>
		<?php endif; ?>
		
		<div class="submitted">submitted <?php echo delta_time($op->timestamp) ?> by <a class="name" href="<?php echo site_url(get_selected_radix()->shortname . '/search/username/' . $op->name_processed) ?>"><?php echo $op->name_processed ?></a>
			<?php if($op->trip_processed) : ?><a href="<?php echo site_url(get_selected_radix()->shortname . '/search/trip/' . $op->trip_processed) ?>"><?php echo $op->trip_processed ?></a><?php endif ?></div>
		<div class="comments_number"><a href="<?php echo site_url(get_selected_radix()->shortname . '/thread/' . $op->num) ?>"><?php echo $op->nreplies ?> comments</a></div>
	</header>
	<?php endif; ?>
	
	<aside class="posts clearfix">
	<?php
	if (isset($post['posts']) && !$is_page)
	{
		?>
		<?php if($is_thread || $is_last50) :?>
		<h3>all <?php echo $op->nreplies ?> comments</h3>
		<?php endif; ?>
		<?php if(isset($section_title)) : ?>
		<h3><?php echo $section_title ?></h3>
		<?php endif; ?>
		<?php

		$post_counter = 0;
		$skip = array();
		foreach ($post['posts'] as $k => $p)
		{
			if(in_array($k,$skip))
			{
				continue;
			}
			
			$quoting = FALSE;
			if ($p->preview)
				$post_counter++;

			if ($p->parent == 0)
				$p->parent = $p->num;

			if (file_exists('content/themes/' . $this->fu_theme . '/views/board_comment.php'))
				include('content/themes/' . $this->fu_theme . '/views/board_comment.php');
			else
				include('content/themes/' . $this->config->item('theme_extends') . '/views/board_comment.php');
		}
	}
	?>
	</aside>
	
	<?php if($is_thread || $is_last50) : ?>
	<?php echo $template['partials']['post_thread']; ?>
	<?php endif; ?>
</article>

<?php endforeach; ?>