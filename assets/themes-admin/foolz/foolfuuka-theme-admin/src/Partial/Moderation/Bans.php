<?php

namespace Foolz\FoolFuuka\Theme\Admin\Partial\Moderation;

use Foolz\FoolFuuka\Model\Ban;
use Foolz\Inet\Inet;

class Bans extends \Foolz\FoolFrame\View\View
{
    public function toString()
    {
        $users = $this->getContext()->getService('users');
        $radix_coll = $this->getContext()->getService('foolfuuka.radix_collection');
        $form = $this->getForm();

        ?>
<div class="admin-container">
    <div class="admin-container-header">
        <?= _i('Bans') ?>
    </div>

    <div class="pull-right">
        <?= $form->open(['action' => 'admin/moderation/find_ban']) ?>
        <div class="input-prepend">
            <label class="add-on" for="form_ip">Search by IP</label><?= $form->input('ip'); ?>
        </div>
        <?= $form->close() ?>
    </div>

    <table class="table table-hover table-condensed">
        <thead>
            <tr>
                <th><?= _i('IP') ?></th>
                <th><?= _i('Board') ?></th>
                <th><?= _i('Reason') ?></th>
                <th><?= _i('Appeal') ?></th>
                <th><?= _i('Issued - Length') ?></th>
                <th><?= _i('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->getParamManager()->getParam('bans') as $b) : ?>
            <tr>
                <td><?= Inet::dtop($b->ip) ?><?= $this->getPreferences()->get('foolfuuka.sphinx.global') ?  '<br><small><a href="'.$this->getUri()->create('_/search/poster_ip/'. Inet::dtop($b->ip)).'" target="_blank">'._i('Search posts').'</a></small>' : '' ?></td>
                <td><?= $b->board_id ? '/'.$radix_coll->getById($b->board_id)->shortname.'/' : _i('Global') ?></td>
                <td><?= htmlentities($b->reason) ?><br><small><?= _i('By:').' '.htmlentities($users->getUserBy('id', $b->creator_id)->username) ?></small></td>
                <td><?= htmlentities($b->appeal) ?><br><small><?= _i('Status:').' '.($b->appeal_status == Ban::APPEAL_PENDING ? _i('pending') : '').($b->appeal_status == Ban::APPEAL_REJECTED ? _i('rejected') : '').($b->appeal_status == Ban::APPEAL_NONE ? _i('none') : '') ?></small></td>
                <td><?= date('d-M-y H:i:s T', $b->start) ?>, <?= $b->length ? ($b->length / 24 / 60 / 60).' '._i('Day(s)') : _i('Forever') ?><br><small><?= _i('Status:').' '.(!$b->length || time() < $b->start + $b->length ? _i('ongoing'): _i('expired')) ?></small></td>
                <td>
                    <div class="btn-group">
                        <a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#">
                            <?= _i('Action') ?>
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="<?= $this->getUri()->create('admin/moderation/ban_manage/unban/'.$b->id) ?>"><?= _i('Unban') ?></a></li>
                            <?php if ($b->appeal_status == Ban::APPEAL_PENDING) : ?>
                                <li><a href="<?= $this->getUri()->create('admin/moderation/ban_manage/reject_appeal/'.$b->id) ?>"><?= _i('Reject appeal') ?></a></li>
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
        <li class=""><a href="<?= $this->getParamManager()->getParam('page_url').($page - 1) ?>"><?= _i('Prev') ?></a></li>
        <?php endif; ?>
        <li class=""><a href="<?= $this->getParamManager()->getParam('page_url').($page + 1) ?>"><?= _i('Next') ?></a></li>
      </ul>
    </div>
    <?php endif; ?>
</div>
<?php
    }
}
