<?php

namespace Foolz\FoolFuuka\Theme\Admin\Partial\Moderation;

use Foolz\FoolFuuka\Model\Audit;

class AuditLog extends \Foolz\FoolFrame\View\View
{
    public function toString()
    {
        ?>
<div class="admin-container">
    <div class="admin-container-header">
        <?= _i('Audit Log') ?>
    </div>

    <table class="table table-hover table-condensed">
        <thead>
            <tr>
                <th>Time</th>
                <th>Action</th>
                <th>User</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->getParamManager()->getParam('logs') as $log): ?>
            <tr>
                <td><?= $log->getTime() ?></td>
                <td><?= $log->getAction() ?></td>
                <td><?= $log->getUser() ?></td>
                <td><?= $log->getMessage() ?></td>
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
