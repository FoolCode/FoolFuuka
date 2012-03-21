<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>
<table class="table table-bordered table-striped table-condensed">
	<thead>
		<tr>
			<th><?php echo _('Plugin name') ?></th>
			<th><?php echo _('Description') ?></th>
			<th><?php echo _('Status') ?></th>
			<th><?php echo _('Remove') ?></th>
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
					echo '<input type="submit" class="btn" value="' . ($plugin->enabled ? _('Disable')
							: _('Enable')) . '" />';
					echo form_close();
					?>
				</td>
				<td><?php
					echo form_open('admin/plugins/action', '', array('action' => 'remove')
					);
					echo '<input type="submit" class="btn" value="' . _('Remove') . '" />';
					echo form_close();
					?>
				</td></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
