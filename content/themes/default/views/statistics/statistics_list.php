<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>
<nav style="margin-top:20px;">
	<ul>
		<?php foreach ($stats_list as $key => $stat) : ?>
			<li>
				<a href="<?php echo site_url(array(get_selected_radix()->shortname, 'statistics', $key)) ?>" title="<?php echo form_prep($stat['name']) ?>" ><?php echo $stat['name'] ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>