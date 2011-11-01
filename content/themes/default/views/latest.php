<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class="list">
	<div class="title">
		<a href="<?php echo site_url('/reader/latest/') ?>"><?php echo _('Latest released chapters')?>:</a>
	</div>
     <?php
		$current_comic = "";
		$current_comic_closer = "";
		
		$opendiv = FALSE;
		// Let's loop over every chapter. The array is just $chapters because we used get_iterated(), else it would be $chapters->all
		foreach($chapters as $key => $chapter)
		{
			if ($current_comic != $chapter->comic_id)
			{
				if ($opendiv) {
					echo '</div>';
				}
				echo '<div class="group"><div class="title">'.$chapter->comic->url().'</div>';
				$current_comic = $chapter->comic_id;
			}
			
			echo '<div class="element">'.$chapter->download_url(NULL, 'fleft small').'
					<div class="title">'.$chapter->url().'</div>
					<div class="meta_r">' . _('by') . ' ' . $chapter->team_url() . ', ' . $chapter->date() . ' ' . $chapter->edit_url() . '</div>
				</div>';
			$opendiv = TRUE;
			
		}
		
		// Closing the last comic group
		echo '</div>';
	?>
</div>