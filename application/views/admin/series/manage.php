<?php
$CI =& get_instance();
$CI->buttoner = array(
	array(
		'href' => site_url('/admin/series/add_new/'),
		'text' => _('Add Series')
	)
);
?>

<div class="table" style="padding-bottom: 15px">
	<h3 style="float: left"><?php echo _('Series Information'); ?></h3>
	<span style="float: right; padding: 5px">
		<div class="smartsearch">
		<?php
		echo form_open(site_url('/admin/series/manage/'));
		echo form_input(array('name'=>'search', 'placeholder' => _('To search, write and hit enter')));
		echo form_close();
		?>
		</div>
	</span>
	<hr class="clear"/>
	<?php echo buttoner(); ?>

	<div class="list comics">
		<?php
		foreach ($comics as $comic)
		{
			echo '<div class="item">
				<div class="title"><a href="'.site_url("admin/series/series/".$comic->stub).'">'.$comic->name.'</a></div>
				<div class="smalltext">'._('Quick tools').': 
					<a href="'.site_url("admin/series/add_new/".$comic->stub).'">'._('Add Chapter').'</a> |
					<a href="'.site_url("admin/series/delete/serie/".$comic->id).'" onclick="confirmPlug(\''.site_url("admin/series/delete/serie/".$comic->id).'\', \''._('Do you really want to delete this serie and its chapters?').'\'); return false;">'._('Delete').'</a> |
					<a href="'.site_url("reader/series/".$comic->stub).'">'._('Read').'</a>
				</div>';
			echo '</div>';
		}
		?>
	</div>	
<?php
	if ($comics->paged->total_pages > 1)
	{
?>
	<div class="pagination" style="margin-bottom: -5px">
		<ul>
		<?php
			if ($comics->paged->has_previous)
				echo '<li class="prev"><a href="' . site_url('admin/series/manage/'.$comics->paged->previous_page) . '">&larr; ' . _('Prev') . '</a></li>';
			else
				echo '<li class="prev disabled"><a href="#">&larr; ' . _('Prev') . '</a></li>';

			$page = 1;
			while ($page <= $comics->paged->total_pages)
			{
				if ($comics->paged->current_page == $page)
					echo '<li class="active"><a href="#">' . $page . '</a></li>';
				else
					echo '<li><a href="' . site_url('admin/series/manage/'.$page) .'">' . $page . '</a></li>';
				$page++;
			}

			if ($comics->paged->has_next)
				echo '<li class="next"><a href="' . site_url('admin/series/manage/'.$comics->paged->next_page) . '">' . _('Next') . ' &rarr;</a></li>';
			else
				echo '<li class="next disabled"><a href="#">' . _('Next') . ' &rarr;</a></li>';
		?>
		</ul>
	</div>
<?php
	}
?>
</div>