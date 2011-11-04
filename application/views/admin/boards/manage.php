<?php
$CI =& get_instance();
$CI->buttoner = array(
	array(
		'href' => site_url('/admin/boards/add_new/'),
		'text' => _('Add Board')
	)
);
?>

<div class="table" style="padding-bottom: 15px">
	<h3><?php echo _('Boards Information'); ?></h3>
	<?php echo buttoner(); ?>

	<div class="list comics">
		<?php
		foreach ($boards as $board)
		{
			echo '<div class="item">
				<div class="title"><a href="'.site_url("admin/boards/board/".$board->shortname).'">'.$board->name.'</a></div>
				<div class="smalltext">'._('Quick tools').': 
					<a href="'.site_url("admin/boards/add_new/".$board->shortname).'">'._('Add Thread').'</a> |
					<a href="'.site_url("admin/boards/delete/board/".$board->id).'" onclick="confirmPlug(\''.site_url("admin/boards/delete/board/".$board->id).'\', \''._('Do you really want to delete this board and its threads?').'\'); return false;">'._('Delete').'</a> |
					<a href="'.site_url($board->shortname).'">'._('Read').'</a>
				</div>';
			echo '</div>';
		}
		?>
	</div>	
</div>