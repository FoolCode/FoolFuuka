<?php
$this->buttoner[] = array(
	'text' => _('Delete Board'),
	'href' => site_url('/admin/boards/delete/board/'.$board->id),
	'plug' => _('Do you really want to delete this board and its threads?')
);
?>
<div class="table">
	<h3 style="float: left"><?php echo _('Board Information'); ?></h3>
	<span style="float: right; padding: 5px"><?php echo buttoner(); ?></span>
	<hr class="clear"/>
	<?php
		echo form_open_multipart("", array('class' => 'form-stacked'));
		echo $table;
		echo form_close();
	?>
</div>

<br/>

<?php
	$this->buttoner = array(
		array(
			'href' => site_url('/admin/boards/add_new/'.$board->shortname),
			'text' => _('Add Thread')
		)
	);
?>
<div class="table" style="padding-bottom: 15px">
	<h3 style="float: left"><?php echo _('Latest threads'); ?></h3>
	<span style="float: right; padding: 5px"><?php echo buttoner(); ?></span>
	<hr class="clear">
	<div class="list chapters">
	<?php /*
		foreach ($threads as $item)
		{
			echo '<div class="item">
				<div class="title"><a href="'.site_url("admin/series/serie/".$comic->stub."/".$item->id).'">'. $item->title().'</a></div>
				<div class="smalltext info">
					Chapter #'.$item->chapter.'
					Sub #'.$item->subchapter;
						if(isset($item->jointers))
						{
							echo ' By ';
							foreach($item->jointers as $key2 => $jointe)
							{
								if($key2>0) echo " | ";
								echo '<a href="'.site_url("/admin/users/teams/".$jointe->stub).'">'.$jointe->name.'</a>';
							}
						}
						else echo '
					By <a href="'.site_url("/admin/users/teams/".$item->team_stub).'">'.$item->team_name.'</a></div>
				<div class="smalltext">
					'._('Quick tools').': 
						<a href="'.site_url("admin/series/delete/chapter/".$item->id).'" onclick="confirmPlug(\''.site_url("admin/series/delete/chapter/".$item->id).'\', \''._('Do you really want to delete this chapter and its pages?'). '\'); return false;">' . _('Delete') . '</a> |
						<a href="';
							if ($item->subchapter)
								echo site_url("reader/read/".$item->comic->stub."/".$item->language."/".$item->volume."/".$item->chapter."/".$item->subchapter."/");
							else
								echo site_url("reader/read/".$item->comic->stub."/".$item->language."/".$item->volume."/".$item->chapter."/");
			echo '">' . _('Read') . '</a>
				</div>';
			echo '</div>';
		}
	*/ ?>
	</div>
</div>