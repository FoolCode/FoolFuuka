<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
//print_r($p);
$selected_radix = isset($p->board)?$p->board:get_selected_radix();
?>

<article class="clearfix post doc_id_<?php echo $p->doc_id ?>
	<?php if ($p->subnum > 0) : ?> post_ghost<?php endif; ?>
	<?php if ($p->parent == $p->num) : ?> post_is_op<?php endif; ?> 
	<?php echo ((isset($p->report_status) && !is_null($p->report_status)) ? ' reported' : '') ?>
	<?php echo ($p->media ? ' has_image' : '') ?><?php if ($p->media) : ?> clearfix<?php endif; ?>
		<?php if (false && $p->spam == 1) : ?> is_spam<?php endif; ?>" 
		 id="<?php echo $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">

	<div class="voting">
		<a class="up" data-function="vote" data-vote="1" data-doc-id="<?php echo $p->doc_id ?>" data-board="<?php echo $selected_radix->shortname ?>"></a>
		<a class="down" data-function="vote" data-vote="-1" data-doc-id="<?php echo $p->doc_id ?>" data-board="<?php echo $selected_radix->shortname ?>"></a>
	</div>
	
	<?php if ($p->preview) : ?>
		<div class="thread_image_box">
			<a href="<?php echo ($p->media_link) ? $p->media_link : $p->remote_media_link ?>" target="_blank" rel="noreferrer" class="thread_image_link">
				<img src="<?php echo $p->thumb_link ?>" <?php echo ($p->preview_w > 0 && $p->preview_h > 0) ? 'width="' . $p->preview_w . '" height="' . $p->preview_h . '" ' : '' ?>class="thread_image<?php echo ($p->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?php echo $p->media_hash ?>" />
			</a>
		</div>
	<?php endif; ?>
	
	<header>
		[-] <a href="#" class="poster"><?php echo $p->name_processed . ' ' . $p->trip_processed ?></a>
			<span class="vote_number"><?php echo $this->vote->count($selected_radix, $p->doc_id) ?></span> points <?php echo delta_time($p->timestamp) ?>
	</header>
	
	<div class="comment">
		<?php echo $p->comment_processed ?>
	</div>
	
	<div class="permalink">
		<a href="<?php echo site_url($selected_radix->shortname . '/thread/' . $p->parent) . '#'  . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>">permalink</a>
		<a href="<?php echo site_url($selected_radix->shortname . '/thread/' . $p->parent) . '#q' . $p->num . (($p->subnum > 0) ? '_' . $p->subnum : '') ?>" data-post="<?php echo $p->num . (($p->subnum > 0) ? ',' . $p->subnum : '') ?>" data-function="quote">quote</a>
	</div>
	<aside class="posts clearfix quoting">
	<?php 
			if(isset($p->backlinks) && !empty($p->backlinks))
			{
				$quoting = TRUE;
				foreach($p->backlinks as $bkey => $blink)
				{
					if(in_array($k, $skip))
					{
						continue;
					}
					
					$p = $post['posts'][$bkey];
					$skip[] = $bkey;
					
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
	
</article>
