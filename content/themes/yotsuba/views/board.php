<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class="board">

	<?php
	foreach ($posts as $key => $post) :
		if (isset($post['op'])) :
			$op = $post['op'];
			$selected_radix = isset($op->board)?$op->board:get_selected_radix();
			
			$num =  $op->num . ( $op->subnum ? '_' . $op->subnum : '' );
	?>

	<div class="thread" id="t1418">
		<div class="postContainer opContainer" id="pc<?= $num ?>">
			<div id="p1418" class="post op">
				<div class="postInfoM mobile" id="pim<?= $num ?>">
					<span class="postNum nameBlock<?= (($op->capcode == 'M') ? 'capcodeMod':'') . (($op->capcode == 'A') ? 'capcodeAdmin':'') ?>">
					<span class="subject"><?= $op->title_processed ?></span> 
					<span class="name"><?= $op->name_processed ?></span>
					<?php if ($op->trip) : ?><span class="postertrip"><?= $op->trip ?></span><?php endif; ?>
					<?php if (in_array($op->capcode, array('M', 'A'))) : ?>
						<strong class="capcode">## <?= (($op->capcode == 'M') ? 'Mod':'') . (($op->capcode == 'A') ? 'Admin':'') ?>"></strong>
						<?php if ($op->capcode == 'M') : ?><img src="//static.4chan.org/image/modicon.gif" alt="This user is a Moderator." title="This user is a Moderator." class="identityIcon" style="float: none!important; margin-left: 0px;"><?php endif; ?>
						<?php if ($op->capcode == 'A') : ?><img src="//static.4chan.org/image/adminicon.gif" alt="This user is an Administrator." title="This user is an Administrator." class="identityIcon" style="float: none!important; margin-left: 0px;"><?php endif; ?>
					<?php endif; ?>
					<br/>
					<em><a href="res/1418#p1418" title="Highlight this post">No.</a><a href="res/1418#q1418" title="Quote this post">1418</a></em>
					</span>
					<span class="dateTime">04/27/12(Fri)22:49:38</span>
				</div>
				<div class="file" id="f<?= $num ?>">
					<div class="fileInfo">
					<span class="fileText">File: <a href="//images.4chan.org/htmlnew/src/1335581378629.jpg" target="_blank">1335581378.jpg</a>-(29 KB, 233x280, <span title="618c1207.jpg">618c1207.jpg</span>)</span>
					</div>
					<a class="fileThumb" href="//images.4chan.org/htmlnew/src/1335581378629.jpg" target="_blank"><img src="//1.thumbs.4chan.org/htmlnew/thumb/1335581378629s.jpg" alt="29 KB" data-md5="YYwSB0r/LTYjErW4ojBkAQ==" style="height: 251px; width: 210px;"/></a>
				</div>
				<div class="postInfo" id="pi<?= $num ?>">
					<input type="checkbox" name="<?= $num ?>" value="delete"/>
					<span class="subject"></span>
					<span class="nameBlock capcodeMod">
					<span class="name">Anonymous</span> <strong class="capcode">## Mod</strong> <img src="//static.4chan.org/image/modicon.gif" alt="This user is a 4chan Moderator." title="This user is a 4chan Moderator." class="identityIcon" style="float: none!important; margin-left: 0px;">
					</span>
					<span class="dateTime">04/27/12(Fri)22:49:38</span>
					<span class="postNum">
					<a href="res/1418#p1418" title="Highlight this post">No.</a><a href="res/1418#q1418" title="Quote this post"><?= $num ?></a> <img src="//static.4chan.org/image/sticky.gif" alt="Sticky" title="Sticky"/> <img src="//static.4chan.org/image/closed.gif" alt="Closed" title="Closed"/> &nbsp; [<a href="res/1418" class="replylink">Reply</a>]
					</span>
				</div>
				<blockquote class="postMessage" id="m<?= $num ?>">Hello extension developers. Use these pages to get you ready for the 4chan HTML refresh.<br/><br/>I've attempted to include everything I can think of that you should need to help with your development.</blockquote>
			</div>
			<div class="postLink mobile">
				<span class="info">
				<strong>9 posts omitted.</strong><br/><em>(9 have images)</em>
				</span>
				<a href="res/1418" class="quotelink button">View Thread</a>
			</div>
		</div>
		<span class="summary desktop">9 posts and 9 image replies omitted. Click Reply to view.</span>
	
	<?php endif; ?>
	
	<?php
		if (isset($post['posts']))
		{
			foreach ($post['posts'] as $p)
			{
				if(!isset($thread_id))
					$thread_id = NULL;

				if ($p->thread_num == 0)
					$p->thread_num = $p->num;

				echo $this->theme->build('board_comment', array('p' => $p, 'modifiers' => $modifiers), TRUE, TRUE);
			}
		}
	?>
	
	
	</div>
	<hr/>
	
	<?php endforeach; ?>


</div>