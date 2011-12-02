<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<table class="bordered-table" style="width:600px; margin: 10px auto;">
	<thead>
		<tr>
			<th><?php echo _('Name') ?></th>
			<th><?php echo _('Trip') ?></th>
			<th><?php echo _('Total Posts') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		$data_array = json_decode($data, TRUE);
		foreach ($data_array as $d)
		{
			echo '<tr><td>'.$d['name'].'</td><td>'.$d['trip'].'</td><td>'.$d['count(*)'].'</td></tr>';
		}
		?>
	</tbody>
</table>