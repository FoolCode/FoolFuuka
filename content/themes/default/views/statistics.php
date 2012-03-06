<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if ($is_statistics) : ?>
<div style="margin: 20px auto; width:960px;">
	<?php if ($is_statistics_list) : ?>
	<nav style="margin-top:20px;">
		<ul>
			<?php foreach ($info as $key => $stat) : ?>
			<li>
				<a href="<?php echo site_url(array(get_selected_radix()->shortname, 'statistics', $key)) ?>" title="<?php echo form_prep($stat['name']) ?>" ><?php echo $stat['name'] ?></a>
			</li>
			<?php endforeach; ?>
		</ul>
	</nav>

	<?php else : ?>
	<?php echo $template['partials']['stats_interface'] ?>
	<?php endif; ?>
</div>
<?php endif; ?>
