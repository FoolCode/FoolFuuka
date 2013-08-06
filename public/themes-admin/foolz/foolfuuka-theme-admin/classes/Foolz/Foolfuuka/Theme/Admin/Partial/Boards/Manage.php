<?php

namespace Foolz\Foolfuuka\Theme\Admin\Partial\Boards;

class Manage extends \Foolz\Foolframe\View\View
{
    public function toString()
    { ?>
<div class="admin-container">
    <div class="admin-container-header">
        <?= _i('Boards') ?>
    </div>

    <div class="pull-right">
        <a class="btn btn-success btn-mini" href="<?= $this->getUri()->create('/admin/boards/add/') ?>">
            <i class="icon-plus" style="color: #FFFFFF"></i> <?= _i('Add Board') ?>
        </a>
    </div>

    <table class="table table-hover table-condensed">
        <thead>
            <tr>
                <th class="span1"><?= _i('ID') ?></th>
                <th class="span4"><?= _i('Board') ?></th>
                <th class="span4"><?= _i('Title') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->getParamManager()->getParam('boards') as $board) : ?>
            <tr>
                <td><?= $board->id ?></td>
                <td>
                    <a href="<?= $this->getUri()->create('admin/boards/board/'.$board->shortname) ?>">/<?= $board->shortname ?>/</a>
                </td>
                <td>
                    <a href="<?= $this->getUri()->create('admin/boards/board/'.$board->shortname) ?>"><?= $board->name ?></a>

                    <div class="btn-group pull-right">
                        <a class="btn btn-mini btn-primary" href="<?= $this->getUri()->create('admin/boards/board/'.$board->shortname) ?>">
                            <?= _i('Edit') ?>
                        </a>

                        <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                        </button>

                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?= $this->getUri()->create('admin/boards/delete/'.$board->id) ?>"><?= _i('Delete') ?></a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
    }
}
