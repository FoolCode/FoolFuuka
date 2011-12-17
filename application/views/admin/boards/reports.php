<div class="table" style="padding-bottom: 15px">
	<h3><?php echo _('Reported Posts'); ?></h3>
	<?php echo buttoner(); ?>

	<div class="list comics">
		<?php
		foreach ($reports as $report)
		{
			echo '<div class="item">
				<div class="title">Anonymous at '.$report->report_created.' on /'.$report->shortname.'/</div>
				<div class="report_reason">'.$report->report_reason.'</div>
				<div class="post">'.$report->comment.'</div>
				<div class="smalltext quick_tools">'._('Quick Tools').': 
					<a href="">'._('Delete').'</a> |
					<a href="">'._('Spam').'</a> |
					<a href="'.site_url($report->shortname.'/post/'.$report->num).'">'._('View').'</a> |
					<a href="">'._('Ban').'</a> '.$report->poster_ip.'
				</div>';
			echo '</div>';
		}
		?>
	</div>	
</div>