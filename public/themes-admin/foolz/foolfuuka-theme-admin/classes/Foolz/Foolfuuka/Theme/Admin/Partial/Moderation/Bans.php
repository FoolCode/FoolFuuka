<?php

namespace Foolz\Foolfuuka\Theme\Admin\Partial\Moderation;

class Bans extends \Foolz\Theme\View
{
	public function toString()
	{
		?>
<div class="admin-container">
	<div class="admin-container-header">
		<?= __('Bans') ?>
	</div>

	<div class="pull-right">
		<?= \Form::open('admin/moderation/find_ban') ?>
		<div class="input-prepend">
			<label class="add-on" for="form_ip">Search by IP</label><?= \Form::input('ip'); ?>
		</div>
		<?= \Form::close() ?>
	</div>

	<table class="table table-hover table-condensed">
		<thead>
			<tr>
				<th><?= __('IP') ?></th>
				<th><?= __('Board') ?></th>
				<th><?= __('Reason') ?></th>
				<th><?= __('Appeal') ?></th>
				<th><?= __('Issued - Length') ?></th>
				<th><?= __('Actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($this->getParamManager()->getParam('bans') as $b) : ?>
			<tr>
				<td><?= \Foolz\Inet\Inet::dtop($b->ip) ?><?= \Preferences::get('foolfuuka.sphinx.global') ?  '<br><small><a href="'.\Uri::create('_/search/poster_ip/'.\Foolz\Inet\Inet::dtop($b->ip)).'" target="_blank">'.__('Search posts').'</a></small>' : '' ?></td>
				<td><?= $b->board_id ? '/'.\Radix::getById($b->board_id)->shortname.'/' : __('Global') ?></td>
				<td><?= e($b->reason) ?><br><small><?= __('By:').' '.e(\Users::getUserBy('id', $b->creator_id)->username) ?></small></td>
				<td><?= e($b->appeal) ?><br><small><?= __('Status:').' '.($b->appeal_status == \BAN::APPEAL_PENDING ? __('pending') : '').($b->appeal_status == \BAN::APPEAL_REJECTED ? __('rejected') : '').($b->appeal_status == \BAN::APPEAL_NONE ? __('none') : '') ?></small></td>
				<td><?= date('d-M-y H:i:s T', $b->start) ?>, <?= $b->length ? ($b->length / 24 / 60 / 60).' '.__('Day(s)') : __('Forever') ?><br><small><?= __('Status:').' '.( ! $b->length || time() < $b->start + $b->length ? __('ongoing'): __('expired')) ?></small></td>
				<td>
					<div class="btn-group">
						<a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#">
							<?= __('Action') ?>
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li><a href="<?= \Uri::create('admin/moderation/ban_manage/unban/'.$b->id) ?>"><?= __('Unban') ?></a></li>
							<?php if ($b->appeal_status == \Ban::APPEAL_PENDING) : ?>
								<li><a href="<?= \Uri::create('admin/moderation/ban_manage/reject_appeal/'.$b->id) ?>"><?= __('Reject appeal') ?></a></li>
							<?php endif; ?>
						</ul>
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php $page = $this->getParamManager()->getParam('page') ?>
	<?php if ($page) : ?>
	<div class="pagination">
	  <ul>
		<?php if ($page > 1) : ?>
		<li class=""><a href="<?= $this->getParamManager()->getParam('page_url').($page - 1) ?>"><?= __('Prev') ?></a></li>
		<?php endif; ?>
		<li class=""><a href="<?= $this->getParamManager()->getParam('page_url').($page + 1) ?>"><?= __('Next') ?></a></li>
	  </ul>
	</div>
	<?php endif; ?>
</div>
<?php
	}
}