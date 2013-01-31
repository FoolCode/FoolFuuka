<div class="admin-container">
	<div class="admin-container-header">
		<?= __('Boards') ?>
	</div>

	<div class="pull-right">
		<a class="btn btn-success btn-mini" href="<?= \Uri::create('/admin/boards/add/') ?>">
			<i class="icon-plus" style="color: #FFFFFF"></i> <?= __('Add Board') ?>
		</a>
	</div>

	<table class="table table-hover table-condensed">
		<thead>
			<tr>
				<th class="span1"><?= __('ID') ?></th>
				<th class="span4"><?= __('Board') ?></th>
				<th class="span4"><?= __('Title') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($boards as $board) : ?>
			<tr>
				<td><?= $board->id ?></td>
				<td>
					<a href="<?= \Uri::create('admin/boards/board/'.$board->shortname) ?>">/<?= $board->shortname ?>/</a>
				</td>
				<td>
					<a href="<?= \Uri::create('admin/boards/board/'.$board->shortname) ?>"><?= $board->name ?></a>

					<div class="btn-group pull-right">
						<a class="btn btn-mini btn-primary" href="<?= \Uri::create('admin/boards/board/'.$board->shortname) ?>">
							<?= __('Edit') ?>
						</a>

						<button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
							<span class="caret"></span>
						</button>

						<ul class="dropdown-menu">
							<li>
								<a href="<?= \Uri::create('admin/boards/delete/'.$board->id) ?>"><?= __('Delete') ?></a>
							</li>
						</ul>
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>