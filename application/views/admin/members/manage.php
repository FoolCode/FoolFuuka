<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<table class="table table-bordered table-striped table-condensed">
	<thead>
		<tr>
			<th><?php echo __('ID') ?></th>
			<th><?php echo __('Username') ?></th>
			<th><?php echo __('Display name') ?></th>
			<th><?php echo __('Twitter') ?></th>
			<th><?php echo __('Email') ?></th>
			<th><?php echo __('Last seen') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($users as $user) : ?>
			<tr>
				<td>
					<a href="<?php echo site_url('admin/members/member/' . $user->id) ?>">
						<?php echo $user->id ?>
					</a>
				</td>
				<td>
					<a href="<?php echo site_url('admin/members/member/' . $user->id) ?>">
						<?php echo fuuka_htmlescape($user->username) ?>
					</a>
				</td>
				<td><?php echo fuuka_htmlescape($user->display_name) ?></td>
				<td>
					<?php if (fuuka_htmlescape($user->twitter)) : ?>
						<a target="_blank" href="http://twitter.com/<?php echo fuuka_htmlescape($user->twitter) ?>">
							<?php echo fuuka_htmlescape($user->twitter) ?>
						</a>
					<?php endif; ?>
				</td>
				<td>
					<?php echo mailto($user->email, fuuka_htmlescape($user->email)); ?>
				</td>
				<td><?php echo fuuka_htmlescape($user->last_login) ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>