<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="btn-group">
	<a class="btn btn-success btn-mini" href="<?php echo site_url('/admin/boards/add_new/') ?>">
		<?php echo __('Add board') ?>
	</a>
</div>
<br/>
<table class="table table-bordered table-striped table-condensed">
	<thead>
			<tr>
				<th><?php echo __('Board') ?></th>
				<th><?php echo __('Shortname') ?></th>
				<th><?php echo __('Quick functions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($boards as $board) : ?>
			<tr>
				<td>
					<a href="<?php echo site_url("admin/boards/board/".$board->shortname) ?>">
					   <?php echo fuuka_htmlescape($board->name) ?>
					</a>
				</td>
				<td>
					 <?php echo fuuka_htmlescape($board->shortname) ?>
				</td>
				<td>
					<div class="btn-group">
					<a class="btn btn-mini btn-primary" href="<?php echo site_url('admin/boards/board/'.$board->shortname) ?>">
						<?php echo __('Edit') ?>
					</a>
					<a class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown" href="#">
						<span class="caret"></span>
					</a>
					<ul class="dropdown-menu">
						<li>
							<a href="<?php echo site_url('admin/boards/board/'.$board->shortname) ?>">
								<?php echo __('Edit') ?>
							</a>
						</li>
						<li class="divider"></li>
						<li>
							<a href="<?php echo site_url('admin/boards/delete/board/'.$board->id) ?>">
								<?php echo __('Delete') ?>
							</a>
						</li>
					</ul>
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
</table>