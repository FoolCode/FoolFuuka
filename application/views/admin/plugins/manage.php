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
						echo form_open('admin/plugins/action/' . $plugin->info->slug, '',
							array('action' => $plugin->enabled ? 'disable' : 'enable')
						);
						echo '<input type="submit" class="btn" value="' .($plugin->enabled ? _('Disable') : _('Enable')). '" />';
						echo form_close();
						?>
					</td>
					<td><?php
						echo form_open('admin/plugins/action', '',
							array('action' => 'remove')
						);
						echo '<input type="submit" class="btn" value="' . _('Remove') . '" />';
						echo form_close();
						?>
					</td></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
