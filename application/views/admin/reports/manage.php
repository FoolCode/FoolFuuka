<?php 			
	echo link_tag('content/themes/default/style.css?v=' . FOOL_VERSION);
?>

<div class="theme_default clearfix" style="padding-bottom: 15px">
	<article class="thread">
	<?php 
	foreach($posts as $key => $p)
	{
		include('content/themes/default/views/board_comment.php');
	}
	
	?>
	</article>
	


	<?php /* if ($reports->paged->total_pages > 1) : ?>
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
	<?php endif; */?>
</div>