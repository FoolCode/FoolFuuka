<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="list series">
	<div class="title">
		<a href="<?php echo site_url('reader/list') ?>"><?php echo _('List of the available comics'); ?></a>
	</div>
	<?php
	foreach ($comics as $key => $comic) {
		echo '<div class="group">';
		if($comic->get_thumb()) echo '<a href="'.$comic->href().'"><img class="preview" src="'.$comic->get_thumb().'" /></a>';
		echo '<div class="title">' . $comic->url() . ' <span class="meta">' . $comic->edit_url() . '</span></div>
				';
		if ($comic->latest_chapter->result_count() == 0) {
			echo '<div class="element">
					<div class="title">' . _("No releases for this series") . '.</div>
				</div></div>';
		}
		else
			echo '<div class="element">
					<div class="title">' . _("Latest release") . ': ' . $comic->latest_chapter->url() . '</div>
					<div class="meta_r">' . _('by') . ' ' . $comic->latest_chapter->team_url() . ', ' . $comic->latest_chapter->date() . ' ' . $comic->latest_chapter->edit_url() . '</div>
				</div></div>';
	}

	echo prevnext('/reader/list/', $comics);
	?>
</div>
