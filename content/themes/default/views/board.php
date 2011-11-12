<?php
if (!defined('BASEPATH'))
exit('No direct script access allowed');

foreach ($posts->all as $post) : ?>

<article class="thread">
	<header id="<?php echo $post->num ?>">
		<h2 class="post_title"><?php echo $post->title ?></h2>
		<span class="post_author"><?php echo $post->name ?></span>
		<span class="post_trip"><?php echo $post->trip ?></span>
		<time datetime="<?php echo date(DATE_W3C, $post->timestamp) ?>">' . date('D M d H:i:s Y', $post->timestamp) ?></time>
		<span class="post_number"><a href="<?php echo site_url($this->fu_board . '/thread/' . $post->num) ?>">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $post->num) . '#q' . $post->num ?>"><?php echo $post->num ?></a></span>
		<span class="post_controls">[<a href="<?php echo site_url($this->fu_board . '/thread/' . $post->num) ?>">Reply</a>] [<a href="http://boards.4chan.org/<?php echo $this->fu_board . '/res/' . $post->num ?>">Original</a>]</span>

		<?php if ($post->media_filename) : ?>
		<br/>
		<span class="post_file">File: <?php echo byte_format($post->media_size, 0) . ', ' . $post->media_w . 'x' . $post->media_h . ', ' . $post->media ?></span>
		<span class="post_file_controls">[<a href="<?php echo site_url($this->fu_board . '/image/' . urlencode(substr($post->media_hash, 0, -2))) ?>">View Same</a>] [<a href="http://iqdb.org/?url=<?php echo urlencode($post->get_thumbnail()) ?>">iqdb</a>]</span>
		<?php endif; ?>
	</header>
	<?php if ($post->media_filename) : ?>
	<img src="<?php echo $post->get_thumbnail() ?>" class="thread_image" />
	<?php endif; ?>
	<div class="text">
		<?php echo $post->get_comment() ?>
		<?php echo (($post->get_omitted() > 0)?'<h6>' . $post->get_omitted() . ' posts omitted.</h6>':'') ?>
	</div>
	<aside class="posts">';
		<?php foreach (array_reverse($post->post->all) as $p) : ?>
		<?php if ($p->subnum > 0) : ?>
		<article class="post" id="<?php echo $p->num . '_' . $p->subnum ?>">
			<header>
				<span class="post_author"><?php echo $p->name ?></span>
				<span class="post_trip"><?php echo $p->trip ?></span>
				<time datetime="<?php echo date(DATE_W3C, $p->timestamp) ?>">' . date('D M d H:i:s Y', $p->timestamp) ?></time>
				<span class="post_number"><a href="<?php echo site_url($this->fu_board . '/thread/' . $p->parent) . '#' . $p->num . '_' . $p->subnum ?>">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $p->parent) . '#q' . $p->num . '_' . $p->subnum ?>"><?php echo $p->num . ',' . $p->subnum ?></a></span>
				<span class="post_ghost">This is not an archived reply.</span>
		<?php else : ?>
		<article class="post" id="<?php echo $p->num ?>">
			<header>
				<span class="post_author"><?php echo $p->name ?></span>
				<span class="post_trip"><?php echo $p->trip ?></span>
				<time datetime="<?php echo date(DATE_W3C, $p->timestamp) ?>"><?php echo date('D M d H:i:s Y', $p->timestamp) ?></time>
				<span class="post_number"><a href="<?php echo site_url($this->fu_board . '/thread/' . $p->parent) . '#' . $p->num ?>">No.</a><a href="<?php echo site_url($this->fu_board . '/thread/' . $p->parent) . '#q' . $p->num ?>"><?php echo $p->num ?></a></span>
				<?php endif; ?>
				<?php if ($p->media_filename) : ?>
				<br/>
				<span class="post_file">File: <?php echo byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media ?></span>
				<span class="post_file_controls">[<a href="<?php echo site_url($this->fu_board . '/image/' . urlencode(substr($p->media_hash, 0, -2))) ?>">View Same</a>] [<a href="http://iqdb.org/?url=<?php echo urlencode($p->get_thumbnail()) ?>">iqdb</a>]</span>
				<?php endif; ?>
			</header>
		<?php if ($p->media_filename) : ?>
		<img src="<?php echo $p->get_thumbnail() ?>" class="post_image" />';
		<?php endif; ?>
			<div class="text">
				<?php echo $p->get_comment() ?>
			</div>
		</article>
		<div style="clear:right"></div>
	<?php endforeach; ?>
	</aside>
	<div class="clearfix"></div>
</article>
<?php endforeach; ?>
