<div class="table" style="padding-bottom: 15px">
	<h3><?php echo _('Reported Posts'); ?></h3>
	<?php echo buttoner(); ?>

	<div class="list comics">
		<?php
		print_r($reports);
		
		foreach ($reports as $report)
		{
			echo '<div class="item">
				<div class="title"><a href="'.site_url("admin/boards/board/").'"></a></div>
				<div class="reason">'.$report->report_reason.'</div>
				<div class="smalltext quick_tools">'._('Quick Tools').': 
					<a href="">'._('Delete').'</a> |
					<a href="">'._('Spam').'</a> |
					<a href="">'._('View').'</a>
				</div>';
			echo '</div>';
		}
		?>
	</div>	
</div>