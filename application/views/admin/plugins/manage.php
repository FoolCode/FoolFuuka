<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="table">
	<h3 style="float: left"><?php echo _('Available plugins'); ?></h3>

	<table class="table-bordered table-striped table-condensed">
		<thead>
			<tr>
				<th>Plugin name</th>
				<th>Description</th>
				<th>Status</th>
				<th>Remove</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($plugins as $plugin) : ?>
				<tr>
					<td><?php echo $plugin->info->name ?></td>
					<td><?php echo $plugin->info->description ?></td>
					<td>
						<?php
						form_open('admin/plugins/action', '',
							array('action' => $plugin->enabled ? 'enable' : 'disable')
						);
						echo '<input type="submit" class="btn" value="' .($plugin->enabled ? _('Disable') : _('Enable')). '" />';
						form_close();
						?>
					</td>
					<td><?php
						form_open('admin/plugins/action', '',
							array('action' => 'remove')
						);
						echo '<input type="submit" class="btn" value="' . _('Remove') . '" />';
						form_close();
						?>
					</td></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
