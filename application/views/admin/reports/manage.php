<div class="table" style="padding-bottom: 15px">
	<h3><?php echo _('Reported Posts'); ?></h3>
	<?php echo buttoner(); ?>

	<div class="list comics">
		<?php $rep = new Report(); ?>
		<?php foreach ($reports->all as $report) : ?>
		<div class="item">
			<div class="report_data">
				<span class="report_author">Anonymous</span> in /<?php echo $report->shortname ?>/
				<time datetime="<?php echo date(DATE_W3C, strtotime($report->report_created)) ?>"><?php echo date('D M d H:i:s Y', strtotime($report->report_created)) ?></time>
				<div class="reason"><?php echo $report->report_reason ?></div>
			</div>
			<article class="report report_id_<?php echo $report->report_id ?>">
				<header>
					<div class="report_data">
						<h2 class="report_title"><?php echo $report->title ?></h2>
						<span class="report_author"><?php echo $report->name ?></span>
						<span class="report_trip"><?php echo $report->trip ?></span>
						<time datetime="<?php echo date(DATE_W3C, $report->timestamp) ?>"><?php echo date('D M d H:i:s Y', $report->timestamp) ?></time>
						<span class="report_number">No.<?php echo $report->num ?> on /<?php echo $report->shortname ?>/</span>
					</div>
				</header>
				<?php if ($report->media_filename) : ?>
				<a href="" rel="noreferrer" target="_blank" class="thread_image_link"><img src="<?php echo $rep->get_image_href($report, TRUE) ?>" width="<?php echo $report->preview_w ?>" height="<?php echo $report->preview_h ?>" class="thread_image"/></a>
				<?php endif; ?>
				<div class="text"><?php echo nl2br($report->comment) ?></div>
			</article>
			<div class="smalltext quick_tools">Quick Tools:
				<a href="<?php echo site_url('/admin/posts/action/remove/'.$report->report_id) ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/remove/'.$report->report_id) ?>', 'Do you really wish to delete this report?'); return false;">Delete Report</a> |
				<a href="<?php echo site_url('/admin/posts/action/delete/'.$report->report_id.'/post/') ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/delete/'.$report->report_id.'/post/') ?>', 'Do you really wish to delete this reported post?'); return false;">Delete Post</a> |
				<a href="<?php echo site_url('/admin/posts/action/delete/'.$report->report_id.'/image/') ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/delete/'.$report->report_id.'/image/') ?>', 'Do you really wish to delete this reported post\'s image?'); return false;">Delete Image</a> |
				<a href="<?php echo site_url('/admin/posts/action/spam/'.$report->report_id) ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/spam/'.$report->report_id) ?>', 'Do you really wish to mark this report as spam?'); return false;">Spam</a> |
				<a href="<?php echo site_url($report->shortname.'/post/'.(($report->subnum > 0) ? $report->num . '_' . $report->subnum : $report->num)) ?>">View</a> |
				<a href="<?php echo site_url('/admin/posts/action/ban/'.$report->report_id) ?>" onclick="confirmPlug('<?php echo site_url('/admin/posts/action/ban/'.$report->report_id) ?>', 'Do you really wish to ban this IP?'); return false;">Ban</a> <?php echo $report->poster_ip ?>
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

			$page = 1;
			while ($page <= $reports->paged->total_pages)
			{
				if ($reports->paged->current_page == $page)
					echo '<li class="active"><a href="#">' . $page . '</a></li>';
				else
					echo '<li><a href="' . site_url('admin/posts/reports/'.$page) .'">' . $page . '</a></li>';
				$page++;
			}

			if ($reports->paged->has_next)
				echo '<li class="next"><a href="' . site_url('admin/posts/reports/'.$reports->paged->next_page) . '">' . _('Next') . ' &rarr;</a></li>';
			else
				echo '<li class="next disabled"><a href="#">' . _('Next') . ' &rarr;</a></li>';
		?>
		</ul>
	</div>
	<?php endif; ?>
</div>