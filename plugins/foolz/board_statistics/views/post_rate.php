<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<?php $data_array = json_decode($data, TRUE); ?>
<table class="bordered-table" style="width:600px; margin: 10px auto;">
	<thead>
	<tr>
		<th><?php echo __('Posts within Last Hour') ?></th>
		<th><?php echo __('Posts per Minute') ?></th>
	</tr>
	</thead>
	<tbody style="text-align:center;">
	<td><?php echo $data_array[0]['COUNT(timestamp)']; ?></td>
	<td><?php echo $data_array[0]['COUNT(timestamp)/60']; ?></td>
	</tbody>
</table>
