<?php $data_array = json_decode($data); ?>

<h4><?= _i('Board') ?>:</h4>
<table class="table table-hover">
    <thead>
        <tr>
            <th class="span6"><?= _i('Poster') ?></th>
            <th class="span2"><?= _i('Last Seen') ?></th>
            <th class="span2"><?= _i('Latest Post') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data_array->board as $d) : ?>
        <tr>
            <td>
                <?php
                $params = [$this->radix->shortname, 'search'];
                if ($d->name) {
                    array_push($params, 'username/' . urlencode($d->name));
                }
                if ($d->trip) {
                    array_push($params, 'tripcode/' . urlencode($d->trip));
                }
                $poster_link = $this->uri->create($params);
                ?>
                <a href="<?= $poster_link ?>">
                    <span class="poster_name"><?= htmlentities($d->name) ?></span> <span class="poster_trip"><?= htmlentities($d->trip) ?></span>
                </a>
            </td>
            <td><?= date('D M d H:i:s Y', $d->timestamp) ?></td>
            <td>
                <a href="<?= $this->uri->create([$this->radix->shortname, 'post', $d->num . ($d->subnum ? '_' . $d->subnum : '')]) ?>">
                    &gt;&gt;<?= $d->num . ($d->subnum ? ',' . $d->subnum : '') ?>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (count($data_array->ghost)) : ?>
<h4><?= _i('Ghost') ?>:</h4>
<table class="table table-hover">
    <thead>
        <tr>
            <th class="span6"><?= _i('Poster') ?></th>
            <th class="span2"><?= _i('Last Seen') ?></th>
            <th class="span2"><?= _i('Latest Post') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data_array->ghost as $d) : ?>
        <tr>
            <td>
                <?php
                $params = [$this->radix->shortname, 'search'];
                if ($d->name) {
                    array_push($params, 'username/' . urlencode($d->name));
                }
                if ($d->trip) {
                    array_push($params, 'tripcode/' . urlencode($d->trip));
                }
                $poster_link = $this->uri->create($params);
                ?>
                <a href="<?= $poster_link ?>">
                    <span class="poster_name"><?= htmlentities($d->name) ?></span> <span class="poster_trip"><?= htmlentities($d->trip) ?></span>
                </a>
            </td>
            <td><?= date('D M d H:i:s Y', $d->timestamp) ?></td>
            <td>
                <a href="<?= $this->uri->create([$this->radix->shortname, 'post', $d->num . ($d->subnum ? '_' . $d->subnum : '')]) ?>">
                    &gt;&gt;<?= $d->num . ($d->subnum ? ',' . $d->subnum : '') ?>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
