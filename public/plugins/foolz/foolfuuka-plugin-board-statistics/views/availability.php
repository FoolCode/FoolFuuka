<table class="table table-hover">
    <thead>
        <tr>
            <th><?= _i('Poster') ?></th>
            <th style="text-align:center"><?= _i('Total Posts') ?></th>
            <th style="text-align:center"><?= _i('Availability') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php $data_array = json_decode($data); ?>
        <?php foreach ($data_array as $d) : ?>
        <tr>
            <td style="width:50%; max-width:50%">
                <?php
                $params = [$this->radix->shortname, 'search'];
                if ($d->name) {
                    array_push($params, 'username/' . urlencode($d->name));
                }
                if ($d->trip) {
                    array_push($params, 'tripcode/' . urlencode($d->trip));
                }

                $poster_link = $this->uri->create($params);

                $values = (($d->std2 < $d->std1) ? [$d->avg2 - $d->std2, $d->avg2 + $d->std2] : [$d->avg1 - $d->std1, $d->avg1 + $d->std1]);
                $val_st = ($values[0] + 86400) % 86400;
                $val_ed = ($values[1] + 86400) % 86400;
                ?>
                <a href="<?= $poster_link ?>">
                    <span class="poster_name"><?= htmlentities($d->name) ?></span> <span class="poster_trip"><?= htmlentities($d->trip) ?></span>
                </a>
            </td>
            <td style="text-align:center"><?= $d->posts ?></td>
            <td style="text-align:center">
                <?= sprintf('%02d:%02d', floor($val_st / 3600), floor($val_st / 60) % 60) ?> - <?= sprintf('%02d:%02d', floor($val_ed / 3600), floor($val_ed / 60) % 60) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<style type="text/css">
    .poster_name, .poster_trip {
        color: #117743;
    }
    .poster_name {
        font-weight: bold;
    }
</style>
