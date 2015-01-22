<?php $data_array = json_decode($data, true); ?>
<table class="table table-hover">
    <thead>
        <tr>
            <th class="span2"></th>
            <th class="span4"><?= _i('Posts within Last Hour') ?></th>
            <th class="span4"><?= _i('Posts per Minute') ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?= _i('Board') ?></td>
            <td><?= $data_array['board'][0]['last_hour'] ?></td>
            <td><?= $data_array['board'][0]['per_minute'] ?></td>
        </tr>
        <tr>
            <td><?= _i('Ghost') ?></td>
            <td><?= $data_array['ghost'][0]['last_hour'] ?></td>
            <td><?= $data_array['ghost'][0]['per_minute'] ?></td>
        </tr>
    </tbody>
</table>
