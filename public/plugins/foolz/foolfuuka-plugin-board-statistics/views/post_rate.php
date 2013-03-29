<?php
if ( ! defined('DOCROOT'))
{
	exit('No direct script access allowed');
}
?>

<?php $data_array = json_decode($data, true); ?>
<table class="table table-hover">
	<thead>
		<tr>
			<th class="span2"></th>
			<th class="span4"><?= __('Posts within Last Hour') ?></th>
			<th class="span4"><?= __('Posts per Minute') ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?= __('Board') ?></td>
			<td><?= $data_array['board'][0]['last_hour'] ?></td>
			<td><?= $data_array['board'][0]['per_minute'] ?></td>
		</tr>
		<tr>
			<td><?= __('Ghost') ?></td>
			<td><?= $data_array['ghost'][0]['last_hour'] ?></td>
			<td><?= $data_array['ghost'][0]['per_minute'] ?></td>
		</tr>
	</tbody>
</table>