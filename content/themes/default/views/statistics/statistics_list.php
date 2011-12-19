<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>
<nav style="margin-top:20px;">
	<p style="margin-left:20px;">The statistics are work in progress. We will get around it quite soon.</p>
	<ul>
		<?php foreach ($stats_list as $key => $stat) : ?>
			<li>
				<a href="<?php echo site_url(array(get_selected_board()->shortname, 'statistics', $key)) ?>" title="<?php echo form_prep($stat['name']) ?>" ><?php echo $stat['name'] ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>