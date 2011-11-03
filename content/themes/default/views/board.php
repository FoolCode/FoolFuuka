<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

foreach ($posts->all as $post)
{
	echo '
			<article class="thread">
				<header>
					<h2 class="post_title">' . $post->title . '</h2>
					<span class="post_author">' . $post->name . '</span>
					<span class="post_trip">' . $post->trip . '</span>
					<time datetime="' . date(DATE_W3C, $post->timestamp) . '">' . date('D M d H:i:s Y', $post->timestamp) . '</time>
					<span class="post_number">No.' . $post->id . '</span>
					<span class="post_controls">[Reply] [Original]</span>';
	if ($post->media_filename)
	{
		echo '
					<br/>
					<span class="post_file">File: ' . byte_format($post->media_size, 0) . ', ' . $post->media_w . 'x' . $post->media_h . ', ' . $post->media . '</span>
					<span class="post_file_controls">[View same] [iqdb]</span>';
	}
	echo '
				</header>';
	if ($post->media_filename)
	{
		echo '
			<img src="' . $post->get_thumbnail() . '" class="thread_image" />';
	}
	echo '
		<div class="text">
		'.nl2br($post->comment).'
		</div>';
	echo '	
			<aside class="posts">';
	foreach ($post->post->all as $p)
	{
		echo '
			<article class="post">
				<header>
					<span class="post_author">' . $p->name . '</span>
					<span class="post_trip">' . $p->trip . '</span>
					<time datetime="' . date(DATE_W3C, $p->timestamp) . '">' . date('D M d H:i:s Y', $p->timestamp) . '</time>
					<span class="post_number">No.' . $p->id . '</span>';
		if ($p->media_filename)
		{
			echo '
					<br/>
					<span class="post_file">File: ' . byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media . '</span>
					<span class="post_file_controls">[View same] [iqdb]</span>';
		}
		echo '
			</header>';
		if ($p->media_filename)
		{
			echo '
			<img src="' . $p->get_thumbnail() . '" class="thread_image" />';
		}
		echo '
		<div class="text">
		' . nl2br($p->comment) . '
		</div>
		</article>
		<div class="clearfix"></div>';
	}
	echo ' </aside>';
	echo '<div class="clearfix"></div>';
	echo '</article>';
}
