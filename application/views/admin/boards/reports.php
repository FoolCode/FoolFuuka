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
				<div class="comment">'.$report->comment.'</div>
				<div class="smalltext quick_tools">'._('Quick tools').': 
					<a href="'.site_url("admin/boards/add_new/").'">'._('Add Thread').'</a> |
					<a href="'.site_url("admin/boards/delete/board/").'" onclick="confirmPlug(\''.site_url("admin/boards/delete/board/").'\', \''._('Do you really want to delete this board and its threads?').'\'); return false;">'._('Delete').'</a> |
					<a href="'.site_url().'">'._('Read').'</a>
				</div>';
			echo '</div>';
		}
		?>
	</div>	
</div>