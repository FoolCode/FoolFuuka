<table class="table table-hover">
    <thead>
        <tr>
            <th class="span6"><?= _i('Poster') ?></th>
            <th class="span2"><?= _i('Total Posts') ?></th>
            <th class="span2"><?= _i('First Seen') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php $data_array = json_decode($data, true)?>
        <?php foreach ($data_array as $d) : ?>
        <tr>
            <td>
                <?php
                $params = [$this->radix->shortname, 'search'];
                if ($d['name']) {
                    array_push($params, 'username/' . urlencode($d['name']));
                }
                if ($d['trip']) {
                    array_push($params, 'tripcode/' . urlencode($d['trip']));
                }
                $poster_link = $this->uri->create($params);
                ?>
                <a href="<?= $poster_link ?>">
                    <span class="poster_name"><?= htmlentities($d['name']) ?></span> <span class="poster_trip"><?= htmlentities($d['trip']) ?></span>
                </a>
            </td>
            <td><?= $d['postcount'] ?></td>
            <td><?= date('D M d H:i:s Y', $d['firstseen']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
