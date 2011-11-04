<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

foreach ($posts->all as $post)
{
	echo '
			<article class="thread">
				<header id="'.$post->num.'">
					<h2 class="post_title">' . $post->title . '</h2>
					<span class="post_author">' . $post->name . '</span>
					<span class="post_trip">' . $post->trip . '</span>
					<time datetime="' . date(DATE_W3C, $post->timestamp) . '">' . date('D M d H:i:s Y', $post->timestamp) . '</time>
					<span class="post_number"><a href="' . site_url($this->fu_board . '/thread/' . $post->num) . '">No.</a><a href="' . site_url($this->fu_board . '/thread/' . $post->num) . '#q' . $post->num . '">' . $post->num . '</a></span>
					<span class="post_controls">[<a href="' . site_url($this->fu_board . '/thread/' . $post->num) . '">Reply</a>] [<a href="http://boards.4chan.org/' . $this->fu_board . '/res/' . $post->num . '">Original</a>]</span>';
	if ($post->media_filename)
	{
		echo '
					<br/>
					<span class="post_file">File: ' . byte_format($post->media_size, 0) . ', ' . $post->media_w . 'x' . $post->media_h . ', ' . $post->media . '</span>
					<span class="post_file_controls">[<a href="' . site_url($this->fu_board . '/image/' . substr($post->media_hash, 0, -2)) . '">View Same</a>] [<a href="http://iqdb.org/?url=' . urlencode($post->get_thumbnail()) . '">iqdb</a>]</span>';
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
		' . $post->get_comment() . '
		</div>';
	echo '	
			<aside class="posts">';
	foreach (array_reverse($post->post->all) as $p)
	{
		if ($p->subnum > 0)
		{
			echo '
			<article class="post" id="'.$p->num.'_'.$p->subnum.'">
				<header>
					<span class="post_author">' . $p->name . '</span>
					<span class="post_trip">' . $p->trip . '</span>
					<time datetime="' . date(DATE_W3C, $p->timestamp) . '">' . date('D M d H:i:s Y', $p->timestamp) . '</time>
					<span class="post_number"><a href="' . site_url($this->fu_board . '/thread/' . $p->parent) . '#' . $p->num . '_' . $p->subnum . '">No.</a><a href="' . site_url($this->fu_board . '/thread/' . $p->parent) . '#q' . $p->num . '_' . $p->subnum . '">' . $p->num . ',' . $p->subnum . '</a></span>
				';
		}
		else
		{
			echo '
			<article class="post" id="'.$p->num.'">
				<header>
					<span class="post_author">' . $p->name . '</span>
					<span class="post_trip">' . $p->trip . '</span>
					<time datetime="' . date(DATE_W3C, $p->timestamp) . '">' . date('D M d H:i:s Y', $p->timestamp) . '</time>
					<span class="post_number"><a href="' . site_url($this->fu_board . '/thread/' . $p->parent) . '#' . $p->num . '">No.</a><a href="' . site_url($this->fu_board . '/thread/' . $p->parent) . '#q' . $p->num . '">' . $p->num . '</a></span>
				';
		}
		
		
		if ($p->media_filename)
		{
			echo '
					<br/>
					<span class="post_file">File: ' . byte_format($p->media_size, 0) . ', ' . $p->media_w . 'x' . $p->media_h . ', ' . $p->media . '</span>
					<span class="post_file_controls">[<a href="' . site_url($this->fu_board . '/image/' . substr($p->media_hash, 0, -2)) . '">View Same</a>] [<a href="http://iqdb.org/?url=' . urlencode($p->get_thumbnail()) . '">iqdb</a>]</span>';
		}
		echo '
			</header>';
		if ($p->media_filename)
		{
			echo '
			<img src="' . $p->get_thumbnail() . '" class="post_image" />';
		}
		echo '
		<div class="text">
		' . $p->get_comment() . '
		</div>
		</article>
		<div class="clearfix"></div>';
	}
	echo ' </aside>';
	echo '<div class="clearfix"></div>';
	echo '</article>';
}
