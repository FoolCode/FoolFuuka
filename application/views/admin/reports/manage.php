<div class="table" style="padding-bottom: 15px">
	<h3><?php echo _('Reported Posts'); ?></h3>
	<?php echo buttoner(); ?>

	<div class="list comics">
		<?php $post = new Post();?>
		<?php foreach ($reports as $report) : ?>
		<div class="item">
			<div class="report_data">
				<span class="report_author">Anonymous</span> in /<?php echo $report['board']->shortname ?>/
				<time datetime="<?php echo date(DATE_W3C, strtotime($report['post']->report_created)) ?>"><?php echo date('D M d H:i:s Y', strtotime($report['post']->report_created)) ?></time>
				<div class="reason"><?php echo $report['post']->report_reason ?></div>
			</div>
			<article class="report report_id_<?php echo $report['post']->report_id ?>">
				<header>
					<div class="report_data">
						<h2 class="report_title"><?php echo $report['post']->title ?></h2>
						<span class="report_author"><?php echo $report['post']->name ?></span>
						<span class="report_trip"><?php echo $report['post']->trip ?></span>
						<time datetime="<?php echo date(DATE_W3C, $report['post']->timestamp) ?>"><?php echo date('D M d H:i:s Y', $report['post']->timestamp) ?></time>
						<span class="report_number">No.<?php echo $report['post']->num ?> on /<?php echo $report['board']->shortname ?>/</span>
					</div>
				</header>
				<?php if ($report['post']->media_filename) : ?>
				<a href="<?php echo site_url($report['board']->shortname.'/post/'.(($report['post']->subnum > 0) ? $report['post']->num . '_' . $report['post']->subnum : $report['post']->num)) ?>" rel="noreferrer" target="_blank" class="thread_image_link">
					<img src="<?php echo $post->get_image_href($report['board'], $report['post'], TRUE) ?>" width="<?php echo $report['post']->preview_w ?>" height="<?php echo $report['post']->preview_h ?>" class="thread_image"/>
				</a>
				<?php endif; ?>
				<div class="text"><?php echo nl2br($report['post']->comment) ?></div>
			</article>
			<div class="smalltext quick_tools">Quick Tools:
				<a href="<?php echo site_url('/admin/posts/action/remove/'.$report['post']->report_id) ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/remove/'.$report['post']->report_id) ?>', 'Do you really wish to delete this report?'); return false;">Delete Report</a> |
				<a href="<?php echo site_url('/admin/posts/action/delete/'.$report['post']->report_id.'/post/') ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/delete/'.$report['post']->report_id.'/post/') ?>', 'Do you really wish to delete this reported post?'); return false;">Delete Post</a> |
				<a href="<?php echo site_url('/admin/posts/action/delete/'.$report['post']->report_id.'/image/') ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/delete/'.$report['post']->report_id.'/image/') ?>', 'Do you really wish to delete this reported post\'s image?'); return false;">Delete Image</a> |
				<a href="<?php echo site_url('/admin/posts/action/spam/'.$report['post']->report_id) ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/spam/'.$report['post']->report_id) ?>', 'Do you really wish to mark this report as spam?'); return false;">Spam</a> |
				<a href="<?php echo site_url($report['board']->shortname.'/post/'.(($report['post']->subnum > 0) ? $report['post']->num . '_' . $report['post']->subnum : $report['post']->num)) ?>">View</a> |
				<a href="<?php echo site_url('/admin/posts/action/ban/'.$report['post']->report_id) ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/ban/'.$report['post']->report_id) ?>', 'Do you really wish to ban this IP?'); return false;">Ban</a> <?php echo $report['post']->poster_ip ?> |
				<a href="<?php echo site_url('/admin/posts/action/md5/'.$report['post']->report_id) ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/md5/'.$report['post']->report_id) ?>', 'Do you really wish to ban and delete this reported post\'s image?'); return false;">Ban and Delete Image</a>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<?php if ($reports->paged->total_pages > 1) : ?>
	<div class="pagination" style="margin-bottom: -5px">
		<ul>
		<?php
			if ($reports->paged->has_previous)
				echo '<li class="prev"><a href="' . site_url('admin/posts/reports/'.$reports->paged->previous_page) . '">&larr; ' . _('Prev') . '</a></li>';
			else
				echo '<li class="prev disabled"><a href="#">&larr; ' . _('Prev') . '</a></li>';

			if ($reports->paged->total_pages <= 15) :
				for ($index = 1; $index <= $reports->paged->total_pages; $index++)
				{
					echo '<li' . (($reports->paged->current_page == $index) ? ' class="active"' : '') . '><a href="' . site_url('admin/posts/reports/' . $index) . '">' . $index . '</a></li>';
				}
			else :
				if ($reports->paged->current_page < 15) :
					for ($index = 1; $index <= 15; $index++)
					{
						echo '<li' . (($reports->paged->current_page == $index) ? ' class="active"' : '') . '><a href="' . site_url('admin/posts/reports/' . $index) . '">' . $index . '</a></li>';
					}

					echo '<li class="disabled"><span>...</span></li>';
				else :
					for ($index = 1; $index < 10; $index++)
					{
						echo '<li' . (($reports->paged->current_page == $index) ? ' class="active"' : '') . '><a href="' . site_url('admin/posts/reports/' . $index) . '">' . $index . '</a></li>';
					}

					echo '<li class="disabled"><span>...</span></li>';

					for ($index = ((($reports->paged->current_page + 2) > $reports->paged->total_pages) ? ($reports->paged->current_page - 4) : ($reports->paged->current_page - 2)); $index <= ((($reports->paged->current_page + 2) > $reports->paged->total_pages) ? $reports->paged->total_pages : ($reports->paged->current_page + 2)); $index++)
					{
						echo '<li' . (($reports->paged->current_page == $index) ? ' class="active"' : '') . '><a href="' . site_url('admin/posts/reports/' . $index) . '">' . $index . '</a></li>';
					}

					if (($reports->paged->current_page + 2) < $reports->paged->total_pages)
						echo '<li class="disabled"><span>...</span></li>';
				endif;
			endif;

			if ($reports->paged->has_next)
				echo '<li class="next"><a href="' . site_url('admin/posts/reports/'.$reports->paged->next_page) . '">' . _('Next') . ' &rarr;</a></li>';
			else
				echo '<li class="next disabled"><a href="#">' . _('Next') . ' &rarr;</a></li>';
		?>
		</ul>
	</div>
	<?php endif; ?>
</div>